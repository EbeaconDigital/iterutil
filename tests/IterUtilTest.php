<?php

use PHPUnit\Framework\TestCase;
use ebeacon\iterutil\IterUtil;
use ebeacon\iterutil\IterUtilException;

class MockCollection implements \ArrayAccess, \Countable
{
    public $var1;
    public $var2;
    private $inner;

    public function __construct($var1 = null, $var2 = null)
    {
        $this->var1 = $var1;
        $this->var2 = $var2;
        $this->inner = [];
    }

    public function offsetExists($offset)
    {
        return isset($this->inner[$offset]);
    }

    public function offsetGet($offset)
    {
        return ($this->offsetExists($offset))
            ? $this->inner[$offset]
            : null;
    }

    public function offsetSet($offset, $value)
    {
        if ($offset === null) {
            $this->inner[] = $value;
        } else {
            $this->inner[$offset] = $value;
        }
    }

    public function offsetUnset($offset)
    {
        if ($this->offsetExists($offset)) {
            unset($this->inner[$offset]);
        }
    }

    public function count()
    {
        return count($this->inner);
    }
}

final class IterUtilTest extends TestCase
{
    private static $testArray = [
        "A" => 1,
        "B" => 2,
        "C" => 3,
        "D" => 4,
        "E" => 5,
        "F" => 6,
        "G" => 7
    ];

    public function testAll()
    {
        $result = IterUtil::from([true, true, true])
            ->all();
        $this->assertTrue($result);

        $result = IterUtil::from([true, false, true])
            ->all();
        $this->assertFalse($result);

        $result = IterUtil::from([true, false, false])
            ->all();
        $this->assertFalse($result);

        $result = IterUtil::from([false, false, false])
            ->all();
        $this->assertFalse($result);

        $result = IterUtil::from([])
            ->all();
        $this->assertTrue($result);
    }

    public function testAny()
    {
        $result = IterUtil::from([true, true, true])
            ->any();
        $this->assertTrue($result);

        $result = IterUtil::from([true, false, true])
            ->any();
        $this->assertTrue($result);

        $result = IterUtil::from([true, false, false])
            ->any();
        $this->assertTrue($result);

        $result = IterUtil::from([false, false, false])
            ->any();
        $this->assertFalse($result);

        $result = IterUtil::from([])
            ->any();
        $this->assertFalse($result);
    }

    public function testChain()
    {
        $generator_1 = function () {
            yield "H";
            yield "i";
            yield " ";
        };

        $generator_2 = function () {
            yield "t";
            yield "h";
            yield "e";
            yield "r";
            yield "e";
        };

        $generator_3 = function () {
            yield "!";
        };

        $expected = [
            0 => "H",
            1 => "i",
            2 => " ",
            3 => "t",
            4 => "h",
            5 => "e",
            6 => "r",
            7 => "e",
            8 => "!"
        ];

        $result = IterUtil::from($generator_1())
            ->chain($generator_2())
            ->chain($generator_3())
            ->collect();
        $this->assertEquals($expected, $result);

        $result = IterUtil::from($generator_1())
            ->chain($generator_2(), $generator_3())
            ->collect();
        $this->assertEquals($expected, $result);
    }

    public function testChunk()
    {
        $result = IterUtil::from(static::$testArray)
            ->chunk(2)
            ->collect();
        $this->assertEquals([[1, 2], [3, 4], [5, 6], [7]], $result);

        $result = IterUtil::from(static::$testArray)
            ->chunk(3)
            ->collect();
        $this->assertEquals([[1, 2, 3], [4, 5, 6], [7]], $result);

        $result = IterUtil::from(static::$testArray)
            ->chunk(4)
            ->collect();
        $this->assertEquals([[1, 2, 3, 4], [5, 6, 7]], $result);

        $result = IterUtil::from(static::$testArray)
            ->chunk(1)
            ->collect();
        $this->assertEquals([[1], [2], [3], [4], [5], [6], [7]], $result);
    }

    public function testChunkFailsOnZeroN()
    {
        $this->expectException(IterUtilException::class);
        $this->expectExceptionCode(IterUtilException::NON_ZERO_POSITIVE_INTEGER_REQUIRED);
        $iter = IterUtil::from(static::$testArray)
            ->chunk(0);
    }

