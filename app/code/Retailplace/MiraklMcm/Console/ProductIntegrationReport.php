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

class ProductIntegrationReport extends Command
{
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
        $this->setName('mirakl:mcm:product:integration-report')
            ->setDescription('Sending product integration report to Mirakl');

        parent::configure();
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln(__("Start - Sending product integration report to Mirakl"));
        try {
            $this->import->sendReport();
        } catch (Exception $e) {
            $message = __('Error: %s', $e->getMessage());
            $this->logger->error($message);
            $output->writeln($message);
        }

        $output->writeln(__("End"));
        return $this;
    }
}
