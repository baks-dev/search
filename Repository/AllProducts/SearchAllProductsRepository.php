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
use BaksDev\Products\Category\Entity\Section\CategoryProductSection;
use BaksDev\Products\Category\Entity\Section\Field\CategoryProductSectionField;
use BaksDev\Products\Category\Entity\Section\Field\Trans\CategoryProductSectionFieldTrans;
use BaksDev\Products\Category\Entity\Trans\CategoryProductTrans;
use BaksDev\Products\Product\Entity\Active\ProductActive;
use BaksDev\Products\Product\Entity\Category\ProductCategory;
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
use BaksDev\Products\Product\Entity\Property\ProductProperty;
use BaksDev\Products\Product\Entity\Seo\ProductSeo;
use BaksDev\Products\Product\Entity\Trans\ProductTrans;
use BaksDev\Search\Index\RedisSearchIndexHandler;
use BaksDev\Search\Type\RedisTags\ProductModificationRedisSearchTag;
use BaksDev\Search\Type\RedisTags\ProductOfferRedisSearchTag;
use BaksDev\Search\Type\RedisTags\ProductRedisSearchTag;
use BaksDev\Search\Type\RedisTags\ProductVariationRedisSearchTag;
use BaksDev\Users\Profile\UserProfile\Entity\Info\UserProfileInfo;
use BaksDev\Users\Profile\UserProfile\Repository\UserProfileTokenStorage\UserProfileTokenStorageInterface;
use BaksDev\Users\Profile\UserProfile\Type\Id\UserProfileUid;
use BaksDev\Users\Profile\UserProfile\Type\UserProfileStatus\Status\UserProfileStatusActive;
use BaksDev\Users\Profile\UserProfile\Type\UserProfileStatus\UserProfileStatus;
use Generator;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Target;

final class SearchAllProductsRepository implements SearchAllProductsInterface
{
    const int MAX_RESULTS = 10;

    private ?SearchDTO $search = null;

    private int|false $maxResult = false;

