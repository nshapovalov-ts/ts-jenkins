<?php

/**
 * Retailplace_MiraklConnector
 *
 * @copyright   Copyright (c) 2021 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Alexander Korsun <aleksandr@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\MiraklConnector\Console\Command;

use Magento\Framework\Exception\LocalizedException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Retailplace\MiraklConnector\Model\TaxClassIdAttributeUpdater;
use Magento\Framework\App\State;
use Symfony\Component\Console\Input\InputOption;
use Magento\Framework\App\Area;
use Exception;

/**
 * Class UpdateAttributeTaxClassId
 */
class UpdateAttributeTaxClassId extends Command
{
    /** @var string */
    public const COMMAND_NAME = 'retailplace:attribute-tax-class-id:update';

    /**
     * Run specific sku
     * @type string
     */
    const RUN_PROCESS_OPTION_SKU = 'sku';

    /**
     * @var TaxClassIdAttributeUpdater
     */
    private $taxClassIdAttributeUpdater;

    /**
     * @var State
     */
    private $state;

    /**
     * Update Attribute Tax Class Id constructor.
     *
     * @param State $state
     * @param TaxClassIdAttributeUpdater $taxClassIdAttributeUpdater
     * @param string|null $name
     */
    public function __construct(
        State $state,
        TaxClassIdAttributeUpdater $taxClassIdAttributeUpdater,
        string $name = null
    ) {
        $this->state = $state;
        $this->taxClassIdAttributeUpdater = $taxClassIdAttributeUpdater;
        parent::__construct($name);
    }

    /**
     * Configure CLI Command
     */
    protected function configure()
    {
        $options = [
            new InputOption(
                self::RUN_PROCESS_OPTION_SKU,
                null,
                InputOption::VALUE_REQUIRED,
                "Execute a specific sku"
            )
        ];

        $this->setName(self::COMMAND_NAME);
        $this->setDescription('Update product attribute tax class id');
        $this->setDefinition($options);
        $this->setHelp(
            <<<HELP
Update the attribute tax_class_id for all products or for some products.

example:
--sku=SKU
--sku='SKU,SKU,SKU'
HELP
        );

        parent::configure();
    }

    /**
     * Execute Update Attribute Tax Class Id
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @throws LocalizedException
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->processArea();
        $start = strtotime("now");
        $output->writeln(__('<info>Update the attribute tax_class_id...</info>'));

        $skus = [];
        if ($params = $input->getOption(self::RUN_PROCESS_OPTION_SKU)) {
            $skus = array_map(function ($v) {
                return trim($v);
            }, explode(',', $params));
            $output->writeln(sprintf('<info>Run Update the attribute for skus: %s</info>', implode(',', $skus)));
        }

        $this->taxClassIdAttributeUpdater->update($skus);
        $end = strtotime("now");
        $output->writeln(__('<info>Update the attribute have been updated.</info>'));

        $output->writeln(__(
            '<info>Completed in %1 seconds</info>',
            ($end - $start)
        ));
    }

    /**
     * Set Area Code for CLI
     *
     * @throws LocalizedException
     */
    protected function processArea()
    {
        try {
            $this->state->getAreaCode();
        } catch (Exception $e) {
            $this->state->setAreaCode(Area::AREA_CRONTAB);
        }
    }
}
