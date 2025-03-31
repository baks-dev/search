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

namespace BaksDev\Search\Index;

use BaksDev\Products\Product\Type\Id\ProductUid;
use BaksDev\Search\RediSearch\Index;
use BaksDev\Search\RediSearch\Query\BuilderInterface;
use BaksDev\Search\RedisRaw\PredisAdapter;
use BaksDev\Search\RedisRaw\RedisRawClientInterface;
use BaksDev\Search\RedisSearchDocuments\EntityDocument;
use Psr\Log\LoggerInterface;

/**
 * Класс для работы RediSearch
 *
 * Включает методы для инициализации клиента,
 * добавления в индексб удаления,
 * а также метод для получения результатов по поисковомоу слову
 */
final class RedisSearchIndexHandler implements RedisSearchIndexInterface
{

    public RedisRawClientInterface $client;
    private Index $index;

    public function __construct(
        private readonly string $REDIS_HOST,
        private readonly string $REDIS_PORT,
        private readonly string $REDIS_TABLE,
        private readonly string $REDIS_PASSWORD,
        private readonly LoggerInterface $logger,
    )
    {

        $this->initClient($this->REDIS_HOST, $this->REDIS_PORT, $this->REDIS_TABLE, $this->REDIS_PASSWORD);
        $this->initIndex();
    }

    public function initClient($host, $port, $table, $password): void
    {
        $this->client = (new PredisAdapter($this->logger))->connect(
            $host, $port, $table, $password
        );
    }

    /**
     * Добавление необходимых полей в индекс
     * и при необходимости создание
     */
    private function initIndex(): void
    {
        $this->index = new Index($this->client);
        $this->index
            ->addTextField('entity_index')
            ->addTagField('search_tag'); // rename -> search_tag
        if(!$this->index->exists())
        {
            $this->index->create();
        }

        // Удаление индекса
//                         $this->index->drop(); die();

    }

    public function addToIndex(EntityDocument $entityDocument): void
    {
        $this->index->delete($entityDocument->getId(), true);
        $this->index->add($entityDocument);
    }

    /**
     * Удалить из индекса
     */
    public function removeFromIndex(ProductUid|string $product_id): void
    {
        $this->index->delete($product_id, TRUE);
    }

    /**
     * Получить результаты по поисковомоу слову, с учетов тегов
     */
    public function handleSearchQuery(?string $search = null, ?string $search_tag = null): bool|array
    {

        /** @var BuilderInterface $builder */
        $builder = $this->index->noContent();
        if(null !== $search_tag)
        {
            $builder->tagFilter('search_tag', [$search_tag]);
        }
        $result = $builder->search($search);

        return $result->getCount() ? $result->getDocuments() : false;
    }
}