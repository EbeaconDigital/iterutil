<?php

namespace ebeacon\iterutil;

/**
 * An iterator with chainable, lazily evaluated transformations.
 */
class IterUtil implements \IteratorAggregate
{
    private $iter;

    /**
     * IterUtil constructor.
     *
     * @param iterable $iterable
     */
    private function __construct($iterable)
    {
        if (is_array($iterable)) {
            $this->iter = new \ArrayIterator($iterable);
        } else {
            $this->iter = $iterable;
        }
    }

    /**
     * Construct a new IterUtil around some iterable data.
     *
     * @param iterable $iterable
     * @return IterUtil
     */
    public static function from($iterable)
    {
        if (!is_array($iterable) && (!is_object($iterable) || !is_a($iterable, "Traversable"))) {
            $message = "IterUtil::from expects an array or Traversable object";
            $code = IterUtilException::ITERABLE_REQUIRED;
            throw new IterUtilException($message, $code);
        }
        return new static($iterable);
    }

    /**
     * Construct a new IterUtil around a string. By default, iteration will be
     * over individual characters. If a delimiter is provided, it will split on
     * the delimiter.
     *
     * @param string $str
     * @param string $delimiter Optional. If an empty string (or omitted), $str will be split into individual characters.
     * @return IterUtil
     */
    public static function fromString($str, $delimiter = "")
    {
        if (!is_string($str)) {
            $message = "IterUtil::fromString expects a string";
            $code = IterUtilException::STRING_REQUIRED;
            throw new IterUtilException($message, $code);
        }

        if (!is_string($delimiter)) {
            $message = "IterUtil::fromString expects a string delimiter";
            $code = IterUtilException::STRING_REQUIRED;
            throw new IterUtilException($message, $code);
        }

        switch ($delimiter) {
            case "":
                $asArray = preg_split("~~u", $str, -1, PREG_SPLIT_NO_EMPTY);
                break;
            default:
                $asArray = explode($delimiter, $str);
        }
        return new static($asArray);
    }

    /**
     * Construct a new IterUtil for an inclusive range of numbers.
     *
     * @param int $start
     * @param int $end
     * @param int $step
     * @return IterUtil
     */
    public static function range($start = 0, $end = PHP_INT_MAX, $step = 1)
    {
        if (!is_int($start)) {
            $message = "range requires an integer for a start value";
            $code = IterUtilException::INTEGER_REQUIRED;
            throw new IterUtilException($message, $code);
        }

        if (!is_int($end)) {
            $message = "range requires an integer for an end value";
            $code = IterUtilException::INTEGER_REQUIRED;
            throw new IterUtilException($message, $code);
        }

        if (!is_int($step) || $step == 0) {
            $message = "range requires a non-zero integer for a step value";
            $code = IterUtilException::NON_ZERO_INTEGER_REQUIRED;
            throw new IterUtilException($message, $code);
        }

        $step = abs($step);
        if ($start > $end) {
            $step *= -1;
        }

        $_range = function () use ($start, $end, $step) {
            if ($start > $end) {
                for ($i = $start; $i >= $end; $i += $step) {
                    yield $i;
                }
            } else {
                for ($i = $start; $i <= $end; $i += $step) {
                    yield $i;
                }
            }
        };

        return new static($_range());
    }

    /**
     * Construct a new IterUtil that yields the same value multiple times.
     *
     * @param mixed $value The value to repeat.
     * @param int $n The number of repetitions. Defaults to infinite.
     * @return IterUtil
     */
    public static function repeat($value, $n = INF)
    {
        if ($n != INF && ((!is_int($n) && !ctype_digit($n)) || $n < 0)) {
            $message = "repeat requires an integer greater than or equal to zero";
            $code = IterUtilException::POSITIVE_INTEGER_REQUIRED;
            throw new IterUtilException($message, $code);
        }

        $_repeat = function () use ($value, $n) {
            for ($i = 0; $i < $n; $i++) {
                yield $value;
            }
        };

        return new static($_repeat());
    }

    /**
     * Return true if every element in the iterator is true (or the iterator is
     * empty), consuming the iterator.
     *
     * @return bool
     */
    public function all()
    {
        foreach ($this->iter as $value) {
            if ($value !== true) {
                return false;
            }
        }
        return true;
    }

    /**
     * Return true if any element in the iterator is true, consuming the
     * iterator. If the iterator is empty, false is returned.
     *
     * @return bool
     */
    public function any()
    {
        foreach ($this->iter as $value) {
            if ($value === true) {
                return true;
            }
        }
        return false;
    }