    public function testChunkFailsOnNegativeN()
    {
        $this->expectException(IterUtilException::class);
        $this->expectExceptionCode(IterUtilException::NON_ZERO_POSITIVE_INTEGER_REQUIRED);
        $iter = IterUtil::from(static::$testArray)
            ->chunk(-1);
    }

    public function testChunkFailsOnStringN()
    {
        $this->expectException(IterUtilException::class);
        $this->expectExceptionCode(IterUtilException::NON_ZERO_POSITIVE_INTEGER_REQUIRED);
        $iter = IterUtil::from(static::$testArray)
            ->chunk("three");
    }

    public function testCount()
    {
        $result = IterUtil::from(static::$testArray)
            ->count();
        $this->assertEquals(7, $result);

        $result = IterUtil::from(static::$testArray)
            ->take(3)
            ->count();
        $this->assertEquals(3, $result);
    }

    public function testCollect()
    {
        $result = IterUtil::from(static::$testArray)
            ->collect();
        $this->assertInternalType('array', $result);
        $this->assertCount(7, $result);
        $this->assertEquals(static::$testArray, $result);

        $result = IterUtil::from(static::$testArray)
            ->collect(MockCollection::class, 1);
        $this->assertInstanceOf(MockCollection::class, $result);
        $this->assertCount(7,  $result);
        $this->assertEquals(1, $result->var1);
        $this->assertNull($result->var2);

        $this->expectException(IterUtilException::class);
        $this->expectExceptionCode(IterUtilException::COLLECTION_CLASS_MUST_IMPLEMENT_ARRAY_ACCESS);
        $result = IterUtil::from(static::$testArray)
            ->collect(DateTime::class);
    }

    public function testEvenSquares()
    {
        $result = IterUtil::from(static::$testArray)
            ->filter(function ($value) { return $value % 2 == 0; })
            ->map(function ($value) { return $value * $value; })
            ->collect();
        $this->assertEquals(["B" => 4, "D" => 16, "F" => 36], $result);

        $result = IterUtil::from(static::$testArray)
            ->map(function ($value) { return $value * $value; })
            ->filter(function ($value) { return $value % 2 == 0; })
            ->collect();
        $this->assertEquals(["B" => 4, "D" => 16, "F" => 36], $result);
    }

    public function testFilter()
    {
        $result = IterUtil::from(static::$testArray)
            ->filter(function ($value) { return $value % 2 == 0; })
            ->collect();
        $this->assertEquals(["B" => 2, "D" => 4, "F" => 6], $result);
    }

    public function testFilterOnKey()
    {
        $result = IterUtil::from(static::$testArray)
            ->filter(function ($_, $key) {
                return in_array($key, ["A", "E", "I", "O", "U"]);
            })
            ->collect();
        $this->assertEquals(["A" => 1, "E" => 5], $result);
    }

    public function testFilterOnBoth()
    {
        $result = IterUtil::from(static::$testArray)
            ->filter(function ($value, $key) {
                return ($value % 2 == 0 ||
                        in_array($key, ["A", "E", "I", "O", "U"]));
            })
            ->collect();
        $this->assertEquals(["A" => 1, "B" => 2, "D" => 4, "E" => 5, "F" => 6], $result);
    }

    public function testFlatMap()
    {
        $result = IterUtil::from(static::$testArray)
            ->flatMap(function ($value, $key) {
                return [$value, $key];
            })
            ->collect();
        $this->assertEquals([1, "A", 2, "B", 3, "C", 4, "D", 5, "E", 6, "F", 7, "G"], $result);
    }

    public function testFlatten()
    {
        $result = IterUtil::from(static::$testArray)
            ->toPairs()
            ->flatten()
            ->collect();
        $this->assertEquals(["A", 1, "B", 2, "C", 3, "D", 4, "E", 5, "F", 6, "G", 7], $result);
    }

    public function testFrom()
    {
        $iter = IterUtil::from(static::$testArray);
        $this->assertInstanceOf(IterUtil::class, $iter);
        $this->assertCount(7, $iter);

        $iter = IterUtil::from(new \ArrayIterator(static::$testArray));
        $this->assertInstanceOf(IterUtil::class, $iter);
        $this->assertCount(7, $iter);

        $generator = function () {
            yield "H";
            yield "i";
        };

        $iter = IterUtil::from($generator());
        $this->assertInstanceOf(IterUtil::class, $iter);
        $this->assertCount(2, $iter);
    }

