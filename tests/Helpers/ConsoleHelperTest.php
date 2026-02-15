<?php

declare(strict_types=1);

namespace Slides\Saml2\Tests;

use PHPUnit\Framework\TestCase;
use Slides\Saml2\Helpers\ConsoleHelper;

/**
 * @internal
 *
 * @coversNothing
 */
class ConsoleHelperTest extends TestCase
{
    public function testStringToArray(): void
    {
        self::assertEquals([], ConsoleHelper::stringToArray(''));
        self::assertEquals([], ConsoleHelper::stringToArray(null));

        self::assertEquals(
            ['item1' => 'value1', 'item2' => 'value2'],
            ConsoleHelper::stringToArray('item1:value1,item2:value2'),
        );

        self::assertEquals(
            ['item1' => 'value1', 'item2' => 'value 2'],
            ConsoleHelper::stringToArray(' item1 :value1 , item2 :value 2'),
        );

        self::assertEquals(
            ['value1', 'value2', 'value3'],
            ConsoleHelper::stringToArray('value1,value2,value3'),
        );
    }

    public function testArrayToString(): void
    {
        self::assertSame('', ConsoleHelper::arrayToString([]));
        self::assertSame('one,two,three', ConsoleHelper::arrayToString(['one', 'two', 'three']));
        self::assertSame('item1:value1,item2:value2', ConsoleHelper::arrayToString([
            'item1' => 'value1',
            'item2' => 'value2',
        ]));
    }

    public function testArrayToStringSkipsNestedArrays(): void
    {
        self::assertSame('item1:value1,item3:value3', ConsoleHelper::arrayToString([
            'item1' => 'value1',
            'item2' => ['nested' => 'skip-me'],
            'item3' => 'value3',
        ]));
    }

    public function testStringToArraySupportsCustomDelimiters(): void
    {
        self::assertSame(
            ['item1' => 'value1', 'item2' => 'value2'],
            ConsoleHelper::stringToArray('item1=value1;item2=value2', '=', ';'),
        );
    }
}
