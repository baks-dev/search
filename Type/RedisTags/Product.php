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

namespace BaksDev\Search\Type\RedisTags;

use BaksDev\Core\Services\Switcher\Switcher;
use BaksDev\Search\RedisSearchDocuments\EntityDocument;
use BaksDev\Search\Repository\AllProductsToIndex\AllProductsToIndexRepository;
use BaksDev\Search\Type\RedisTags\Collection\RedisSearchIndexTagInterface;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag('baks.redis-tags')]
class Product implements RedisSearchIndexTagInterface
{

    public function __construct(
        private readonly AllProductsToIndexRepository $repository,
        private readonly Switcher $switcher
    ) {}

    public const TAG = 'product-products';
    public const INDEX_ID = 'id';

    /**
     * @inheritDoc
     */
    public function getValue(): string
    {
        return self::TAG;
    }

    public function getIndexId(): string
    {
        return self::INDEX_ID;
    }

    /**
     * Создает EntityDocument для передачи в индекс
     */
    public function prepareDocument(array $item): EntityDocument {
        $documentId = $item[self::INDEX_ID];
        $entityDocument = new EntityDocument($documentId);

        // Необходимо очистить строки вида "TA01-16-205-55-94V" от символа дефиса '-' для правильной индексации и поиска
        // see https://github.com/RediSearch/RediSearch/issues/2628
        $product_article = mb_strtolower(str_replace('-', ' ', $item['product_article']));
        $product_name = mb_strtolower($item['product_name']);

        // Добавить "ошибочный" вариант Switcher
        $transl_article = $this->switcher->toRus($product_article);
        $transl_name = $this->switcher->toRus($product_name);

        $entityDocument
            ->setEntityIndex($product_article . ' ' . $product_name . ' ' . $transl_article . ' ' . $transl_name)
            ->setPrefix(self::TAG);

        return $entityDocument;
    }

    /**
     * @inheritDoc
     */
    public static function sort(): int
    {
        return 1;
    }

    // TODO
    public function getRepositoryData(): array //
    {
//        $repo = new AllProductsRepository(); // нужно заинжектить через DI
        return $this->repository->getAllProductsToIndex();
    }

    /**
     * @inheritDoc
     */
    public static function equals(string $tag): bool
    {
        // TODO: Implement equals() method.
    }
}