<?php declare(strict_types=1);

final class MyClass
{
    public function foreachToInArray(): bool
    {
        return in_array('something', $items, true);
    }
}
