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

namespace BaksDev\Search\Repository\AllProducts;

use BaksDev\Core\Doctrine\DBALQueryBuilder;
use BaksDev\Core\Form\Search\SearchDTO;
use BaksDev\Products\Category\Entity\CategoryProduct;
use BaksDev\Products\Category\Entity\Info\CategoryProductInfo;
use BaksDev\Products\Category\Entity\Offers\CategoryProductOffers;
use BaksDev\Products\Category\Entity\Offers\Variation\CategoryProductVariation;
use BaksDev\Products\Category\Entity\Offers\Variation\Modification\CategoryProductModification;
use BaksDev\Products\Category\Entity\Trans\CategoryProductTrans;
use BaksDev\Products\Product\Entity\Active\ProductActive;
use BaksDev\Products\Product\Entity\Category\ProductCategory;
use BaksDev\Products\Product\Entity\Description\ProductDescription;
use BaksDev\Products\Product\Entity\Event\ProductEvent;
use BaksDev\Products\Product\Entity\Info\ProductInfo;
use BaksDev\Products\Product\Entity\Offers\Image\ProductOfferImage;
use BaksDev\Products\Product\Entity\Offers\Price\ProductOfferPrice;
use BaksDev\Products\Product\Entity\Offers\ProductOffer;
use BaksDev\Products\Product\Entity\Offers\Quantity\ProductOfferQuantity;
use BaksDev\Products\Product\Entity\Offers\Variation\Image\ProductVariationImage;
use BaksDev\Products\Product\Entity\Offers\Variation\Modification\Image\ProductModificationImage;
use BaksDev\Products\Product\Entity\Offers\Variation\Modification\Price\ProductModificationPrice;
use BaksDev\Products\Product\Entity\Offers\Variation\Modification\ProductModification;
use BaksDev\Products\Product\Entity\Offers\Variation\Modification\Quantity\ProductModificationQuantity;
use BaksDev\Products\Product\Entity\Offers\Variation\Price\ProductVariationPrice;
use BaksDev\Products\Product\Entity\Offers\Variation\ProductVariation;
use BaksDev\Products\Product\Entity\Offers\Variation\Quantity\ProductVariationQuantity;
use BaksDev\Products\Product\Entity\Photo\ProductPhoto;
use BaksDev\Products\Product\Entity\Price\ProductPrice;
use BaksDev\Products\Product\Entity\Product;
use BaksDev\Products\Product\Entity\ProductInvariable;
use BaksDev\Products\Product\Entity\Seo\ProductSeo;
use BaksDev\Products\Product\Entity\Trans\ProductTrans;
use BaksDev\Search\Index\RedisSearchIndexHandler;
use BaksDev\Search\Type\RedisTags\ProductModificationRedisSearchTag;
use BaksDev\Search\Type\RedisTags\ProductOfferRedisSearchTag;
use BaksDev\Search\Type\RedisTags\ProductRedisSearchTag;
use BaksDev\Search\Type\RedisTags\ProductVariationRedisSearchTag;
use Psr\Log\LoggerInterface;

final class SearchAllProductsRepository implements SearchAllProductsInterface
{

    const int MAX_RESULTS = 10;

    private ?SearchDTO $search = null;

    private int|false $maxResult = false;

    public function __construct(
        private readonly DBALQueryBuilder $DBALQueryBuilder,
        private readonly ?RedisSearchIndexHandler $redisSearchIndexHandler
    ) {}

    /** Максимальное количество записей в результате */
    public function maxResult(int $max): self
    {
        $this->maxResult = $max;
        return $this;
    }

    public function search(SearchDTO $search): self
    {
        $this->search = $search;
        return $this;
    }