    public function testFromPairs()
    {
        $pairs = [
            0 => ["A", 1],
            1 => ["B", 2],
            2 => ["C", 3],
            3 => ["D", 4],
            4 => ["E", 5],
            5 => ["F", 6],
            6 => ["G", 7]
        ];
        $result = IterUtil::from($pairs)
            ->fromPairs()
            ->collect();
        $this->assertEquals(static::$testArray, $result);
    }

    public function testFromString()
    {
        $iter = IterUtil::fromString("Hello world!");
        $this->assertInstanceOf(IterUtil::class, $iter);
        $this->assertCount(12, $iter);

        $iter = IterUtil::fromString("Hello world!", " ");
        $this->assertInstanceOf(IterUtil::class, $iter);
        $this->assertCount(2, $iter);

        $iter = IterUtil::fromString("Hello world!", "o");
        $this->assertInstanceOf(IterUtil::class, $iter);
        $this->assertCount(3, $iter);

        $iter = IterUtil::fromString("Hello world!", "o ");
        $this->assertInstanceOf(IterUtil::class, $iter);
        $this->assertCount(2, $iter);
    }

    public function testJoin()
    {
        $result = IterUtil::from(static::$testArray)
            ->join();
        $this->assertEquals("1, 2, 3, 4, 5, 6, 7", $result);

        $result = IterUtil::from(static::$testArray)
            ->join(" - ");
        $this->assertEquals("1 - 2 - 3 - 4 - 5 - 6 - 7", $result);
    }

    public function testKeys()
    {
        $result = IterUtil::from(static::$testArray)
            ->keys()
            ->collect();
        $this->assertEquals([0 => "A", 1 => "B", 2 => "C", 3 => "D", 4 => "E", 5 => "F", 6 => "G"], $result);
    }

    public function testMap()
    {
        $result = IterUtil::from(static::$testArray)
            ->map(function ($value) { return $value * $value; })
            ->collect();
        $this->assertEquals(["A" => 1, "B" => 4, "C" => 9, "D" => 16, "E" => 25, "F" => 36, "G" => 49], $result);
    }

    public function testMapKeys()
    {
        $result = IterUtil::from(static::$testArray)
            ->mapKeys(function ($_, $key) { return $key . $key; })
            ->collect();
        $this->assertEquals(["AA" => 1, "BB" => 2, "CC" => 3, "DD" => 4, "EE" => 5, "FF" => 6, "GG" => 7], $result);
    }

    public function testNth()
    {
        $result = IterUtil::from(static::$testArray)
            ->nth(0);
        $this->assertEquals(1, $result);

        $result = IterUtil::from(static::$testArray)
            ->nth(1);
        $this->assertEquals(2, $result);

        $result = IterUtil::from(static::$testArray)
            ->nth(2);
        $this->assertEquals(3, $result);

        $result = IterUtil::from(static::$testArray)
            ->nth(3);
        $this->assertEquals(4, $result);

        $result = IterUtil::from(static::$testArray)
            ->nth(4);
        $this->assertEquals(5, $result);

        $result = IterUtil::from(static::$testArray)
            ->nth(5);
        $this->assertEquals(6, $result);

        $result = IterUtil::from(static::$testArray)
            ->nth(6);
        $this->assertEquals(7, $result);

        $result = IterUtil::from(static::$testArray)
            ->nth(7);
        $this->assertNull($result);
    }

    public function testNthFailsWithNegativeNumber()
    {
        $this->expectException(IterUtilException::class);
        $this->expectExceptionCode(IterUtilException::POSITIVE_INTEGER_REQUIRED);
        $result = IterUtil::from(static::$testArray)
            ->nth(-1);
    }

    public function testNthFailsWithNonNumericString()
    {
        $this->expectException(IterUtilException::class);
        $this->expectExceptionCode(IterUtilException::POSITIVE_INTEGER_REQUIRED);
        $result = IterUtil::from(static::$testArray)
            ->nth("three");
    }

    public function testOddAddition()
    {
        $result = IterUtil::from(static::$testArray)
            ->filter(function ($value) { return $value % 2 != 0; })
            ->map(function ($value) { return $value + 3; })
            ->collect();
        $this->assertEquals(["A" => 4, "C" => 6, "E" => 8, "G" => 10], $result);

        $result = IterUtil::from(static::$testArray)
            ->map(function ($value) { return $value + 3; })
            ->filter(function ($value) { return $value % 2 != 0; })
            ->collect();
        $this->assertEquals(["B" => 5, "D" => 7, "F" => 9], $result);
    }

