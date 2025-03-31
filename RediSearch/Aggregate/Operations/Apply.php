<?php

namespace BaksDev\Search\RediSearch\Aggregate\Operations;

use BaksDev\Search\RediSearch\CanBecomeArrayInterface;

class Apply implements CanBecomeArrayInterface
{
    public $expression;
    public $asFieldName;

    public function __construct(string $expression, string $asFieldName)
    {
        $this->expression = $expression;
        $this->asFieldName = $asFieldName;
    }

    public function toArray(): array
    {
        return ['APPLY', $this->expression, 'AS', $this->asFieldName];
    }
}
