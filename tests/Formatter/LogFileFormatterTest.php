<?php

namespace Tunguska\Monolog\Tests\Formatter;

use DateTime;
use LogicException;
use Monolog\Utils;
use ReflectionException;
use ReflectionMethod;
use RuntimeException;
use stdClass;
use Tunguska\Monolog\Formatter\LogFileFormatter;
use Tunguska\Monolog\Helpers\FieldsHelper;
use Tunguska\Monolog\Tests\TestCase;


/**
 * @covers LogFileFormatter
 */
class LogFileFormatterTest extends TestCase
{
    public function testFormat()
    {
        $datetime = gmdate('c');
        $formatter = new LogFileFormatter(['skipEnv' => true]);
        $formatted = $formatter->format(
            [
                'level' => null,
                'level_name' => 'ERROR',
                'channel' => 'meh',
                'message' => 'foo',
                'datetime' => $datetime,
                'extra' => [
                    'foo' => new TestFooNorm(),
                    'bar' => new TestBarNorm(),
                    'baz' => [],
                    'res' => fopen('php://memory', 'rb'),
                ],
                'context' => [
                    'foo' => 'bar',
                    'baz' => 'qux',
                    'inf' => INF,
                    '-inf' => -INF,
                    'nan' => acos(4),
                ],
            ]
        );

        $this->assertEquals(
            json_encode(
                [
                    "app" => "php-cli",
                    'level' => FieldsHelper::DEFAULT_LOG_LEVEL,
                    'level_name' => 'ERROR',
                    'channel' => 'meh',
                    'env' => [],
                    'context' => [
                        'foo' => 'bar',
                        'baz' => 'qux',
                        'inf' => 'INF',
                        '-inf' => '-INF',
                        'nan' => 'NaN',
                    ],
                    'datetime' => $datetime,
                    'message' => 'foo',
                    'extra' => [
                        'foo' => ['Tunguska\\Monolog\\Tests\\Formatter\\TestFooNorm' => '{"foo":"foo"}'],
                        'bar' => ['Tunguska\\Monolog\\Tests\\Formatter\\TestBarNorm' => 'bar'],
                        'baz' => [],
                        'res' => '[resource(stream)]',
                    ],

                ]
            ) . "\n",
            $formatted
        );
    }

    public function testFormatExceptions()
    {
        $formatter = new LogFileFormatter();
        $e = new LogicException('bar');
        $e2 = new RuntimeException('foo', 0, $e);
        $formatted = $formatter->prepareLogRecord(
            [
                'context' => [
                    'exception' => $e2,
                ],
            ]
        );

        $this->assertTrue(isset($formatted['context']['exception_previous']));
    }

    public function testNormalizeHandleLargeArrays()
    {
        $formatter = new LogFileFormatter();
        $largeArray = range(1, 2000);

        $res = $formatter->prepareLogRecord(
            [
                'level_name' => 'CRITICAL',
                'channel' => 'test',
                'message' => 'bar',
                'context' => [$largeArray],
                'datetime' => new DateTime(),
                'extra' => [],
            ]
        );

        $this->assertCount(1001, $res['context'][0]);
        $this->assertEquals('Over 1000 items (2000 total), aborting normalization', $res['context'][0]['...']);
    }

    /**
     * @expectedException RuntimeException
     * @throws ReflectionException
     */
    public function testThrowsOnInvalidEncoding()
    {
        $formatter = new LogFileFormatter();
        $reflMethod = new ReflectionMethod($formatter, 'toJson');
        $reflMethod->setAccessible(true);

        // send an invalid unicode sequence as a object that can't be cleaned
        $record = new stdClass();
        $record->message = "\xB1\x31";
        $res = $reflMethod->invoke($formatter, $record);
        if (PHP_VERSION_ID < 50500 && $res === '{"message":null}') {
            throw new RuntimeException(
                'PHP 5.3/5.4 throw a warning and null the value instead of returning false entirely'
            );
        }
    }

    /**
     * @throws ReflectionException
     */
    public function testConvertsInvalidEncodingAsLatin9()
    {
        $formatter = new LogFileFormatter();
        $reflMethod = new ReflectionMethod($formatter, 'toJson');
        $reflMethod->setAccessible(true);

        $res = $reflMethod->invoke($formatter, ['message' => "\xA4\xA6\xA8\xB4\xB8\xBC\xBD\xBE"]);

        if (version_compare(PHP_VERSION, '5.5.0', '>=')) {
            $this->assertSame('{"message":"€ŠšŽžŒœŸ"}', $res);
        } else {
            // PHP <5.5 does not return false for an element encoding failure,
            // instead it emits a warning (possibly) and nulls the value.
            $this->assertSame('{"message":null}', $res);
        }
    }

    public function providesDetectAndCleanUtf8()
    {
        $obj = new stdClass();

        return [
            'null' => [null, null],
            'int' => [123, 123],
            'float' => [123.45, 123.45],
            'bool false' => [false, false],
            'bool true' => [true, true],
            'ascii string' => ['abcdef', 'abcdef'],
            'latin9 string' => ["\xB1\x31\xA4\xA6\xA8\xB4\xB8\xBC\xBD\xBE\xFF", '±1€ŠšŽžŒœŸÿ'],
            'unicode string' => ['¤¦¨´¸¼½¾€ŠšŽžŒœŸ', '¤¦¨´¸¼½¾€ŠšŽžŒœŸ'],
            'empty array' => [[], []],
            'array' => [['abcdef'], ['abcdef']],
            'object' => [$obj, $obj],
        ];
    }

    /**
     * @param int $code
     * @param string $msg
     *
     * @dataProvider providesHandleJsonErrorFailure
     * @throws ReflectionException
     */
    public function testHandleJsonErrorFailure($code, $msg)
    {
        $formatter = new Utils();
        $reflMethod = new ReflectionMethod($formatter, 'handleJsonError');
        $reflMethod->setAccessible(true);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage($msg);
        $reflMethod->invoke($formatter, $code, 'faked');
    }

    public function providesHandleJsonErrorFailure(): array
    {
        return [
            'depth' => [JSON_ERROR_DEPTH, 'Maximum stack depth exceeded'],
            'state' => [JSON_ERROR_STATE_MISMATCH, 'Underflow or the modes mismatch'],
            'ctrl' => [JSON_ERROR_CTRL_CHAR, 'Unexpected control character found'],
            'default' => [-1, 'Unknown error'],
        ];
    }

}

class TestFooNorm
{
    public $foo = 'foo';
}

class TestBarNorm
{
    public function __toString()
    {
        return 'bar';
    }
}

class TestStreamFoo
{
    public $foo;
    public $resource;

    public function __construct($resource)
    {
        $this->resource = $resource;
        $this->foo = 'BAR';
    }

    public function __toString()
    {
        fseek($this->resource, 0);

        return $this->foo . ' - ' . (string)stream_get_contents($this->resource);
    }
}