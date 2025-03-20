<?php

namespace BaksDev\Search\RediSearch\Aggregate\Reducers;

class StandardDeviation extends AbstractFieldNameReducer
{
    protected $reducerKeyword = 'STDDEV';

    public function toArray(): array
    {
        return ['REDUCE', $this->reducerKeyword, '1', $this->fieldName, 'AS', $this->makeAlias()];
    }
}
