<?php
/**
 * Retailplace_MiraklMcm
 *
 * @copyright   Copyright (c) 2021 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Alexander Korsun <aleksandr@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\MiraklMcm\Console;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Mirakl\Mcm\Model\Product\Import\Handler\Csv as Import;
use Retailplace\MiraklMcm\Logger\Logger;
use Exception;
use Symfony\Component\Console\Input\InputOption;

/**
 * Class FindingAndReimportingFaultyProducts
 */
class FindingAndReimportingFaultyProducts extends Command
{

    /**
     * @var string
     */
    const RUN_PROCESS_OPTION_SKU = 'sku';

    /**
     * @var string
     */
    const RUN_PROCESS_SHORT_OPTION_SKU = 's';

    /**
     * @var Import
     */
    private $import;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * ProductIntegrationReport constructor.
     * @param Import $import
     * @param Logger $logger
     * @param string|null $name
     */
    public function __construct(
        Import $import,
        Logger $logger,
        string $name = null
    ) {
        parent::__construct($name);
        $this->import = $import;
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $options = [
            new InputOption(
                self::RUN_PROCESS_OPTION_SKU,
                self::RUN_PROCESS_SHORT_OPTION_SKU,
                InputOption::VALUE_REQUIRED,
                'Execute a specific simple sku (example --s=sku1,sku2,sku3,sku4,sku5)'
            )
        ];

        $this->setName('mirakl:mcm:product:re-importing-faulty-products')
            ->setDescription('Finding and re-importing faulty products')
            ->setDefinition($options);

        parent::configure();
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln(__("Start - Finding and re-importing faulty products"));
        $skus = [];
        try {
            if ($option = $input->getOption(self::RUN_PROCESS_OPTION_SKU)) {
                $option = trim($option);
                $skus = explode(',', $option);
                $output->writeln(sprintf('<info>Run Re-Import for produt sku: %s</info>', $option));
            }

            $this->import->reImportFaultyProducts($skus);
        } catch (Exception $e) {
            $message = __('Error: %s', $e->getMessage());
            $this->logger->error($message);
            $output->writeln($message);
        }

        $output->writeln(__("End"));
        return $this;
    }

}
