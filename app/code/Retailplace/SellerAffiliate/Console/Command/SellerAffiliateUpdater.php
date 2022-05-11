<?php

/**
 * Retailplace_SellerAffiliate
 *
 * @copyright   Copyright (c) 2022 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Evgeniy Derevyanko <evgeniy@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\SellerAffiliate\Console\Command;

use Exception;
use Magento\Framework\App\Area;
use Magento\Framework\App\State;
use Magento\Framework\Exception\LocalizedException;
use Retailplace\SellerAffiliate\Model\SellerAffiliateManagement;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class SellerAffiliateUpdater
 */
class SellerAffiliateUpdater extends Command
{
    /** @var string */
    public const COMMAND_NAME = 'retailplace:shop-affiliate:update';

    /** @var SellerAffiliateManagement */
    private $sellerAffiliateManagement;

    /** @var State */
    private $appState;

    /**
     * ShopAffiliateUpdater constructor
     *
     * @param SellerAffiliateManagement $sellerAffiliateManagement
     * @param State $appState
     * @param string|null $name
     */
    public function __construct(
        SellerAffiliateManagement $sellerAffiliateManagement,
        State $appState,
        string $name = null
    ) {
        $this->sellerAffiliateManagement = $sellerAffiliateManagement;
        $this->appState = $appState;
        parent::__construct($name);
    }

    /**
     * Configure CLI Command
     */
    protected function configure()
    {
        $this->setName(self::COMMAND_NAME);
        $this->setDescription('Update shop affiliated values');
        parent::configure();
    }

    /**
     * Execute Update Products Command
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @throws LocalizedException
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->processArea();
        $count = $this->sellerAffiliateManagement->updateShopAffiliates();
        $output->writeln(__('<info>%1 Records were updated.</info>', $count));
    }

    /**
     * Set Area Code for CLI
     *
     * @throws LocalizedException
     */
    private function processArea()
    {
        try {
            $this->appState->getAreaCode();
        } catch (Exception $e) {
            $this->appState->setAreaCode(Area::AREA_ADMINHTML);
        }
    }
}