    public function testPairInversion()
    {
        $result = IterUtil::from(static::$testArray)
            ->toPairs()
            ->fromPairs()
            ->collect();
        $this->assertEquals(static::$testArray, $result);
    }

    public function testPartition()
    {
        list($evens, $odds) = IterUtil::from(static::$testArray)
            ->partition(function ($v) { return $v % 2 == 0; });

        $this->assertEquals(["B" => 2, "D" => 4, "F" => 6], $evens);
        $this->assertEquals(["A" => 1, "C" => 3, "E" => 5, "G" => 7], $odds);

        list($consonants, $vowels) = IterUtil::from(static::$testArray)
            ->partition(function ($_, $k) { return !in_array($k, ["A", "E", "I", "O", "U"]); });

        $this->assertEquals(["B" => 2, "C" => 3, "D" => 4, "F" => 6, "G" => 7], $consonants);
        $this->assertEquals(["A" => 1, "E" => 5], $vowels);
    }

    public function testRange()
    {
        $result = IterUtil::range(0, 4)
            ->collect();
        $this->assertEquals([0 => 0, 1 => 1, 2 => 2, 3 => 3, 4 => 4], $result);

        $result = IterUtil::range(0, 8, 2)
            ->collect();
        $this->assertEquals([0 => 0, 1 => 2, 2 => 4, 3 => 6, 4 => 8], $result);

        $result = IterUtil::range(3, 15, 3)
            ->collect();
        $this->assertEquals([0 => 3, 1 => 6, 2 => 9, 3 => 12, 4 => 15], $result);

        $result = IterUtil::range(4, 0)
            ->collect();
        $this->assertEquals([0 => 4, 1 => 3, 2 => 2, 3 => 1, 4 => 0], $result);

        $result = IterUtil::range(8, 0, -2)
            ->collect();
        $this->assertEquals([0 => 8, 1 => 6, 2 => 4, 3 => 2, 4 => 0], $result);

        $result = IterUtil::range(15, 3, -3)
            ->collect();
        $this->assertEquals([0 => 15, 1 => 12, 2 => 9, 3 => 6, 4 => 3], $result);

        $result = IterUtil::range(15, 3, 3)
            ->collect();
        $this->assertEquals([0 => 15, 1 => 12, 2 => 9, 3 => 6, 4 => 3], $result);

        $result = IterUtil::range(-10, -2, 2)
            ->collect();
        $this->assertEquals([0 => -10, 1 => -8, 2 => -6, 3 => -4, 4 => -2], $result);

        $result = IterUtil::range(-10, -2, -2)
            ->collect();
        $this->assertEquals([0 => -10, 1 => -8, 2 => -6, 3 => -4, 4 => -2], $result);
    }

    public function testRangeFailsOnStringStart()
    {
        $this->expectException(IterUtilException::class);
        $this->expectExceptionCode(IterUtilException::INTEGER_REQUIRED);
        $iter = IterUtil::range("zero");
    }

    public function testRangeFailsOnStringEnd()
    {
        $this->expectException(IterUtilException::class);
        $this->expectExceptionCode(IterUtilException::INTEGER_REQUIRED);
        $iter = IterUtil::range(0, "four");
    }

    public function testRangeFailsOnStringStep()
    {
        $this->expectException(IterUtilException::class);
        $this->expectExceptionCode(IterUtilException::NON_ZERO_INTEGER_REQUIRED);
        $iter = IterUtil::range(0, 4, "one");
    }

    public function testRangeFailsOnZeroStep()
    {
        $this->expectException(IterUtilException::class);
        $this->expectExceptionCode(IterUtilException::NON_ZERO_INTEGER_REQUIRED);
        $iter = IterUtil::range(0, 4, 0);
    }

    public function testReduce()
    {
        $result = IterUtil::from(static::$testArray)
            ->reduce(function ($acc, $value) { return $acc + $value; }, 0);
        $this->assertEquals(28, $result);
    }

    public function testReduceOnKey()
    {
        $result = IterUtil::from(static::$testArray)
            ->reduce(function ($acc, $_, $key) { return $acc . $key; }, "");
        $this->assertEquals("ABCDEFG", $result);
    }

