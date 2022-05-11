<?php
/**
 * Retailplace_MiraklSellerAdditionalField
 *
 * @copyright   Copyright (c) 2022 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Alexander Korsun <aleksandr@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\MiraklSellerAdditionalField\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Retailplace\MiraklSellerAdditionalField\Model\ShopUpdater;

class UpdateLeadtimeToShip extends Command
{
    /**
     * @var ShopUpdater
     */
    private $shopUpdater;

    /**
     * @param ShopUpdater $shopUpdater
     * @param string|null $name
     */
    public function __construct(
        ShopUpdater $shopUpdater,
        ?string $name = null
    ) {
        $this->shopUpdater = $shopUpdater;
        parent::__construct($name);
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName("mirakl:shop:update_leadtime_to_ship");
        $this->setDescription("Updating Lead Time To Ships in Shop");
        parent::configure();
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln(__('<info>Start - Updating Lead Time To Ships in Shop</info>'));
        $result = $this->shopUpdater->updateLeadtimeToShip();
        foreach ($result as $id => $item) {
            $output->writeln(__('<info>Shop id %1, change %2 to %3.</info>', $id, $item['old'], $item['new']));
        }
        $output->writeln(__('<info>Finish</info>'));
    }
}
