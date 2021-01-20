<?php

namespace Tunguska\Monolog\Tests;

use DateTime;
use Monolog\Formatter\FormatterInterface;
use Monolog\Logger;
use PHPUnit\Framework\MockObject\MockObject;

class TestCase extends \PHPUnit\Framework\TestCase
{
    /**
     * @param int $level
     * @param string $message
     * @param array $context
     *
     * @return array Record
     */
    protected function getRecord($level = Logger::WARNING, $message = 'test', $context = []): array
    {
        return [
            'message' => $message,
            'context' => $context,
            'level' => $level,
            'level_name' => Logger::getLevelName($level),
            'channel' => 'test',
            'datetime' => DateTime::createFromFormat('U.u', sprintf('%.6F', microtime(true))),
            'extra' => [],
        ];
    }

    /**
     * @return array
     */
    protected function getMultipleRecords(): array
    {
        return [
            $this->getRecord(Logger::DEBUG, 'debug message 1'),
            $this->getRecord(Logger::DEBUG, 'debug message 2'),
            $this->getRecord(Logger::INFO, 'information'),
            $this->getRecord(Logger::WARNING, 'warning'),
            $this->getRecord(Logger::ERROR, 'error'),
        ];
    }

    /**
     * @return MockObject
     */
    protected function getIdentityFormatter(): MockObject
    {
        /**
         * @var $formatter MockObject
         */
        $formatter = $this->getMockBuilder(FormatterInterface::class)
            ->setMethods(['format', 'formatBatch'])
            ->getMock();

//        $formatter = $this->getMockBuilder('Monolog\\Formatter\\FormatterInterface');
        $formatter->expects($this->any())
            ->method('format')
            ->will(
                $this->returnCallback(
                    function ($record) {
                        return $record['message'];
                    }
                )
            );

        return $formatter;
    }
}
