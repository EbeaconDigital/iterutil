# IterUtil

IterUtil is an iterator builder for PHP that enables you to apply a variety of
transformations on the data held in any `iterable` without modifying the
original's contents. All "non-consuming" methods are lazily evaluated, saving
memory usage as well as CPU time. It is mostly inspired by Rust's [Iterator
trait](https://doc.rust-lang.org/std/iter/trait.Iterator.html), with some
alterations made for PHP coding conventions and its somewhat unique situation
with associative arrays.

At present, it supports PHP all the way back through 5.6, though 7.4+ is
certainly recommended if only to have access to the arrow function syntax!

## General Constructor

    $fruits = ["apples", "bananas", "oranges"];
    $iter = IterUtil::from($fruits);

`IterUtil::from` creates a new `IterUtil` instance from any array, or any object
that implements `Traversable`. At this point, it is effectively just an iterator
wrapping the iterator you provided. No other work is performed, but now you can
apply transformations.

## Transformations

    $vowels = ["A", "E", "I", "O", "U"];
    $fruits = ["Apples", "Bananas", "oranges"];
    $iter = IterUtil::from($fruits)
        ->map(fn($fruit) => ucfirst($fruit))
        ->filter(fn($fruit) => in_array(substr($fruit, 0, 1), $vowels))
        ->keys();

Transformations are both lazy and chainable. All that we've done is modify the
iterator itself. `$fruits` is unchanged and we haven't even begun to iterate
yet.

## Consumers

Other methods will consume the iterator, perform any transformations in the
order in which they were applied, and produce some sort of result. One of these,
`collect()`, produces an array:

    $vowels = ["A", "E", "I", "O", "U"];
    $fruits = ["Apples", "Bananas", "oranges"];
    $result = IterUtil::from($fruits)
        ->map(fn($fruit) => ucfirst($fruit))
        ->filter(fn($fruit) => in_array(substr($fruit, 0, 1), $vowels))
        ->keys()
        ->collect();

`$result` is now equal to `[0, 2]`, which are the keys of the fruits that begin
with vowels. The original list in `$fruits` is actually only iterated over a
single time, and is the equivalent of writing:

    $vowels = ["A", "E", "I", "O", "U"];
    $fruits = ["Apples", "Bananas", "oranges"];
    $result = [];
    foreach ($fruits as $key => $fruit) {
        if (in_array(substr(ucfirst($fruit), 0, 1), $vowels)) {
            $result[] = $key;
        }
    }

`reduce()` is another common example:

    $cart = [
        ["name" => "Apples", "qty" => 2, "price" => .30],
        ["name" => "Bananas", "qty" => 1, "price" => 10.00],
        ["name" => "Oranges", "qty" => 3, "price" => .45],
    ];
    $subtotal = IterUtil::from($cart)
        ->reduce(fn($acc, $item) => $acc + ($item["qty"] * $item["price"]), 0);

## Specialized Constructors

`IterUtil::fromString($str, $delimiter = "")` will allow you to iterate over a
string, split on the delimiter. (If the delimiter is an empty string or not
provided, it will split the string into individual characters.)

    $hello = "Hello, World!";
    $words = IterUtil::fromString($hello, " ")
        ->collect();
    // $words is now equal to ["Hello,", "World!"]

`IterUtil::range($start = 0, $end = PHP_INT_MAX, $step = 1)` can generate an
(inclusive) range of numbers.

    $numbers = IterUtil::range(0, 7)
        ->collect();
    // $numbers is now equal to [0, 1, 2, 3, 4, 5, 6, 7]

`IterUtil::repeat($value, $n = INF)` will repeat the same value over and over
again.

    $aBunchOfZeroes = IterUtil::repeat(0, 8)
        ->collect();
    // $aBunchOfZeroes is now equal to [0, 0, 0, 0, 0, 0, 0, 0]
