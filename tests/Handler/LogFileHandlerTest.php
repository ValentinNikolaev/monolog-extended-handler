<?php

namespace Tunguska\Monolog\Tests\Handler;

use Exception;
use Tunguska\Monolog\Handler\LogFileHandler;
use Tunguska\Monolog\Tests\TestCase;
use InvalidArgumentException;
use LogicException;
use Monolog\Logger;
use ReflectionProperty;
use UnexpectedValueException;

class LogFileHandlerTest extends TestCase
{
    /**
     * @covers LogFileHandler::__construct
     * @covers LogFileHandler::write
     * @throws Exception
     */
    public function testWrite()
    {
        $handle = fopen('php://memory', 'a+');
        $handler = new LogFileHandler($handle);
        $handler->setFormatter($this->getIdentityFormatter());
        $handler->handle($this->getRecord(Logger::WARNING, 'test'));
        $handler->handle($this->getRecord(Logger::WARNING, 'test2'));
        $handler->handle($this->getRecord(Logger::WARNING, 'test3'));
        fseek($handle, 0);
        $this->assertEquals('testtest2test3', fread($handle, 100));
    }

    /**
     * @covers LogFileHandler::close
     * @throws Exception
     */
    public function testCloseKeepsExternalHandlersOpen()
    {
        $handle = fopen('php://memory', 'a+');
        $handler = new LogFileHandler($handle);
        $this->assertTrue(is_resource($handle));
        $handler->close();
        $this->assertTrue(is_resource($handle));
    }

    /**
     * @covers LogFileHandler::close
     */
    public function testClose()
    {
        $handler = new LogFileHandler('php://memory');
        $handler->handle($this->getRecord(Logger::WARNING, 'test'));
        $streamProp = new ReflectionProperty('Tunguska\Monolog\Handler\LogFileHandler', 'stream');
        $streamProp->setAccessible(true);
        $handle = $streamProp->getValue($handler);

        $this->assertTrue(is_resource($handle));
        $handler->close();
        $this->assertFalse(is_resource($handle));
    }

    /**
     * @covers LogFileHandler::write
     */
    public function testWriteCreatesTheStreamResource()
    {
        $handler = new LogFileHandler('php://memory');
        $handler->handle($this->getRecord());
    }

    /**
     * @covers LogFileHandler::__construct
     * @covers LogFileHandler::write
     * @throws Exception
     */
    public function testWriteLocking()
    {
        $temp = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'monolog_locked_log';
        $handler = new LogFileHandler('default', $temp, Logger::DEBUG);
        $handler->handle($this->getRecord());
    }

    /**
     * @expectedException LogicException
     * @covers LogFileHandler::__construct
     * @covers LogFileHandler::write
     */
    public function testWriteMissingResource()
    {
        $handler = new LogFileHandler(null);
        $handler->handle($this->getRecord());
    }

    public function invalidArgumentProvider()
    {
        return [
            [1],
            [[]],
            [['bogus://url']],
        ];
    }

    /**
     * @dataProvider invalidArgumentProvider
     * @expectedException InvalidArgumentException
     * @covers       LogFileHandler::__construct
     *
     * @param $invalidArgument
     *
     * @throws Exception
     */
    public function testWriteInvalidArgument($invalidArgument)
    {
        new LogFileHandler($invalidArgument);
    }

    /**
     * @expectedException UnexpectedValueException
     * @covers LogFileHandler::__construct
     * @covers LogFileHandler::write
     */
    public function testWriteInvalidResource()
    {
        $handler = new LogFileHandler('bogus://url', '/tmp/monolog-logs');
        $handler->handle($this->getRecord());
    }

    /**
     * @expectedException UnexpectedValueException
     * @covers LogFileHandler::__construct
     * @covers LogFileHandler::write
     * @throws Exception
     */
    public function testWriteNonExistingResource()
    {
        $handler = new LogFileHandler('ftp://foo/bar/baz/' . rand(0, 10000), '/tmp/monolog-logs');
        $handler->handle($this->getRecord());
    }

    /**
     * @coversLogFileHandler::__construct
     * @covers LogFileHandlerr::write
     * @throws Exception
     */
    public function testWriteNonExistingFileResource()
    {
        $handler = new LogFileHandler(
            'file://' . sys_get_temp_dir() . '/bar/' . rand(
                0,
                10000
            ) . DIRECTORY_SEPARATOR . rand(0, 10000),
            '/tmp/monolog-logs'
        );
        $handler->handle($this->getRecord());
    }
}
