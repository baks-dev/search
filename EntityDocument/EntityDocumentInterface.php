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

namespace BaksDev\Search\EntityDocument;

/**
 * Используется для определения класса - Документа для передачи в индекс
 */
interface EntityDocumentInterface
{
    /**
     *  Идентификатор сущности (UUID) для поиска результата по базе
     */
    public function getId(): string;

    /**
     * Задать Id сущности
     */
    public function setEntityId(string $documentId): self;

    /**
     * Возвращает результат для полнотекстового поиска
     */
    public function getEntityIndex(): mixed;

    /**
     * Присваивает результат для полнотекстового поиска
     */
    public function setEntityIndex(string $entity_index): self;

    /**
     * Возвращаем тег (название модуля) для поиска
     */
    public function getSearchTag(): mixed;

    /**
     * Присваивает тег (название модуля), для поиска по найденным идентификаторам
     */
    public function setSearchTag(string $search_tag): self;

}