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

declare(strict_types=1);

namespace BaksDev\Search\Messenger;

use BaksDev\Products\Product\Messenger\ProductMessage;
use BaksDev\Search\Index\RedisSearchIndexHandler;
use BaksDev\Search\Repository\DataToIndex\DataToIndexRepository;
use BaksDev\Search\Type\RedisTags\Product;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * Обработчик сообщения ProductMessage.
 * Удаление, добавление/обновление данных в индекс
 */
#[AsMessageHandler(priority: 0)]
final class SearchHandler
{

    public function __construct(
        private readonly RedisSearchIndexHandler $handler,
        private readonly DataToIndexRepository $repository,
        private readonly Product $product,
    ) {}

    public function __invoke(ProductMessage $message): void
    {
        $productData = $this->repository->findProductDataToIndexRepository($message->getEvent());
        if(false === $productData)
        {
            /**
             * Удалить из индекса
             */
            $this->handler->removeFromIndex($message->getId());
            return;
        }

        $this->handler->addToIndex($productData, $this->product->prepareDocument($productData));
    }
}
