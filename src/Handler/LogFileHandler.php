<?php

namespace Tunguska\Monolog\Handler;

use Tunguska\Monolog\Helpers\FileHelper;
use InvalidArgumentException;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;

/**
 * Stores to any json file resource
 *
 * @author Valentin Nikolaev <valenti.nikolaev@gmail.cm>
 */
class LogFileHandler extends StreamHandler
{

    public const DATE_FORMAT = 'Y-m-d-H';
    public const  LOG_EXTENSION = 'json';
    public const  LOG_SUBDIR = 'json';

    /**
     * LogFileHandler constructor.
     * @param string|resource $channel
     * @param string $logPath
     * @param int $level
     * @param string $dateFormat
     */
    public function __construct(
        $channel,
        string $logPath = '',
        int $level = Logger::DEBUG,
        string $dateFormat = self::DATE_FORMAT
    ) {
        if (!FileHelper::isStream($channel)) {
            $dir = FileHelper::getFullLogDir($logPath);
            if (!is_dir($dir)) {
                if (!mkdir($dir, 0755, true)) {
                    $error = error_get_last();
                    throw new InvalidArgumentException(
                        sprintf(
                            'The dir "%s" could not be created: ' . (is_null($error) ? 'unknown' : $error['message']),
                            $dir
                        )
                    );
                }
            }

            $stream = FileHelper::getChannelFileStreamPath($dir, $channel, $dateFormat);
        } else {
            $stream = $channel;
        }

        parent::__construct($stream, $level);
    }
}
