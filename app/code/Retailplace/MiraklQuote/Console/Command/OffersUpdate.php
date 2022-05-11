<?php

/**
 * Retailplace_MiraklQuote
 *
 * @copyright   Copyright (c) 2022 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Dmitriy Fionov <dmitriy@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\MiraklQuote\Console\Command;

use Exception;
use Magento\Framework\App\Area;
use Magento\Framework\App\State;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Retailplace\MiraklQuote\Model\MiraklOfferUpdater;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class OffersUpdate
 */
class OffersUpdate extends Command
{
    /** @var string */
    public const COMMAND_NAME = 'retailplace:mirakl-quote:make-offers-quotable';
    public const INPUT_KEY_SELLER_IDS = 'sellers';

    /** @var \Magento\Framework\App\State */
    private $state;

    /** @var \Retailplace\MiraklQuote\Model\MiraklOfferUpdater */
    private $miraklSellerUpdater;

    /**
     * QuoteAddressCompanyUpdate constructor.
     *
     * @param \Magento\Framework\App\State $state
     * @param \Retailplace\MiraklQuote\Model\MiraklOfferUpdater $miraklSellerUpdater
     * @param string|null $name
     */
    public function __construct(
        State $state,
        MiraklOfferUpdater $miraklSellerUpdater,
        string $name = null
    ) {
        parent::__construct($name);

        $this->state = $state;
        $this->miraklSellerUpdater = $miraklSellerUpdater;
    }

    /**
     * Configure CLI Command
     */
    protected function configure()
    {
        $this->setName(self::COMMAND_NAME);

        $this->addOption(
            self::INPUT_KEY_SELLER_IDS,
            's',
            InputOption::VALUE_OPTIONAL,
            'Provide Seller IDs to Update'
        );

        $this->setDescription(
            'Mirakl Offers Updating to allow Quote Requests.'
        );

        parent::configure();
    }

    /**
     * Execute Update Sellers Command
     *
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->processArea();

        $shopIds = $input->getOption(self::INPUT_KEY_SELLER_IDS);
        if ($shopIds) {
            $shopIds = preg_split('/[, ]+/', $shopIds);
        }

        $output->writeln(__('<info>Start Mirakl Offers Updater for Allow Quote Requests field</info>'));
        $count = $this->miraklSellerUpdater->updateOffers($shopIds);
        $output->writeln(__('<info>Finish Updater. Offers updated: %1</info>', $count));
    }

    /**
     * Set Area Code for CLI
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function processArea()
    {
        try {
            $this->state->getAreaCode();
        } catch (Exception $e) {
            $this->state->setAreaCode(Area::AREA_FRONTEND);
        }
    }
}
