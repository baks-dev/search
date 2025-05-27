<?php

namespace BaksDev\Search\Repository\RedisToIndexResult;

use BaksDev\Core\Services\Switcher\Switcher;

class RedisToIndexResultRepository implements RedisToIndexResultInterface
{

    public function getTransformedValue(Switcher $switcher): string
    {
        return '';
    }
}