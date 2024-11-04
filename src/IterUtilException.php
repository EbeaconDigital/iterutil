<?php

namespace ebeacon\iterutil;

class IterUtilException extends \RuntimeException
{
    const ITERABLE_REQUIRED = 1;
    const INTEGER_REQUIRED = 2;
    const NON_ZERO_INTEGER_REQUIRED = 3;
    const POSITIVE_INTEGER_REQUIRED = 4;
    const NON_ZERO_POSITIVE_INTEGER_REQUIRED = 5;
    const STRING_REQUIRED = 6;
    const COLLECTION_CLASS_DOES_NOT_EXIST = 7;
    const COLLECTION_CLASS_MUST_IMPLEMENT_ARRAY_ACCESS = 8;
}
