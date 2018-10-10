<?php declare(strict_types=1);

namespace Rector\Php\Tests\Rector\FuncCall\CountOnNullRector\Source;

final class CountableClass implements \Countable
{
    public function count()
    {
        return 0;
    }
}
