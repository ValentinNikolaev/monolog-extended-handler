<?php

namespace Tunguska\Monolog\Formatter;


use Tunguska\Monolog\Helpers\EnvHelper;
use Tunguska\Monolog\Helpers\FieldsHelper;
use Monolog\Formatter\NormalizerFormatter;

/**
 * Serializes a log message to custom Format
 *
 * @author Valentin Nikolaev <valenti.nikolaev@gmail.com>
 */
class LogFileFormatter extends NormalizerFormatter
{
    public const APP_TYPE = 'php-web';
    public const  APP_TYPE_CLI = 'php-cli';
    public const CHANNEL_DEFAULT = 'data';

    /**
     * @var string an application level_name for the Logstash log message, used to fill the @type field
     */
    protected $applicationName;

    /**
     * @var bool
     */
    private $skipEnv = false;

    /**
     * @param array<mixed> $options
     */
    public function __construct(array $options = [])
    {
        // requires a ISO 8601 format date with optional millisecond precision.
        parent::__construct('Y-m-d\TH:i:sP');
        $this->applicationName = php_sapi_name() == 'cli'
            ? static::APP_TYPE_CLI
            : static::APP_TYPE;

        foreach ($options as $key => $value) {
            if (property_exists($this, $key)) {
                $this->$key = $value;
            }
        }
    }

    /**
     * @param array<mixed> $record
     *
     * @return string
     */
    public function format(array $record): string
    {
        $logLine = $this->prepareLogRecord($record);
        return $this->toJson($logLine) . "\n";
    }

    /**
     * @param array<mixed> $record
     *
     * @return array<mixed>
     */
    public function prepareLogRecord(array $record): array
    {
        $record = parent::format($record);
        $record = FieldsHelper::fillMissingRecordFields($record, static::CHANNEL_DEFAULT);

        /**
         * some magic to support exceptions
         */

        if (!empty($record['context']['exception'])) {
            $exception = $record['context']['exception'];
            $record['context']['exception'] = FieldsHelper::prepareException($exception);

            if (!empty($exception['previous'])) {
                $record['context']['exception_previous'] = FieldsHelper::prepareException($exception['previous']);
            }

            if (empty($record['message'])) {
                $record['message'] = $exception['message'];
            }
        }

        $recordEnv = (!empty($record['env']) && is_array($record['env'])) ? $record['env'] : [];

        $message = [
            'app' => $this->applicationName,
            'level' => $record['level'],
            'level_name' => $record['level_name'],
            'channel' => FieldsHelper::prepareChannel($record['channel']),
            'env' => EnvHelper::getServerInfo($recordEnv),
            'context' => FieldsHelper::prepareArrayField($record['context']),
            'datetime' => $record['datetime'],
            'message' => FieldsHelper::prepareTextMessage($record['message']),
            'extra' => FieldsHelper::prepareArrayField($record['extra']),
        ];

        if ($this->skipEnv) {
            $message['env'] = [];
        }

        return $message;
    }
}
