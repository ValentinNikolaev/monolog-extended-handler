<?php

namespace Tunguska\Monolog\Helpers;

use InvalidArgumentException;
use Tunguska\Monolog\Handler\LogFileHandler;

class FileHelper
{
    /**
     * @param string $logPath
     *
     * @return string
     */
    public static function getFullLogDir(string $logPath = ''): string
    {
        $logPath = rtrim($logPath, '\\/') . DIRECTORY_SEPARATOR;
        return $logPath . LogFileHandler::LOG_SUBDIR . DIRECTORY_SEPARATOR;
    }

    /**
     * @param string $dateFormat
     *
     * @return string
     */
    public static function getChannelFileSuffix(string $dateFormat): string
    {
        return '_' . date($dateFormat) . '.' . LogFileHandler::LOG_EXTENSION;
    }

    /**
     * @param string $dir
     * @param resource|string $channel
     * @param string $dateFormat
     *
     * @return string
     */
    public static function getChannelFileStreamPath(string $dir, $channel, string $dateFormat): string
    {
        return $dir . $channel . self::getChannelFileSuffix($dateFormat);
    }

    /**
     * @param mixed $channel
     *
     * @return bool
     */
    public static function isStream($channel): bool
    {
        if (is_resource($channel)) {
            return true;
        }

        if (!is_string($channel)) {
            throw new InvalidArgumentException();
        }

        $checkWords = [
            'php://',
            'file://',
        ];

        foreach ($checkWords as $word) {
            if (strpos($channel, $word) !== false) {
                return true;
            }
        }


        return false;
    }

}