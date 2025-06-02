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

namespace BaksDev\Search\Controller;

use BaksDev\Core\Controller\AbstractController;
use BaksDev\Core\Form\Search\SearchDTO;
use BaksDev\Core\Form\Search\SearchForm;
use BaksDev\Search\Type\RedisTags\Collection\RedisSearchIndexTagCollection;
use BaksDev\Core\Contracts\Search\SearchIndexTagInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Annotation\Route;

#[AsController]
final class SearchController extends AbstractController
{
    #[Route('/search', name: 'public.search', methods: ['POST', 'GET'], priority: -100)]
    public function search(
        Request $request,
        RedisSearchIndexTagCollection $RedisSearchIndexTagCollection
    ): Response
    {
        /** Поиск */
        $search = new SearchDTO();

        /** Задать теги для использования в поиске */
        $search->setSearchTags(['products-product']);

        $searchForm = $this
            ->createForm(
                type: SearchForm::class,
                data: $search,
                options: ['action' => $this->generateUrl('search:public.search'),]
            )
            ->handleRequest($request);


        $search_results = [];

        $searchArrayTags = $search->getSearchTags();

        /** Получить результаты поиска по тегам */
        /** @var SearchIndexTagInterface $redisTag */
        foreach($RedisSearchIndexTagCollection->cases() as $redisTag)
        {

            $max_results = false;

            /** Теги заданы или нет */
            if(in_array($redisTag->getValue(), $search->getSearchTags()) || empty($searchArrayTags))
            {
                // Для Ajax запроса указать определенное кол-во
                if($request->headers->get('X-Requested-With') === 'XMLHttpRequest')
                {
                    $max_results = 6;
                }

                /** @var \Generator $searchData */
                $searchData = $redisTag->getRepositorySearchData($search, $max_results);

                if($searchData !== false)
                {
                    $search_results[$redisTag->getValue()] = $searchData;
                }
            }

        }

        return $this->render([
            'search' => $searchForm->createView(),
            'headers' => count($searchArrayTags) !== 1,
            'search_results' => $search_results,
        ]);

    }
}
