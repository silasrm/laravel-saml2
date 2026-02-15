<?php declare(strict_types=1);

namespace Slides\Saml2\Tests;

use PHPUnit\Framework\TestCase;
use Slides\Saml2\Helpers\ConsoleHelper;

class ConsoleHelperTest extends TestCase
{
    public function testStringToArray(): void
    {
        static::assertEquals([], ConsoleHelper::stringToArray(''));
        static::assertEquals([], ConsoleHelper::stringToArray(null));

        static::assertEquals(
            ['item1' => 'value1', 'item2' => 'value2'],
            ConsoleHelper::stringToArray('item1:value1,item2:value2')
        );

        static::assertEquals(
            ['item1' => 'value1', 'item2' => 'value 2'],
            ConsoleHelper::stringToArray(' item1 :value1 , item2 :value 2')
        );

        static::assertEquals(
            ['value1', 'value2', 'value3'],
            ConsoleHelper::stringToArray('value1,value2,value3')
        );
    }

    public function testArrayToString(): void
    {
        static::assertSame('', ConsoleHelper::arrayToString([]));
        static::assertSame('one,two,three', ConsoleHelper::arrayToString(['one', 'two', 'three']));
        static::assertSame('item1:value1,item2:value2', ConsoleHelper::arrayToString([
            'item1' => 'value1',
            'item2' => 'value2',
        ]));
    }

    public function testArrayToStringSkipsNestedArrays(): void
    {
        static::assertSame('item1:value1,item3:value3', ConsoleHelper::arrayToString([
            'item1' => 'value1',
            'item2' => ['nested' => 'skip-me'],
            'item3' => 'value3',
        ]));
    }

    public function testStringToArraySupportsCustomDelimiters(): void
    {
        static::assertSame(
            ['item1' => 'value1', 'item2' => 'value2'],
            ConsoleHelper::stringToArray('item1=value1;item2=value2', '=', ';')
        );
    }
}
