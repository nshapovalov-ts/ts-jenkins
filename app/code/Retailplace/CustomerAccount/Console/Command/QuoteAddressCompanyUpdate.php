<?php

/**
 * Retailplace_CustomerAccount
 *
 * @copyright   Copyright (c) 2021 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Dmitriy Fionov <dmitriy@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\CustomerAccount\Console\Command;

use Exception;
use Magento\Framework\App\Area;
use Magento\Framework\App\State;
use Retailplace\CustomerAccount\Model\AddressCompanyManagement;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class QuoteAddressCompanyUpdate
 */
class QuoteAddressCompanyUpdate extends Command
{
    /** @var string */
    public const COMMAND_NAME = 'retailplace:quote-address:company-update';

    /** @var \Magento\Framework\App\State */
    private $state;

    /** @var \Retailplace\CustomerAccount\Model\AddressCompanyManagement */
    private $addressCompanyManagement;

    /**
     * QuoteAddressCompanyUpdate constructor.
     *
     * @param \Magento\Framework\App\State $state
     * @param \Retailplace\CustomerAccount\Model\AddressCompanyManagement $addressCompanyManagement
     * @param string|null $name
     */
    public function __construct(
        State $state,
        AddressCompanyManagement $addressCompanyManagement,
        string $name = null
    ) {
        $this->state = $state;
        $this->addressCompanyManagement = $addressCompanyManagement;
        parent::__construct($name);
    }

    /**
     * Configure CLI Command
     */
    protected function configure()
    {
        $this->setName(self::COMMAND_NAME);
        $this->setDescription('Update Quote Addresses Company from Customer Business Name Attribute.');

        parent::configure();
    }

    /**
     * Execute Update Groups Command
     *
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->processArea();

        $output->writeln(__('<info>Updating Quote Address Company...</info>'));
        $count = $this->addressCompanyManagement->update();
        $output->writeln(__('<info>%1 Addresses were updated.</info>', $count));
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