    /**
     * Modifies the iterator, appending any passed in iterables in sequence.
     *
     * As a result, any existing keys are discarded.
     *
     * @param iterable $iterable,...
     * @return IterUtil
     */
    public function chain()
    {
        $iterables = func_get_args();
        if (count($iterables) > 0) {
            foreach ($iterables as $arg) {
                if (!is_array($arg) && (!is_object($arg) || !is_a($arg, "Traversable"))) {
                    $message = "chain expects arrays or Traversable objects";
                    $code = IterUtilException::ITERABLE_REQUIRED;
                    throw new IterUtilException($message, $code);
                }
            }

            $_chain = function ($iter) use ($iterables) {
                foreach ($iter as $value) {
                    yield $value;
                }

                foreach ($iterables as $iterable) {
                    foreach ($iterable as $value) {
                        yield $value;
                    }
                }
            };

            $this->iter = $_chain($this->iter);
        }

        return $this;
    }

    /**
     * Modify the iterator, yielding the values in array "chunks" of the
     * specified size.
     *
     * @param int $n
     * @return IterUtil
     */
    public function chunk($n)
    {
        if ((!is_int($n) && !ctype_digit($n)) || $n <= 0) {
            $message = "chunk requires an integer greater than zero";
            $code = IterUtilException::NON_ZERO_POSITIVE_INTEGER_REQUIRED;
            throw new IterUtilException($message, $code);
        }

        $_chunk = function ($iter) use ($n) {
            $i = 0;
            $current = [];
            foreach ($iter as $value) {
                $current[] = $value;
                $i++;
                if ($i === $n) {
                    yield $current;
                    $i = 0;
                    $current = [];
                }
            }
            if ($i !== 0) {
                yield $current;
            }
        };

        $this->iter = $_chunk($this->iter);

        return $this;
    }

    /**
     * Return the contents of the iterator as a new collection. Both keys and
     * values are maintained.
     *
     * @template T of \ArrayAccess
     * @param class-string<T>|null $collectionClass The name of the collection class to use. The class *must* implement the ArrayAccess interface. If null, an array will be used instead.
     * @param mixed ...$constructorParams Any extra parameters passed into this method will be handed off to the collection class during object construction.
     * @return T|array
     */
    public function collect($collectionClass = null, ...$constructorParams)
    {
        if ($collectionClass !== null) {
            $interfaces = class_implements($collectionClass);
            if ($interfaces === false) {
                $message = "collection class does not exist";
                throw new IterUtilException(
                    $message,
                    IterUtilException::COLLECTION_CLASS_DOES_NOT_EXIST
                );
            }
            if (!in_array("ArrayAccess", $interfaces)) {
                $message = "collection class must implement ArrayAccess";
                throw new IterUtilException(
                    $message,
                    IterUtilException::COLLECTION_CLASS_MUST_IMPLEMENT_ARRAY_ACCESS
                );
            }
            $collection = new $collectionClass(...$constructorParams);
        } else {
            $collection = [];
        }
        foreach ($this->iter as $key => $value) {
            $collection[$key] = $value;
        }
        return $collection;
    }

    /**
     * Return the number of elements in the iterator, consuming the iterator.
     *
     * @return int
     */
    public function count()
    {
        return iterator_count($this->iter);
    }

    /**
     * Modify the iterator, skipping over any value that does not return true
     * for the filter function.
     *
     * @param callable $fn ( mixed $value, [ mixed $key ] ) : bool
     * @return IterUtil
     */
    public function filter(callable $fn)
    {
        $_filter = function ($iter) use ($fn) {
            foreach ($iter as $key => $value) {
                if ($fn($value, $key) === true) {
                    yield $key => $value;
                }
            }
        };

        $this->iter = $_filter($this->iter);
        return $this;
    }

    /**
     * Modify the iterator, applying a callback function (that itself returns
     * something iterable) to each value and subsequently flattens the result
     * by one level.
     *
     * As a result, any existing keys are discarded.
     *
     * This is the equivalent of `IterUtil::from(...)->map(...)->flatten()`.
     *
     * @param callable $fn ( mixed $value, [ mixed $key ] ) : iterable
     * @return IterUtil
     */
    public function flatMap(callable $fn)
    {
        $_flatMap = function ($iter) use ($fn) {
            foreach ($iter as $key => $value) {
                foreach ($fn($value, $key) as $v) {
                    yield $v;
                }
            }
        };

        $this->iter = $_flatMap($this->iter);
        return $this;
    }

