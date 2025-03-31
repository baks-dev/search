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

namespace BaksDev\Search\Command;

use BaksDev\Search\Index\RedisSearchIndexHandler;
use BaksDev\Search\Type\RedisTags\Collection\RedisSearchIndexTagCollection;
use BaksDev\Search\Type\RedisTags\Collection\RedisSearchIndexTagInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'baks:redis:search:index',
    description: 'Команда для индексации в RediSearch'
)]
class RedisSearchIndexCommand extends Command
{

    public function __construct(
        private readonly RedisSearchIndexTagCollection $redisTags,
        private readonly RedisSearchIndexHandler $handler,
    )
    {
        parent::__construct();
    }

    //    protected function configure(): void
    //    {
    //        $this->addArgument('argument', InputArgument::OPTIONAL, 'Описание аргумента');
    //    }

    protected function execute(
        InputInterface $input,
        OutputInterface $output,
    ): int
    {

        $io = new SymfonyStyle($input, $output);

        $progressBar = new ProgressBar($output);
        $progressBar->start();

        /** @var RedisSearchIndexTagInterface $tag */
        foreach($this->redisTags->cases() as $tag)
        {
            $repositoryData = $tag->getRepositoryData();

            foreach($repositoryData as $item)
            {
                $this->handler->addToIndex($tag->prepareDocument($item));
                $progressBar->advance();
            }

        }

        $progressBar->finish();
        $io->success('Команда успешно завершена');

        return Command::SUCCESS;
    }

}
