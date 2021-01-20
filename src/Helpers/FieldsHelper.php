<?php

namespace Tunguska\Monolog\Helpers;

use Monolog\Logger;

class FieldsHelper
{
    public const DEFAULT_LOG_LEVEL = Logger::ERROR;

    /**
     * remove backspaces e.t.c
     *
     * @param mixed $message
     * @todo should be fixed as here could be object/resources or something else
     *
     * @return mixed
     */
    public static function prepareTextMessage($message = '')
    {
        if (is_string($message)) {
            $message = trim($message);
            if (mb_strlen($message) == 0) {
                return $message;
            }

            $message = preg_replace('#(\r\n|\r|\n){2,}#m', ' ', trim(strip_tags($message)));
            $message = preg_replace('#\t+#', '', $message ?? '');
            $message = preg_replace('# {2,}#', ' ', $message ?? '');
            $message = preg_replace('#^[ \t]+#m', '', $message ?? '');

            /** pipeline */
            if (!is_null($message) && strpos($message, 'Pipeline') !== false) {
                $message = substr($message, 0, strpos($message, 'Pipeline'));
                if (strrpos($message, '#') !== false) {
                    $message = substr($message, 0, strrpos($message, '#'));
                }
            }
        }
        return $message;
    }

    /**
     * @param array<mixed> $record
     * @param string|null $channel
     *
     * @return array<mixed>
     */
    public static function fillMissingRecordFields(array $record, string $channel = null): array
    {
        /**
         * check missing fields
         */
        if (empty($record['datetime'])) {
            $record['datetime'] = gmdate('c');
        }

        if (empty($record['channel'])) {
            $record['channel'] = $channel;
        }

        if (empty($record['level'])) {
            $record['level'] = self::DEFAULT_LOG_LEVEL;
            $record['level_name'] = Logger::getLevelName(self::DEFAULT_LOG_LEVEL);
        }

        if (empty($record['level_name'])) {
            $record['level_name'] = Logger::getLevelName($record['level']);
        }

        if (empty($record['message'])) {
            $record['message'] = null;
        }

        if (empty($record['context'])) {
            $record['context'] = [];
        }

        if (empty($record['extra'])) {
            $record['extra'] = [];
        }

        foreach ($record['context'] as $key => &$val) {
            if ($key == 'message') {
                $val = FieldsHelper::prepareTextMessage($val);
                if (!$record['message']) {
                    $record['message'] = $val;
                }
            }
        }

        return $record;
    }

    /**
     * Prefer array here. Or it will be converted to a string
     *
     * @param mixed $exceptionArray
     *
     * @return string
     */
    public static function prepareException($exceptionArray): string
    {
        if (!is_array($exceptionArray)) {
            return (string)$exceptionArray;
        }

        $trace = [];
        $line = 0;

        if (!empty($exceptionArray['trace'])) {
            $trace = $exceptionArray['trace'];
        }

        if (!empty($exceptionArray['line'])) {
            $line = (int)$exceptionArray['line'];
        }

        return self::arrayToString(
            [
                'message' => FieldsHelper::prepareTextMessage($exceptionArray['message'] ?? ''),
                'class' => $exceptionArray['class'] ?? '',
                'code' => $exceptionArray['code'] ?? '',
                'file' => $exceptionArray['file'] ?? '',
                'line' => $line,
                'trace' => self::arrayToString($trace),
            ]
        );
    }

    /**
     * @param string $channel
     *
     * @return string
     */
    public static function prepareChannel(string $channel): string
    {
        return strtolower(str_replace(["-", "."], "_", trim($channel)));
    }

    /**
     * Need to convert array's 3rd level into string, if it exists
     *
     * @param mixed $context
     * @todo possibly we should filter context values here or return array here.
     *
     * @return mixed
     */
    public static function prepareArrayField($context)
    {
        if (!is_array($context)) {
            return $context;
        }
        /**
         * check do we have objects or arrays
         */
        return array_map(
            function ($value) {
                if (is_object($value)) {
                    return self::objectToString($value);
                }

                if (is_array($value)) {
                    return array_map(
                        function ($v) {
                            if (is_object($v)) {
                                return self::objectToString($v);
                            }
                            if (is_array($v)) {
                                return self::arrayToString($v);
                            }
                            return $v;
                        },
                        $value
                    );
                }
                return $value;
            },
            $context
        );
    }

    /**
     * If project can be converted into string - let's do it. Otherwise - serialize
     *
     * @param object $object
     *
     * @return string
     */
    private static function objectToString(object $object): string
    {
        if (method_exists($object, '__toString')) {
            return $object->__toString();
        }

        $jsonObject = json_encode($object);

        $result = (JSON_ERROR_NONE === json_last_error())
            ? $jsonObject
            : serialize($object);

        if ($result === false) {
            return '';
        }

        return $result;
    }

    /**
     * We usually should convert array to a string because we shouldn't have multilevel arrays in json log
     *
     * @param array<mixed> $array
     *
     * @return string
     */
    private static function arrayToString(array $array): string
    {
        if (count($array) == count($array, COUNT_RECURSIVE)) {
            /**
             * we shouldn't implode Associative arrays as some keys can be missing
             */
            if (!self::isAssoc($array)) {
                return implode("; ", $array);
            }
        }

        $jsonObject =  json_encode($array, JSON_FORCE_OBJECT);

        $result = (JSON_ERROR_NONE === json_last_error())
            ? $jsonObject
            : serialize($array);

        if ($result === false) {
            return '';
        }

        return $result;
    }


    /**
     * Check if array is associative
     *
     * @param array<mixed> $array
     *
     * @return bool
     */
    private static function isAssoc(array $array): bool
    {
        return count(array_filter(array_keys($array), 'is_string')) > 0;
    }


}