    /**
     * Modify the iterator, flattening nested structure by one level.
     *
     * As a result, any existing keys are discarded.
     *
     * @return IterUtil
     */
    public function flatten()
    {
        $_flatten = function ($iter) {
            foreach ($iter as $value) {
                if (is_array($value) || (is_object($value) && is_a($value, "Traversable"))) {
                    foreach ($value as $v) {
                        yield $v;
                    }
                } else {
                    yield $value;
                }
            }
        };

        $this->iter = $_flatten($this->iter);
        return $this;
    }

    /**
     * Modify the iterator, destructuring two-element array values into new
     * keys and values.
     *
     * @return IterUtil
     */
    public function fromPairs()
    {
        $_fromPairs = function ($iter) {
            foreach ($iter as list($key, $value)) {
                yield $key => $value;
            }
        };

        $this->iter = $_fromPairs($this->iter);
        return $this;
    }

    /**
     * Enable iteration over our inner iterator.
     *
     * @return \IteratorIterator|\Traversable
     */
    public function getIterator()
    {
        return $this->iter;
    }

    /**
     * Return a string containing all values in the iterator, separated by a
     * delimiter. Consumes the iterator.
     *
     * @param string $delimiter
     * @return string
     */
    public function join($delimiter = ", ")
    {
        $output = "";
        $firstIteration = true;

        foreach ($this->iter as $value) {
            if ($firstIteration) {
                $output .= $value;
                $firstIteration = false;
            } else {
                $output .= $delimiter . $value;
            }
        }

        return $output;
    }

    /**
     * Modify the iterator, returning only keys.
     *
     * @return IterUtil
     */
    public function keys()
    {
        $_keys = function ($iter) {
            foreach ($iter as $key => $_) {
                yield $key;
            }
        };

        $this->iter = $_keys($this->iter);
        return $this;
    }

    /**
     * Modify the iterator, applying the function to each value.
     *
     * @param callable $fn ( mixed $value, [ mixed $key ] ) : mixed
     * @return IterUtil
     */
    public function map(callable $fn)
    {
        $_map = function ($iter) use ($fn) {
            foreach ($iter as $key => $value) {
                yield $key => $fn($value, $key);
            }
        };

        $this->iter = $_map($this->iter);
        return $this;
    }

    /**
     * Modify the iterator, applying the function to each key.
     *
     * @param callable $fn ( mixed $value, [ mixed $key ] ) : mixed
     * @return IterUtil
     */
    public function mapKeys(callable $fn)
    {
        $_mapKeys = function ($iter) use ($fn) {
            foreach ($iter as $key => $value) {
                yield $fn($value, $key) => $value;
            }
        };

        $this->iter = $_mapKeys($this->iter);
        return $this;
    }

    /**
     * Return the nth element of the iterator, zero-indexed. Null is returned
     * if n is greater than or equal to the length of the iterator.
     *
     * @param int $n
     * @return mixed|null
     */
    public function nth($n)
    {
        if ((!is_int($n) && !ctype_digit($n)) || $n < 0) {
            $message = "nth requires an integer greater than or equal to zero";
            $code = IterUtilException::POSITIVE_INTEGER_REQUIRED;
            throw new IterUtilException($message, $code);
        }

        $count = 0;
        foreach ($this->iter as $value) {
            if ($count != $n) {
                $count++;
                continue;
            }
            return $value;
        }
        return null;
    }

    /**
     * Return two arrays, one containing elements for which the function
     * returns true and the other containing elements for which the function
     * returns false. Consumes the iterator.
     *
     * @param callable $fn ( mixed $value, [ mixed $key ] ) : bool
     * @return array
     */
    public function partition(callable $fn)
    {
        $trueElements = [];
        $falseElements = [];
        foreach ($this->iter as $key => $value) {
            if ($fn($value, $key) === true) {
                $trueElements[$key] = $value;
            } else {
                $falseElements[$key] = $value;
            }
        }

        return [$trueElements, $falseElements];
    }

    /**
     * Reduces an iterator to a single value, consuming the iterator.
     *
     * @param callable $fn callback ( mixed $accumulator , mixed $value, [ mixed $key ] ) : mixed
     * @param mixed $initial The initial value.
     * @return mixed
     */
    public function reduce(callable $fn, $initial = null)
    {
        $accumulator = $initial;
        foreach ($this->iter as $key => $value) {
            $accumulator = $fn($accumulator, $value, $key);
        }
        return $accumulator;
    }

