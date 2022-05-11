<?php

/**
 * Retailplace_MiraklConnector
 *
 * @copyright   Copyright (c) 2021 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Ruslan Mukhatov <ruslan@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\MiraklConnector\Console\Command;

use Magento\Framework\Exception\LocalizedException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Retailplace\MiraklConnector\Rewrite\Helper\Offer\Catalog as OfferCatalogHelper;

/**
 * Class UpdateMiraklAttributesCommand
 */
class UpdateMiraklAttributesCommand extends Command
{
    /** @var string */
    public const COMMAND_NAME = 'retailplace:mirakl-attributes:update';

    /** @var OfferCatalogHelper */
    private $offerCatalogHelper;

    /**
     * UpdateMiraklAttributesCommand constructor.
     *
     * @param OfferCatalogHelper $offerCatalogHelper
     * @param string|null $name
     */
    public function __construct(
        OfferCatalogHelper $offerCatalogHelper,
        string $name = null
    ) {
        $this->offerCatalogHelper = $offerCatalogHelper;
        parent::__construct($name);
    }

    /**
     * Configure CLI Command
     */
    protected function configure()
    {
        $this->setName(self::COMMAND_NAME);
        $this->setDescription('Update product attributes based on offer and shop data');

        $this->setHelp(
            <<<HELP
Update all product attributes from the corresponding fields of offers and shops.

All products will be updated.
HELP
        );

        parent::configure();
    }

    /**
     * Execute Update Mirakl Attributes Command
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @throws LocalizedException
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln(__('<info>Updating Mirakl attributes...</info>'));

        $this->offerCatalogHelper->updateAttributes();

        $output->writeln(__('<info>Mirakl attributes have been updated.</info>'));
    }
}
