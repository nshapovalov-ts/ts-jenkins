<?php
/**
 * Retailplace_Stripe
 *
 * @copyright   Copyright (c) 2022 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Alexander Korsun <aleksandr@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\Stripe\Console;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Retailplace\Stripe\Model\ProcessingFactory;
use Retailplace\MiraklMcm\Logger\Logger;
use Exception;
use Magento\Framework\App\State;
use Magento\Framework\App\Area;
use Magento\Framework\Exception\LocalizedException;
use Retailplace\Stripe\Model\Processing;

/**
 * Class UpdatePaymentsCreditCardInfo
 */
class UpdatePaymentsCreditCardInfo extends Command
{
    /**
     * @var Processing
     */
    private $processing;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var State
     */
    private $state;

    /**
     * @var ProcessingFactory
     */
    private $processingFactory;

    /**
     * UpdatePaymentsCreditCardInfo constructor.
     *
     * @param State $state
     * @param Logger $logger
     * @param ProcessingFactory $processingFactory
     * @param string|null $name
     */
    public function __construct(
        State $state,
        Logger $logger,
        ProcessingFactory $processingFactory,
        string $name = null
    ) {
        $this->logger = $logger;
        $this->state = $state;
        $this->processingFactory = $processingFactory;
        parent::__construct($name);
    }

    /**
     * Configure CLI Command
     */
    protected function configure()
    {
        $this->setName('stripe:payments:update_credit_card_info')
            ->setDescription('Updating of credit card info in payment');

        parent::configure();
    }

    /**
     * Execute Update Credit Card Info Command
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @throws LocalizedException
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->processArea();

        $output->writeln(sprintf('<info>Start - Updating of credit card info in payment</info>'));
        try {
            $this->processing = $this->processingFactory->create();
            $this->processing->updateCreditCardInfo(true);
        } catch (Exception $e) {
            $message = __('Error: %1', $e->getMessage());
            $this->logger->error($message);
            $output->writeln($message);
        }

        $output->writeln(sprintf('<info>End</info>'));
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
