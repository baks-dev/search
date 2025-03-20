<?php

namespace BaksDev\Search\RediSearch\Aggregate\Reducers;

class Min extends AbstractFieldNameReducer
{
    protected $reducerKeyword = 'MIN';

    public function toArray(): array
    {
        return ['REDUCE', $this->reducerKeyword, '1', $this->fieldName, 'AS', $this->makeAlias()];
    }
}
