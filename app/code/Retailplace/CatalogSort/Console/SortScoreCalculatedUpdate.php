<?php

/**
 * Retailplace_CatalogSort
 *
 * @copyright   Copyright (c) 2021 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Alexander Korsun <aleksandr@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\CatalogSort\Console;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Retailplace\CatalogSort\Model\SortScoreCalculatedManagement;

/**
 * Class SortScoreCalculatedUpdate
 */
class SortScoreCalculatedUpdate extends Command
{
    /** @var string */
    public const COMMAND_NAME = 'retailplace:sort-score-calculated:update';

    /**
     * @var SortScoreCalculatedManagement
     */
    private $sortScoreCalculatedManagement;

    /**
     * SortScoreCalculatedUpdate constructor.
     *
     * @param SortScoreCalculatedManagement $sortScoreCalculatedManagement
     * @param string|null $name
     */
    public function __construct(
        SortScoreCalculatedManagement $sortScoreCalculatedManagement,
        string $name = null
    ) {
        $this->sortScoreCalculatedManagement = $sortScoreCalculatedManagement;
        parent::__construct($name);
    }

    /**
     * Configure CLI Command
     */
    protected function configure()
    {
        $this->setName(self::COMMAND_NAME);
        $this->setDescription('Update "Sort score calculated" attribute');
        parent::configure();
    }

    /**
     * Execute Update Products Command
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $count = $this->sortScoreCalculatedManagement->updateSortScore();
        $output->writeln(__('<info>%1 Products were updated.</info>', $count));
    }
}
