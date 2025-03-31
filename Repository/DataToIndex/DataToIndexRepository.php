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

namespace BaksDev\Search\Repository\DataToIndex;

use BaksDev\Core\Doctrine\DBALQueryBuilder;
use BaksDev\Products\Product\Entity\Event\ProductEvent;
use BaksDev\Products\Product\Entity\Info\ProductInfo;
use BaksDev\Products\Product\Entity\Modify\ProductModify;
use BaksDev\Products\Product\Entity\Offers\ProductOffer;
use BaksDev\Products\Product\Entity\Offers\Variation\Modification\ProductModification;
use BaksDev\Products\Product\Entity\Offers\Variation\ProductVariation;
use BaksDev\Products\Product\Entity\Product;
use BaksDev\Products\Product\Entity\Trans\ProductTrans;
use BaksDev\Products\Product\Type\Event\ProductEventUid;
use Doctrine\DBAL\ParameterType;

/**
 * Используется для получения данных по товару (название и актикул) по ProductEventUid
 * в обработчике сообщения SearchHandler.php
 */
final readonly class DataToIndexRepository implements DataToIndexInterface
{
    public function __construct(private DBALQueryBuilder $DBALQueryBuilder) {}

    /**
     * Получение данных для добавления в индекс
     *
     * @param ProductEventUid|string $eventUid
     * @return array|bool
     * @throws \Doctrine\DBAL\Exception
     */
    public function findProductDataToIndexRepository(ProductEventUid|string $eventUid): array|bool
    {
        $dbal = $this->DBALQueryBuilder
            ->createQueryBuilder(self::class)
            ->bindLocal();

        $dbal
            ->addSelect('product.id AS id')
            ->join(
                'product_event',
                Product::class,
                'product',
                'product_event.id = product.event AND product.event = :event'
            );

        $dbal
            ->addSelect('product_trans.name AS product_name')
            ->join(
                'product',
                ProductTrans::class,
                'product_trans',
                'product_trans.event = product.event AND product_trans.local = :local AND product.event = :event'
            );


        /** Modify */
        $dbal
            ->addSelect('product_modify.action AS action')
            ->join(
                'product',
                ProductModify::class,
                'product_modify',
                'product_modify.event = product.event'
            );

        /** Инфо */
        $dbal
            ->leftJoin(
                'product_event',
                ProductInfo::class,
                'product_info',
                'product_info.product = product.id'
            );

        /** Торгового предложение */
        $dbal
            ->leftJoin(
                'product_event',
                ProductOffer::class,
                'product_offer',
                'product_offer.event = product_event.id'
            );

        /** Множественные варианты торгового предложения */
        $dbal
            ->leftJoin(
                'product_offer',
                ProductVariation::class,
                'product_variation',
                'product_variation.offer = product_offer.id'
            );

        /** Модификация множественного варианта */
        $dbal
            ->leftJoin(
                'product_variation',
                ProductModification::class,
                'product_modification',
                'product_modification.variation = product_variation.id '
            );


        /** Артикул продукта */
        $dbal->addSelect("
            COALESCE(
                product_modification.article,
                product_variation.article,
                product_offer.article,
                product_info.article
            ) AS product_article
		");


        $dbal
            ->from(ProductEvent::class, 'product_event')
            ->setParameter('event', $eventUid, ProductEventUid::TYPE)
            ->setParameter('device', 'pc', ParameterType::STRING) /// TODO
        ;

        return $dbal->fetchAssociative();
    }
}