    public function testReduceOnBoth()
    {
        $result = IterUtil::from(static::$testArray)
            ->reduce(function ($acc, $value, $key) {
                return $acc . $key . " => " . $value . ", ";
            }, "");
        $this->assertEquals("A => 1, B => 2, C => 3, D => 4, E => 5, F => 6, G => 7, ", $result);
    }

    public function testReduceWithInitial()
    {
        $result = IterUtil::from(static::$testArray)
            ->reduce(function ($acc, $value) { return $acc + $value; }, 5);
        $this->assertEquals(33, $result);
    }

    public function testRepeat()
    {
        $result = IterUtil::repeat(0, 5)
            ->collect();
        $this->assertEquals([0, 0, 0, 0, 0], $result);

        $result = IterUtil::repeat(0)
            ->take(5)
            ->collect();
        $this->assertEquals([0, 0, 0, 0, 0], $result);
    }

    public function testRepeatFailsOnNegativeN()
    {
        $this->expectException(IterUtilException::class);
        $this->expectExceptionCode(IterUtilException::POSITIVE_INTEGER_REQUIRED);
        $iter = IterUtil::repeat(0, -1);
    }

    public function testRepeatFailsOnStringN()
    {
        $this->expectException(IterUtilException::class);
        $this->expectExceptionCode(IterUtilException::POSITIVE_INTEGER_REQUIRED);
        $iter = IterUtil::repeat(0, "three");
    }

    public function testSkip()
    {
        $result = IterUtil::from(static::$testArray)
            ->skip(0)
            ->collect();
        $this->assertEquals(static::$testArray, $result);

        $result = IterUtil::from(static::$testArray)
            ->skip(3)
            ->collect();
        $this->assertEquals(["D" => 4, "E" => 5, "F" => 6, "G" => 7], $result);

        $result = IterUtil::from(static::$testArray)
            ->skip(7)
            ->collect();
        $this->assertCount(0, $result);

        $result = IterUtil::from(static::$testArray)
            ->skip(20)
            ->collect();
        $this->assertCount(0, $result);
    }

    public function testSkipFailsOnNegativeN()
    {
        $this->expectException(IterUtilException::class);
        $this->expectExceptionCode(IterUtilException::POSITIVE_INTEGER_REQUIRED);
        $iter = IterUtil::from(static::$testArray)
            ->skip(-1);
    }

    public function testSkipFailsOnStringN()
    {
        $this->expectException(IterUtilException::class);
        $this->expectExceptionCode(IterUtilException::POSITIVE_INTEGER_REQUIRED);
        $iter = IterUtil::from(static::$testArray)
            ->skip("three");
    }

    public function testSkipWhile()
    {
        $result = IterUtil::from(static::$testArray)
            ->skipWhile(function ($value) { return $value % 2 == 0; })
            ->collect();
        $this->assertEquals(static::$testArray, $result);

        $result = IterUtil::from(static::$testArray)
            ->skipWhile(function ($value) { return $value < 5; })
            ->collect();
        $this->assertEquals(["E" => 5, "F" => 6, "G" => 7], $result);

        $result = IterUtil::from(static::$testArray)
            ->skipWhile(function ($_, $key) { return in_array($key, ["A", "B"]); })
            ->collect();
        $this->assertEquals(["C" => 3, "D" => 4, "E" => 5, "F" => 6, "G" => 7], $result);
    }

    public function testStepBy()
    {
        $result = IterUtil::from(static::$testArray)
            ->stepBy(1)
            ->collect();
        $this->assertEquals(static::$testArray, $result);

        $result = IterUtil::from(static::$testArray)
            ->stepBy(2)
            ->collect();
        $this->assertEquals(["A" => 1, "C" => 3, "E" => 5, "G" => 7], $result);

        $result = IterUtil::from(static::$testArray)
            ->stepBy(3)
            ->collect();
        $this->assertEquals(["A" => 1, "D" => 4, "G" => 7], $result);

        $result = IterUtil::from(static::$testArray)
            ->stepBy(4)
            ->collect();
        $this->assertEquals(["A" => 1, "E" => 5], $result);

        $result = IterUtil::from(static::$testArray)
            ->stepBy(7)
            ->collect();
        $this->assertEquals(["A" => 1], $result);
    }

    public function testStepByFailsOnZeroN()
    {
        $this->expectException(IterUtilException::class);
        $this->expectExceptionCode(IterUtilException::NON_ZERO_POSITIVE_INTEGER_REQUIRED);
        $iter = IterUtil::from(static::$testArray)
            ->stepBy(0);
    }