    /**
     * Modify the iterator, skipping the first n elements.
     *
     * @param int $n
     * @return IterUtil
     */
    public function skip($n)
    {
        if ((!is_int($n) && !ctype_digit($n)) || $n < 0) {
            $message = "skip requires an integer greater than or equal to zero";
            $code = IterUtilException::POSITIVE_INTEGER_REQUIRED;
            throw new IterUtilException($message, $code);
        }

        $_skip = function ($iter) use ($n) {
            $count = 0;
            foreach ($iter as $key => $value) {
                if ($count < $n) {
                    $count++;
                    continue;
                }
                yield $key => $value;
                $count++;
            }
        };

        $this->iter = $_skip($this->iter);
        return $this;
    }

    /**
     * Modify the iterator, skipping over any value that returns true for the
     * predicate function until the predicate function fails for the first
     * time. That value--and any value thereafter--will be yielded.
     *
     * @param callable $fn callback ( mixed $value, [ mixed $key ] ) : bool
     * @return IterUtil
     */
    public function skipWhile(callable $fn)
    {
        $_skipWhile = function ($iter) use ($fn) {
            $keepSkipping = true;
            foreach ($iter as $key => $value) {
                if ($keepSkipping) {
                    if ($fn($value, $key) === true) {
                        continue;
                    } else {
                        $keepSkipping = false;
                    }
                }

                yield $key => $value;
            }
        };

        $this->iter = $_skipWhile($this->iter);
        return $this;
    }

    /**
     * Modify the iterator, yielding the first value and every nth value
     * thereafter.
     *
     * @param int $n
     * @return IterUtil
     */
    public function stepBy($n)
    {
        if ((!is_int($n) && !ctype_digit($n)) || $n <= 0) {
            $message = "stepBy requires an integer greater than zero";
            $code = IterUtilException::NON_ZERO_POSITIVE_INTEGER_REQUIRED;
            throw new IterUtilException($message, $code);
        }

        if ($n > 1) {
            $_stepBy = function ($iter) use ($n) {
                $count = $n;
                foreach ($iter as $key => $value) {
                    if ($count == $n) {
                        yield $key => $value;
                    }

                    $count = ($count == 1) ? $n : $count - 1;
                }
            };

            $this->iter = $_stepBy($this->iter);
        }

        return $this;
    }

    /**
     * Modify the iterator, taking only the first n elements.
     *
     * @param int $n
     * @return IterUtil
     */
    public function take($n)
    {
        if ((!is_int($n) && !ctype_digit($n)) || $n < 0) {
            $message = "take requires an integer greater than or equal to zero";
            $code = IterUtilException::POSITIVE_INTEGER_REQUIRED;
            throw new IterUtilException($message, $code);
        }

        $_take = function ($iter) use ($n) {
            $count = 0;
            foreach ($iter as $key => $value) {
                if ($count >= $n) {
                    break;
                }
                yield $key => $value;
                $count++;
            }
        };

        $this->iter = $_take($this->iter);
        return $this;
    }

    /**
     * Modify the iterator, taking only the values that return true for the
     * predicate function until the predicate function fails for the first
     * time. That value--and any value thereafter--will not be yielded.
     *
     * @param callable $fn callback ( mixed $value, [ mixed $key ] ) : bool
     * @return IterUtil
     */
    public function takeWhile(callable $fn)
    {
        $_takeWhile = function ($iter) use ($fn) {
            foreach ($iter as $key => $value) {
                if (!$fn($value, $key)) {
                    break;
                }

                yield $key => $value;
            }
        };

        $this->iter = $_takeWhile($this->iter);
        return $this;
    }

    /**
     * Modify the iterator, changing the yielded value to an array containing
     * both the original key and value, and introducing new (sequential) keys.
     *
     * @return IterUtil
     */
    public function toPairs()
    {
        $_toPairs = function ($iter) {
            foreach ($iter as $key => $value) {
                yield [$key, $value];
            }
        };

        $this->iter = $_toPairs($this->iter);
        return $this;
    }

    /**
     * Modify the iterator, returning only values.
     *
     * @return IterUtil
     */
    public function values()
    {
        $_values = function ($iter) {
            foreach ($iter as $_ => $value) {
                yield $value;
            }
        };

        $this->iter = $_values($this->iter);
        return $this;
    }
}
