<?php

namespace BaksDev\Search\RediSearch;

interface CanBecomeArrayInterface
{
    public function toArray(): array;
}