    public function testStepByFailsOnNegativeN()
    {
        $this->expectException(IterUtilException::class);
        $this->expectExceptionCode(IterUtilException::NON_ZERO_POSITIVE_INTEGER_REQUIRED);
        $iter = IterUtil::from(static::$testArray)
            ->stepBy(-1);
    }

    public function testStepByFailsOnStringN()
    {
        $this->expectException(IterUtilException::class);
        $this->expectExceptionCode(IterUtilException::NON_ZERO_POSITIVE_INTEGER_REQUIRED);
        $iter = IterUtil::from(static::$testArray)
            ->stepBy("three");
    }

    public function testTake()
    {
        $result = IterUtil::from(static::$testArray)
            ->take(0)
            ->collect();
        $this->assertCount(0, $result);

        $result = IterUtil::from(static::$testArray)
            ->take(3)
            ->collect();
        $this->assertEquals(["A" => 1, "B" => 2, "C" => 3], $result);

        $result = IterUtil::from(static::$testArray)
            ->take(7)
            ->collect();
        $this->assertEquals(static::$testArray, $result);

        $result = IterUtil::from(static::$testArray)
            ->take(20)
            ->collect();
        $this->assertEquals(static::$testArray, $result);
    }

    public function testTakeFailsOnNegativeN()
    {
        $this->expectException(IterUtilException::class);
        $this->expectExceptionCode(IterUtilException::POSITIVE_INTEGER_REQUIRED);
        $iter = IterUtil::from(static::$testArray)
            ->take(-1);
    }

    public function testTakeFailsOnStringN()
    {
        $this->expectException(IterUtilException::class);
        $this->expectExceptionCode(IterUtilException::POSITIVE_INTEGER_REQUIRED);
        $iter = IterUtil::from(static::$testArray)
            ->take("three");
    }

    public function testTakeWhile()
    {
        $result = IterUtil::from(static::$testArray)
            ->takeWhile(function ($value) { return $value % 2 == 0; })
            ->collect();
        $this->assertCount(0, $result);

        $result = IterUtil::from(static::$testArray)
            ->takeWhile(function ($value) { return $value < 5; })
            ->collect();
        $this->assertEquals(["A" => 1, "B" => 2, "C" => 3, "D" => 4], $result);

        $result = IterUtil::from(static::$testArray)
            ->takeWhile(function ($_, $key) { return in_array($key, ["A", "B"]); })
            ->collect();
        $this->assertEquals(["A" => 1, "B" => 2], $result);
    }

    public function testTakeSkipNthKeys()
    {
        $result = IterUtil::from(static::$testArray)
            ->keys()
            ->take(4)
            ->collect();
        $this->assertEquals([0 => "A", 1 => "B", 2 => "C", 3 => "D"], $result);

        $result = IterUtil::from(static::$testArray)
            ->keys()
            ->skip(4)
            ->collect();
        $this->assertEquals([4 => "E", 5 => "F", 6 => "G"], $result);

        $result = IterUtil::from(static::$testArray)
            ->keys()
            ->nth(4);
        $this->assertEquals("E", $result);
    }

    public function testToPairs()
    {
        $result = IterUtil::from(static::$testArray)
            ->toPairs()
            ->collect();
        $this->assertEquals([
            0 => ["A", 1],
            1 => ["B", 2],
            2 => ["C", 3],
            3 => ["D", 4],
            4 => ["E", 5],
            5 => ["F", 6],
            6 => ["G", 7]
        ], $result);
    }

    public function testTruncatedRanges()
    {
        $result = IterUtil::range()
            ->take(5)
            ->collect();
        $this->assertEquals([0 => 0, 1 => 1, 2 => 2, 3 => 3, 4 => 4], $result);

        $result = IterUtil::range(0, PHP_INT_MAX, 2)
            ->take(5)
            ->collect();
        $this->assertEquals([0 => 0, 1 => 2, 2 => 4, 3 => 6, 4 => 8], $result);
    }

    public function testValues()
    {
        $result = IterUtil::from(static::$testArray)
            ->values()
            ->collect();
        $this->assertEquals([0 => 1, 1 => 2, 2 => 3, 3 => 4, 4 => 5, 5 => 6, 6 => 7], $result);
    }
}
