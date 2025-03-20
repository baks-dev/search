<?php

namespace BaksDev\Search\RediSearch\Aggregate\Reducers;

use BaksDev\Search\RediSearch\CanBecomeArrayInterface;

abstract class AbstractFieldNameReducer implements CanBecomeArrayInterface
{
    use Aliasable;

    public $fieldName;
    protected $reducerKeyword;

    public function __construct(string $fieldName, string $alias = '')
    {
        $this->fieldName = $fieldName;
        $this->alias = $alias;
    }

    public function toArray(): array
    {
        return [];
    }

    protected function makeAlias(): string
    {
        return empty($alias) ? strtolower($this->reducerKeyword) . "_" . $this->fieldName : $this->alias;
    }
}
