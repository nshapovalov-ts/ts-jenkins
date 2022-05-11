<?php

/**
 * Retailplace_BestSeller
 *
 * @copyright   Copyright (c) 2021 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Evgeniy Derevyanko <evgeniy@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\BestSeller\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Retailplace\BestSeller\Model\BestSellersCalculatedManagement;

/**
 * Class BestSellersCalculatedUpdate
 */
class BestSellersCalculatedUpdate extends Command
{
    /** @var string */
    public const COMMAND_NAME = 'retailplace:best-sellers-calculated:update';

    /** @var \Retailplace\BestSeller\Model\BestSellersCalculatedManagement  */
    private $bestSellersCalculatedManagement;

    /**
     * BestSellersCalculatedUpdate constructor.
     *
     * @param \Retailplace\BestSeller\Model\BestSellersCalculatedManagement $bestSellersCalculatedManagement
     * @param string|null $name
     */
    public function __construct(
        BestSellersCalculatedManagement $bestSellersCalculatedManagement,
        string $name = null
    ) {
        $this->bestSellersCalculatedManagement = $bestSellersCalculatedManagement;
        parent::__construct($name);
    }

    /**
     * Configure CLI Command
     */
    protected function configure()
    {
        $this->setName(self::COMMAND_NAME);
        $this->setDescription('Update "Best sellers calculated" attribute');
        parent::configure();
    }

    /**
     * Execute Update Products Command
     *
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $count = $this->bestSellersCalculatedManagement->updateBestSellers();
        $output->writeln(__('<info>%1 Products were updated.</info>', $count));
    }
}
