<?php
/*
 *  Copyright 2023.  Baks.dev <admin@baks.dev>
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

namespace BaksDev\Search\Listeners\Event;

use BaksDev\Core\Form\Search\SearchDTO;
use BaksDev\Core\Form\Search\SearchForm;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Twig\Environment;

#[AsEventListener(event: ControllerEvent::class)]
final class SearchFormListener
{
    private $twig;

    private FormFactoryInterface $formFactory;

    public function __construct(
        Environment $twig,
        FormFactoryInterface $formFactory
    )
    {
        $this->twig = $twig;
        $this->formFactory = $formFactory;
    }

    /** Создает глобальную переменную для twig - форму поиска  */
    public function onKernelController(ControllerEvent $event): void
    {
        if($event->getRequest()->isXmlHttpRequest())
        {
            return;
        }

        if(str_contains($event->getRequest()->getRequestUri(), 'admin'))
        {
            return;
        }

        $search = new SearchDTO($event->getRequest());
        $searchForm = $this->formFactory
            ->create(
                type: SearchForm::class,
                data: $search,
                options: ['action' => '/search',]
            )
            ->handleRequest($event->getRequest());

        $this->twig->addGlobal('baks_search_form', $searchForm->createView());

        /* Форма поиска в футере */
        $searchFormFooter = $this->formFactory
            ->createNamed(
                name: 'search_form_footer' ,
                type: SearchForm::class,
                data: $search,
                options: [
                    'action' => '/search',
                ]
            );

        $searchFormFooter->handleRequest($event->getRequest());
        $this->twig->addGlobal('baks_search_form_footer', $searchFormFooter->createView());
    }
}