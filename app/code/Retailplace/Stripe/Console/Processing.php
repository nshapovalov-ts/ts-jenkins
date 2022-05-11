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
use Retailplace\Stripe\Model\Processing as ModelProcessing;
use Retailplace\MiraklMcm\Logger\Logger;
use Exception;
use Magento\Framework\App\State;
use Magento\Framework\App\Area;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\ObjectManagerInterface;

/**
 * Class Processing
 */
class Processing extends Command
{
    /**
     * Run specific order id
     * @type string
     */
    const RUN_PROCESS_OPTION_ORDER_ID = 'order_id';

    /**
     * Force - Run ignore pay date
     * @type string
     */
    const RUN_PROCESS_OPTION_FORCE = 'force';

    /**
     * Force Short - Run ignore pay date
     * @type string
     */
    const RUN_PROCESS_OPTION_FORCE_SHORT = 'f';

    /**
     * @var ModelProcessing
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
     * @var ObjectManagerInterface
     */
    private $objectManager;

    /**
     * ProductIntegrationReport constructor.
     *
     * @param State $state
     * @param ObjectManagerInterface $objectManager
     * @param Logger $logger
     * @param string|null $name
     */
    public function __construct(
        State $state,
        ObjectManagerInterface $objectManager,
        Logger $logger,
        string $name = null
    ) {
        $this->objectManager = $objectManager;
        $this->logger = $logger;
        $this->state = $state;
        parent::__construct($name);
    }

    /**
     * Configure CLI Command
     */
    protected function configure()
    {
        $options = [
            new InputOption(
                self::RUN_PROCESS_OPTION_ORDER_ID,
                null,
                InputOption::VALUE_REQUIRED,
                'Execute a specific order id'
            ),
            new InputOption(
                self::RUN_PROCESS_OPTION_FORCE,
                self::RUN_PROCESS_OPTION_FORCE_SHORT,
                InputOption::VALUE_NONE,
                'ignore pay date'
            )
        ];

        $this->setName('stripe:invoicing:pay')
            ->setDescription('Take all unpaid orders and paid them via Stripe')
            ->setDefinition($options);

        parent::configure();
    }

    /**
     * Execute Pay Invoicing Command
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return $this
     * @throws LocalizedException
     */
    protected function execute(InputInterface $input, OutputInterface $output): Processing
    {
        $this->processArea();

        $output->writeln(__("Start - Pay invoicing "));
        try {
            $params = [];

            if ($orderId = $input->getOption(self::RUN_PROCESS_OPTION_ORDER_ID)) {
                $output->writeln(sprintf('<info>Run Processing for order id: %s</info>', $orderId));
                $params['filters']['order_id'] = $orderId;
            }

            if ($input->getOption(self::RUN_PROCESS_OPTION_FORCE)) {
                $output->writeln('<info>Set Force Mode</info>');
                $params['force'] = true;
            }

            $this->processing = $this->objectManager->create(ModelProcessing::class);
            $this->processing->payInvoices($params);
        } catch (Exception $e) {
            $message = __('Error: %1', $e->getMessage());
            $this->logger->error($message);
            $output->writeln($message);
        }

        $output->writeln(__("End"));
        return $this;
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
