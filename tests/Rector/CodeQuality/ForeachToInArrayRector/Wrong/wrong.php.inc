<?php declare(strict_types=1);

final class MyClass
{
    public function foreachToInArray(): bool
    {
        foreach ($items as $item) {
            if ($item === 'something') {
                return true;
            }
        }

        return false;
    }
}
