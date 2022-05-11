<?php

/**
 * Retailplace_MiraklConnector
 *
 * @copyright   Copyright (c) 2022 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Dmitriy Kosykh <dmitriykosykh@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\MiraklConnector\Console\Command;

use Magento\Framework\App\Area;
use Magento\Framework\App\State;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Magento\Framework\Registry;
use Retailplace\MiraklConnector\Model\ProductSynchronization;

class ProductSynchronizationCommand extends Command
{
    /** @var string */
    public const COMMAND_NAME = 'retailplace:mirakl-products:delete-unexisting';

    /**
     * @var Registry
     */
    private $registry;
    /**
     * @var ProductSynchronization
     */
    private $productSynchronization;
    /**
     * @var State
     */
    private $state;

    /**
     * @param Registry $registry
     * @param ProductSynchronization $productSynchronization
     * @param State $state
     * @param string|null $name
     */
    public function __construct(
        Registry $registry,
        ProductSynchronization $productSynchronization,
        State $state,
        string $name = null
    ) {
        $this->registry = $registry;
        $this->productSynchronization = $productSynchronization;
        $this->state = $state;

        parent::__construct($name);
    }

    /**
     * Configure CLI Command
     */
    protected function configure()
    {
        $this->setName(self::COMMAND_NAME);
        $this->setDescription('Synchronization product with mirakl');

        $this->setHelp(
            <<<HELP
Deletes products in magento that are not in Mirakl
HELP
        );

        parent::configure();
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return int|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            $start = microtime(true);
            $output->writeln(__('<info>Synchronization product with mirakl</info>'));
            $this->state->setAreaCode(Area::AREA_CRONTAB);
            $this->registry->register('isSecureArea', true);

            $output->writeln(__('<info>Get all products from mirakl</info>'));
            $this->productSynchronization->getAllProductFromMirakl();
            $output->writeln(__('<info>Finished get all products from mirakl</info>'));

            $output->writeln(__('<info>Removing simple products</info>'));
            $this->productSynchronization->deleteSimpleProducts();
            $output->writeln(__('<info>Finished removing simple products</info>'));


            $output->writeln(__('<info>Removing configurable products</info>'));
            $this->productSynchronization->deleteConfigurableWithoutChildren();
            $output->writeln(__('<info>Finished removing configurable products</info>'));

            $time = round(microtime(true) - $start, 2);
            $output->writeln("Execute time: $time");
        } catch (\Exception $e) {
            $output->writeln("ERROR: " . $e->getMessage());
        }
    }
}
