<?php

namespace BaksDev\Search\RediSearch\Aggregate\Reducers;

use BaksDev\Search\RediSearch\CanBecomeArrayInterface;

class Count implements CanBecomeArrayInterface
{
    use Aliasable;

    private $group;
    protected $reducerKeyword = 'COUNT';

    public function __construct(int $group)
    {
        $this->group = $group;
    }

    public function toArray(): array
    {
        return ['REDUCE', $this->reducerKeyword, $this->group, 'AS', empty($this->alias) ? 'count' : $this->alias];
    }
}