    public function getAllProductsData(): array|false
    {

        if($this->search->getQuery())
        {
            $dbal = $this->DBALQueryBuilder
                ->createQueryBuilder(self::class)
                ->bindLocal();

            $dbal
                ->select('product.id')
                ->addSelect('product.event')
                ->from(Product::class, 'product');

            $dbal->leftJoin(
                'product',
                ProductEvent::class,
                'product_event',
                'product_event.id = product.event'
            );

            $dbal
                ->addSelect('product_trans.name AS product_name')
                ->leftJoin(
                    'product_event',
                    ProductTrans::class,
                    'product_trans',
                    'product_trans.event = product_event.id AND product_trans.local = :local'
                );

            $dbal
                ->addSelect('trans.name AS search_name')
                ->leftJoin(
                    'product',
                    ProductTrans::class,
                    'trans',
                    'trans.event = product.event AND trans.local = :local'
                );

            /* Базовая Цена товара */
            $dbal->leftJoin(
                'product',
                ProductPrice::class,
                'product_price',
                'product_price.event = product.event'
            );

            $dbal
                ->addSelect('product_info.url AS url')
                ->leftJoin(
                    'product',
                    ProductInfo::class,
                    'product_info',
                    'product_info.product = product.id '
                );

            $dbal
                ->addSelect('seo.title AS search_desc')
                ->leftJoin(
                    'product',
                    ProductSeo::class,
                    'seo',
                    'seo.event = product.event'
                );


            /** Торговое предложение */

            $dbal
                ->addSelect('product_offer.id as product_offer_uid')
                ->addSelect('product_offer.const as product_offer_const')
                ->addSelect('product_offer.value as product_offer_value')
                ->addSelect('product_offer.postfix as product_offer_postfix')
                ->leftJoin(
                    'product_event',
                    ProductOffer::class,
                    'product_offer',
                    'product_offer.event = product_event.id'
                );

            /* Цена торгового предо жения */
            $dbal->leftJoin(
                'product_offer',
                ProductOfferPrice::class,
                'product_offer_price',
                'product_offer_price.offer = product_offer.id'
            );

            /* Тип торгового предложения */
            $dbal
                ->addSelect('category_offer.reference as product_offer_reference')
                ->leftJoin(
                    'product_offer',
                    CategoryProductOffers::class,
                    'category_offer',
                    'category_offer.id = product_offer.category_offer'
                );


            /** Множественные варианты торгового предложения */

            $dbal
                ->addSelect('product_variation.id as product_variation_uid')
                ->addSelect('product_variation.const as product_variation_const')
                ->addSelect('product_variation.value as product_variation_value')
                ->addSelect('product_variation.postfix as product_variation_postfix')
                ->leftJoin(
                    'product_offer',
                    ProductVariation::class,
                    'product_variation',
                    'product_variation.offer = product_offer.id'
                );

            /* Цена множественного варианта */
            $dbal->leftJoin(
                'product_variation',
                ProductVariationPrice::class,
                'product_variation_price',
                'product_variation_price.variation = product_variation.id'
            );


            /* Тип множественного варианта торгового предложения */
            $dbal
                ->addSelect('category_variation.reference as product_variation_reference')
                ->leftJoin(
                    'product_variation',
                    CategoryProductVariation::class,
                    'category_variation',
                    'category_variation.id = product_variation.category_variation'
                );


            /** Модификация множественного варианта */
            $dbal
                ->addSelect('product_modification.id as product_modification_uid')
                ->addSelect('product_modification.const as product_modification_const')
                ->addSelect('product_modification.value as product_modification_value')
                ->addSelect('product_modification.postfix as product_modification_postfix')
                ->leftJoin(
                    'product_variation',
                    ProductModification::class,
                    'product_modification',
                    'product_modification.variation = product_variation.id '
                );

            /* Цена модификации множественного варианта */
            $dbal->leftJoin(
                'product_modification',
                ProductModificationPrice::class,
                'product_modification_price',
                'product_modification_price.modification = product_modification.id'
            );

            /** Получаем тип модификации множественного варианта */
            $dbal
                ->addSelect('category_modification.reference as product_modification_reference')
                ->leftJoin(
                    'product_modification',
                    CategoryProductModification::class,
                    'category_modification',
                    'category_modification.id = product_modification.category_modification'
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


            /** Фото продукта */

            $dbal->leftJoin(
                'product_event',
                ProductPhoto::class,
                'product_photo',
                'product_photo.event = product_event.id AND product_photo.root = true'
            );

            $dbal->leftJoin(
                'product_offer',
                ProductOfferImage::class,
                'product_offer_images',
                'product_offer_images.offer = product_offer.id AND product_offer_images.root = true'
            );

            $dbal->leftJoin(
                'product_offer',
                ProductVariationImage::class,
                'product_variation_image',
                'product_variation_image.variation = product_variation.id AND product_variation_image.root = true'
            );

            $dbal->leftJoin(
                'product_modification',
                ProductModificationImage::class,
                'product_modification_image',
                'product_modification_image.modification = product_modification.id AND product_modification_image.root = true'
            );

            $dbal->addSelect(
                "
			CASE
			
			    WHEN product_modification_image.name IS NOT NULL 
			   THEN CONCAT ( '/upload/".$dbal->table(ProductModificationImage::class)."' , '/', product_modification_image.name)
			   
			   WHEN product_variation_image.name IS NOT NULL 
			   THEN CONCAT ( '/upload/".$dbal->table(ProductVariationImage::class)."' , '/', product_variation_image.name)
			   
			   WHEN product_offer_images.name IS NOT NULL 
			   THEN CONCAT ( '/upload/".$dbal->table(ProductOfferImage::class)."' , '/', product_offer_images.name)
			   
			   WHEN product_photo.name IS NOT NULL 
			   THEN CONCAT ( '/upload/".$dbal->table(ProductPhoto::class)."' , '/', product_photo.name)
			   
			   ELSE NULL
			END AS product_image
		"
            );

            /** Флаг загрузки файла CDN */
            $dbal->addSelect("
			CASE
			   WHEN product_variation_image.name IS NOT NULL 
			   THEN product_variation_image.ext
			   
			   WHEN product_offer_images.name IS NOT NULL 
			   THEN product_offer_images.ext
			   
			   WHEN product_photo.name IS NOT NULL 
			   THEN product_photo.ext
			   
			   ELSE NULL
			END AS product_image_ext
		");


            /** Флаг загрузки файла CDN */
            $dbal->addSelect("
			CASE
			   WHEN product_variation_image.name IS NOT NULL 
			   THEN product_variation_image.cdn
					
			   WHEN product_offer_images.name IS NOT NULL 
			   THEN product_offer_images.cdn
					
			   WHEN product_photo.name IS NOT NULL 
			   THEN product_photo.cdn
			   
			   ELSE NULL
			END AS product_image_cdn
		");


            /* Стоимость продукта */

            $dbal->addSelect('
			COALESCE(
                NULLIF(product_modification_price.price, 0), 
                NULLIF(product_variation_price.price, 0), 
                NULLIF(product_offer_price.price, 0), 
                NULLIF(product_price.price, 0),
                0
            ) AS product_price
		');


            /* Предыдущая стоимость продукта */

            $dbal->addSelect("
			COALESCE(
                NULLIF(product_modification_price.old, 0),
                NULLIF(product_variation_price.old, 0),
                NULLIF(product_offer_price.old, 0),
                NULLIF(product_price.old, 0),
                0
            ) AS product_old_price
		");

            /* Валюта продукта */

            $CURRENCY = 'CASE
			   WHEN product_modification_price.price IS NOT NULL AND product_modification_price.price > 0 
			   THEN product_modification_price.currency
			   
			   WHEN product_variation_price.price IS NOT NULL AND product_variation_price.price > 0 
			   THEN product_variation_price.currency
			   
			   WHEN product_offer_price.price IS NOT NULL AND product_offer_price.price > 0 
			   THEN product_offer_price.currency
			   
			   WHEN product_price.price IS NOT NULL AND product_price.price > 0 
			   THEN product_price.currency
			   
			   ELSE NULL
			END
		';

            $dbal->addSelect($CURRENCY.' AS product_currency ');
            $dbal->andWhere($CURRENCY.' IS NOT NULL');


            /* Категория */
            $dbal->leftJoin(
                'product_event',
                ProductCategory::class,
                'product_event_category',
                'product_event_category.event = product_event.id AND product_event_category.root = true'
            );


            $dbal->leftJoin(
                'product_event_category',
                CategoryProduct::class,
                'category',
                'category.id = product_event_category.category'
            );

            $dbal
                ->addSelect('category_trans.name AS category_name')
                ->leftJoin(
                    'category',
                    CategoryProductTrans::class,
                    'category_trans',
                    'category_trans.event = category.event AND category_trans.local = :local'
                );


            $dbal
                ->addSelect('category_info.url AS category_url')
                ->addSelect('category_info.minimal AS category_minimal')
                ->addSelect('category_info.input AS category_input')
                ->addSelect('category_info.threshold AS category_threshold')
                ->leftJoin(
                    'category',
                    CategoryProductInfo::class,
                    'category_info',
                    'category_info.event = category.event'
                );


            /* Наличие продукта */

            /* Наличие и резерв торгового предложения */
            $dbal->leftJoin(
                'product_offer',
                ProductOfferQuantity::class,
                'product_offer_quantity',
                'product_offer_quantity.offer = product_offer.id'
            );

            /* Наличие и резерв множественного варианта */
            $dbal->leftJoin(
                'product_variation',
                ProductVariationQuantity::class,
                'product_variation_quantity',
                'product_variation_quantity.variation = product_variation.id'
            );

            $dbal
                ->leftJoin(
                    'product_modification',
                    ProductModificationQuantity::class,
                    'product_modification_quantity',
                    'product_modification_quantity.modification = product_modification.id'
                );


            $dbal->addSelect("
			COALESCE(
                NULLIF(product_modification_quantity.quantity, 0),
                NULLIF(product_variation_quantity.quantity, 0),
                NULLIF(product_offer_quantity.quantity, 0),
                NULLIF(product_price.quantity, 0),
                0
            ) AS product_quantity   
		");

            $dbal->addSelect("
			COALESCE(
                NULLIF(product_modification_quantity.reserve, 0),
                NULLIF(product_variation_quantity.reserve, 0),
                NULLIF(product_offer_quantity.reserve, 0),
                NULLIF(product_price.reserve, 0),
                0
            ) AS product_reserve
		");

            $dbal
                ->join(
                    'product',
                    ProductActive::class,
                    'product_active',
                    '
                    product_active.event = product.event AND 
                    product_active.active IS TRUE    
                '
                );

            /** Product Invariable */
            $dbal
                ->addSelect('product_invariable.id AS product_invariable_id')
                ->leftJoin(
                    'product_modification',
                    ProductInvariable::class,
                    'product_invariable',
                    '
                    product_invariable.product = product.id AND 
                    
                    (
                        (product_offer.const IS NOT NULL AND product_invariable.offer = product_offer.const) OR 
                        (product_offer.const IS NULL AND product_invariable.offer IS NULL)
                    )
                    
                    AND
                     
                    (
                        (product_variation.const IS NOT NULL AND product_invariable.variation = product_variation.const) OR 
                        (product_variation.const IS NULL AND product_invariable.variation IS NULL)
                    )
                     
                   AND
                   
                   (
                        (product_modification.const IS NOT NULL AND product_invariable.modification = product_modification.const) OR 
                        (product_modification.const IS NULL AND product_invariable.modification IS NULL)
                   )
         
            ');


            /** Поиск */

            $search = str_replace('-', ' ', $this->search->getQuery());

            /** Модификация */
            $result_mod = $this->redisSearchIndexHandler->handleSearchQuery($search, ProductModificationRedisSearchTag::TAG);
            /** Вариация */
            $result_var = $this->redisSearchIndexHandler->handleSearchQuery($search, ProductVariationRedisSearchTag::TAG);
            /** ТП */
            $result_off = $this->redisSearchIndexHandler->handleSearchQuery($search, ProductOfferRedisSearchTag::TAG);
            /** Товар */
            $result_prod = $this->redisSearchIndexHandler->handleSearchQuery($search, ProductRedisSearchTag::TAG);

            $builder = $dbal->createSearchQueryBuilder($this->search);

            if($result_mod)
            {
                $builder->addSearchInArray('product_modification.id', array_column($result_mod, "id"));
            }

            if($result_var)
            {
                $builder->addSearchInArray('product_variation.id', array_column($result_var, "id"));
            }

            if($result_off)
            {
                $builder->addSearchInArray('product_offer.id', array_column($result_off, "id"));
            }

            if($result_prod)
            {
                $builder->addSearchInArray('product.id', array_column($result_prod, "id"));
            }


            if($this->maxResult)
            {
                $dbal->setMaxResults($this->maxResult);
            }
            else
            {
                $dbal->setMaxResults(self::MAX_RESULTS);
            }


            if($result_mod || $result_var || $result_off || $result_prod)
            {
                $dbal->orderBy('product_reserve', 'DESC');

                return $dbal->fetchAllAssociative();
            }

            $dbal
                ->createSearchQueryBuilder($this->search)
                ->addSearchEqualUid('product.id')
                ->addSearchEqualUid('product.event')
                ->addSearchEqualUid('product_variation.id')
                ->addSearchEqualUid('product_modification.id')
                ->addSearchLike('product_trans.name')
                //->addSearchLike('product_trans.preview')
                ->addSearchLike('product_info.article')
                ->addSearchLike('product_offer.article')
                ->addSearchLike('product_modification.article')
                ->addSearchLike('product_modification.article')
                ->addSearchLike('product_variation.article');

            $dbal->orderBy('product_reserve', 'DESC');

            return $dbal->fetchAllAssociative();

        }

        return false;

    }

}