    public function __construct(
        #[Target('productsProductLogger')] private LoggerInterface $logger,
        private readonly DBALQueryBuilder $DBALQueryBuilder,
        private readonly UserProfileTokenStorageInterface $userProfileTokenStorage,
        private readonly ?RedisSearchIndexHandler $redisSearchIndexHandler = null,
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

    public function findAll(): Generator|false
    {

        if(is_null($this->search))
        {
            throw new InvalidArgumentException('Не передан обязательный параметр запроса $search');
        }

        if(empty($this->search->getQuery()))
        {
            return false;
        }


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


        /**
         * Изображение продукта
         */
        $dbal->leftJoin(
            'product_event',
            ProductPhoto::class,
            'product_photo',
            'product_photo.event = product_event.id AND product_photo.root = true'
        )
            ->addGroupBy('product_photo.ext');

        $dbal->leftJoin(
            'product_offer',
            ProductOfferImage::class,
            'product_offer_images',
            'product_offer_images.offer = product_offer.id AND product_offer_images.root = true'
        )
            ->addGroupBy('product_offer_images.ext');

        $dbal->leftJoin(
            'product_offer',
            ProductVariationImage::class,
            'product_variation_image',
            'product_variation_image.variation = product_variation.id AND product_variation_image.root = true'
        )
            ->addGroupBy('product_variation_image.ext');

        $dbal->leftJoin(
            'product_modification',
            ProductModificationImage::class,
            'product_modification_image',
            'product_modification_image.modification = product_modification.id AND product_modification_image.root = true'
        )
            ->addGroupBy('product_modification_image.ext');

        /** Агрегация фотографий */
        $dbal->addSelect("
            CASE 
            WHEN product_modification_image.ext IS NOT NULL THEN
                JSON_AGG 
                    (DISTINCT
                        JSONB_BUILD_OBJECT
                            (
                                'img_root', product_modification_image.root,
                                'img', CONCAT ( '/upload/".$dbal->table(ProductModificationImage::class)."' , '/', product_modification_image.name),
                                'img_ext', product_modification_image.ext,
                                'img_cdn', product_modification_image.cdn
                            )
                    )
            
            WHEN product_variation_image.ext IS NOT NULL THEN
                JSON_AGG
                    (DISTINCT
                    JSONB_BUILD_OBJECT
                        (
                            'img_root', product_variation_image.root,
                            'img', CONCAT ( '/upload/".$dbal->table(ProductVariationImage::class)."' , '/', product_variation_image.name),
                            'img_ext', product_variation_image.ext,
                            'img_cdn', product_variation_image.cdn
                        ) 
                    )
                    
            WHEN product_offer_images.ext IS NOT NULL THEN
            JSON_AGG
                (DISTINCT
                    JSONB_BUILD_OBJECT
                        (
                            'img_root', product_offer_images.root,
                            'img', CONCAT ( '/upload/".$dbal->table(ProductOfferImage::class)."' , '/', product_offer_images.name),
                            'img_ext', product_offer_images.ext,
                            'img_cdn', product_offer_images.cdn
                        )
                        
                    /*ORDER BY product_photo.root DESC, product_photo.id*/
                )
                
            WHEN product_photo.ext IS NOT NULL THEN
            JSON_AGG
                (DISTINCT
                    JSONB_BUILD_OBJECT
                        (
                            'img_root', product_photo.root,
                            'img', CONCAT ( '/upload/".$dbal->table(ProductPhoto::class)."' , '/', product_photo.name),
                            'img_ext', product_photo.ext,
                            'img_cdn', product_photo.cdn
                        )
                    
                    /*ORDER BY product_photo.root DESC, product_photo.id*/
                )
            
            ELSE NULL
            END
			AS product_root_image"
        );

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


        /** Категория */
        $dbal->leftJoin(
            'product_event',
            ProductCategory::class,
            'product_event_category',
            '
                    product_event_category.event = product_event.id AND 
                    product_event_category.root = true'
        );

        $dbal->leftJoin(
            'product_event_category',
            CategoryProduct::class,
            'category',
            'category.id = product_event_category.category'
        );

        $dbal
            ->addSelect('category_info.url AS category_url')
            //                ->addSelect('category_info.minimal AS category_minimal')
            //                ->addSelect('category_info.input AS category_input')
            //                ->addSelect('category_info.threshold AS category_threshold')
            ->leftJoin(
                'category',
                CategoryProductInfo::class,
                'category_info',
                'category_info.event = category.event'
            );

        $dbal
            ->addSelect('category_trans.name AS category_name')
            ->leftJoin(
                'category',
                CategoryProductTrans::class,
                'category_trans',
                'category_trans.event = category.event AND category_trans.local = :local'
            );

        $dbal->leftJoin(
            'category',
            CategoryProductSection::class,
            'category_section',
            'category_section.event = category.event'
        );

        /** Свойства, участвующие в карточке */
        $dbal->leftJoin(
            'category_section',
            CategoryProductSectionField::class,
            'category_section_field',
            'category_section_field.section = category_section.id AND (category_section_field.card = TRUE OR category_section_field.photo = TRUE OR category_section_field.name = TRUE )'
        );

        $dbal->leftJoin(
            'category_section_field',
            CategoryProductSectionFieldTrans::class,
            'category_section_field_trans',
            'category_section_field_trans.field = category_section_field.id AND category_section_field_trans.local = :local'
        );

        $dbal->leftJoin(
            'category_section_field',
            ProductProperty::class,
            'product_property',
            'product_property.event = product.event AND product_property.field = category_section_field.const'
        );

        $dbal->addSelect("JSON_AGG
                ( DISTINCT
                    
                        JSONB_BUILD_OBJECT
                        (
                            'field_sort', category_section_field.sort,
                            'field_name', category_section_field.name,
                            'field_card', category_section_field.card,
                            'field_photo', category_section_field.photo,
                            'field_type', category_section_field.type,
                            'field_trans', category_section_field_trans.name,
                            'field_value', product_property.value
                        )
                    
                )
			    AS category_section_field"
        );


        /**
         * Наличие продукта
         */

        /** Наличие и резерв торгового предложения */
        $dbal->leftJoin(
            'product_offer',
            ProductOfferQuantity::class,
            'product_offer_quantity',
            'product_offer_quantity.offer = product_offer.id'
        );

        /** Наличие и резерв множественного варианта */
        $dbal->leftJoin(
            'product_variation',
            ProductVariationQuantity::class,
            'product_variation_quantity',
            'product_variation_quantity.variation = product_variation.id'
        );

        /** Наличие и резерв модификации множественного варианта */
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
            ->addSelect('product_active.active_from AS product_active_from')
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

        /** Персональная скидка из профиля авторизованного пользователя */
        if(true === $this->userProfileTokenStorage->isUser())
        {
            $profile = $this->userProfileTokenStorage->getProfileCurrent();

            if($profile instanceof UserProfileUid)
            {
                $dbal
                    ->addSelect('profile_info.discount AS profile_discount')
                    ->leftJoin(
                        'product',
                        UserProfileInfo::class,
                        'profile_info',
                        '
                        profile_info.profile = :profile AND 
                        profile_info.status = :profile_status'
                    )
                    ->setParameter(
                        key: 'profile',
                        value: $profile,
                        type: UserProfileUid::TYPE)
                    /** Активный статус профиля */
                    ->setParameter(
                        key: 'profile_status',
                        value: UserProfileStatusActive::class,
                        type: UserProfileStatus::TYPE
                    );
            }
        }

        /** Поиск */
        $search = str_replace('-', ' ', $this->search->getQuery());
        $searchBuilder = $dbal->createSearchQueryBuilder($this->search);

        $this->logger->info(sprintf('Строка поиска: %s', $search));


        $resultModifications = false;
        $resultVariations = false;
        $resultOffers = false;
        $resultProducts = false;

        if($this->redisSearchIndexHandler instanceof RedisSearchIndexHandler)
        {
            /**
             * Модификация
             */

            $resultModifications = $this->redisSearchIndexHandler
                ->handleSearchQuery($search, ProductModificationRedisSearchTag::TAG);

            if(is_array($resultModifications))
            {
                $searchBuilder->addSearchInArray('product_modification.id', array_column($resultModifications, "id"));
                $this->logger->warning('Найден результат поиска product_modification');
            }

            /**
             * Вариация
             */

            $resultVariations = $this->redisSearchIndexHandler
                ->handleSearchQuery($search, ProductVariationRedisSearchTag::TAG);


            if(is_array($resultVariations))
            {
                $searchBuilder->addSearchInArray('product_variation.id', array_column($resultVariations, "id"));
                $this->logger->warning('Найден результат поиска product_variation');
            }

            /**
             * Торговое предложение
             */

            $resultOffers = $this->redisSearchIndexHandler
                ->handleSearchQuery($search, ProductOfferRedisSearchTag::TAG);

            if(is_array($resultOffers))
            {
                $searchBuilder->addSearchInArray('product_offer.id', array_column($resultOffers, "id"));
                $this->logger->warning('Найден результат поиска product_offer');
            }

            /**
             * Продукт
             */

            $resultProducts = $this->redisSearchIndexHandler
                ->handleSearchQuery($search, ProductRedisSearchTag::TAG);

            if(is_array($resultProducts))
            {
                $searchBuilder->addSearchInArray('product.id', array_column($resultProducts, "id"));
                $this->logger->warning('Найден результат поиска product');
            }

        }

        /**
         * В случае если не найден результат в RedisSearch - пробуем найти бо базе
         */

        if($resultModifications === false || $resultVariations === false || $resultOffers === false || $resultProducts === false)
        {
            $this->logger->warning('Поиск по RedisSearch не найден, пробуем найти по базе данных');

            $searchBuilder
                ->addSearchEqualUid('product.id')
                ->addSearchEqualUid('product.event')
                ->addSearchEqualUid('product_variation.id')
                ->addSearchEqualUid('product_modification.id')
                ->addSearchLike('product_trans.name')
                ->addSearchLike('product_info.article')
                ->addSearchLike('product_offer.article')
                ->addSearchLike('product_modification.article')
                ->addSearchLike('product_modification.article')
                ->addSearchLike('product_variation.article');
        }

        $dbal->allGroupByExclude();

        $dbal->orderBy('product_reserve', 'DESC');

        $this->maxResult ? $dbal->setMaxResults($this->maxResult) : $dbal->setMaxResults(self::MAX_RESULTS);

        return $dbal->fetchAllHydrate(SearchAllResult::class);


    }

}
