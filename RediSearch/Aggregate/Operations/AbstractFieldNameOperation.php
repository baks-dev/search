<?php

namespace BaksDev\Search\RediSearch\Aggregate\Operations;

use BaksDev\Search\RediSearch\CanBecomeArrayInterface;

abstract class AbstractFieldNameOperation implements CanBecomeArrayInterface
{
    protected $operationName;
    protected $fieldNames;

    public function __construct(string $operationName, array $fieldNames)
    {
        $this->fieldNames = $fieldNames;
        $this->operationName = $operationName;
    }

    public function toArray(): array
    {
        return array_merge(
            [$this->operationName, count($this->fieldNames)],
            array_map(function ($fieldName) {
                return "@$fieldName";
            }, $this->fieldNames)
        );
    }
}
