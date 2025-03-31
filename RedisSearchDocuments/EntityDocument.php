<?php
/*
 *  Copyright 2025.  Baks.dev <admin@baks.dev>
 *
 *  Permission is hereby granted, free of charge, to any person obtaining a copy
 *  of this software and associated documentation files (the "Software"), to deal
 *  in the Software without restriction, including without limitation the rights
 *  to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 *  copies of the Software, and to permit persons to whom the Software is furnished
 *  to do so, subject to the following conditions:
 *
 *  The above copyright notice and this permission notice shall be included in all
 *  copies or substantial portions of the Software.
 *
 *  THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 *  IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 *  FITNESS FOR A PARTICULAR PURPOSE AND NON INFRINGEMENT. IN NO EVENT SHALL THE
 *  AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 *  LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 *  OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 *  THE SOFTWARE.
 */

namespace BaksDev\Search\RedisSearchDocuments;

use BaksDev\Search\RediSearch\Document\Document;
use BaksDev\Search\RediSearch\Fields\TagField;
use BaksDev\Search\RediSearch\Fields\TextField;

/**
 * @property TextField entity_index
 * @property TagField search_tag
 */
class EntityDocument extends Document
{

    protected TextField $entity_index;
    protected TagField $search_tag;
    protected $id;

    /**
     * @return mixed
     */
    public function getId(): string
    {
        return $this->id;
    }

    public function getEntityIndex(): TextField
    {
        return $this->entity_index;
    }

    public function setEntityIndex(string $entity_index): self
    {
        $this->entity_index = new TextField('entity_index', $entity_index);

        return $this;
    }

    public function getSearchTag(): TagField
    {
        return $this->search_tag;
    }

    public function setSearchTag(string $search_tag): self
    {
        $this->search_tag = new TagField('search_tag', $search_tag);

        return $this;
    }

    public function __construct(?string $id = null)
    {
        parent::__construct($id);
    }
}