<?php

namespace BaksDev\Search\RediSearch\Aggregate\Operations;

use BaksDev\Search\RediSearch\CanBecomeArrayInterface;

class Filter implements CanBecomeArrayInterface
{
    public $expression;

    public function __construct(string $expression)
    {
        $this->expression = $expression;
    }

    public function toArray(): array
    {
        return ['FILTER', $this->expression];
    }
}
