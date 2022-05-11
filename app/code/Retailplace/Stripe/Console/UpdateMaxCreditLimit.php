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
use Retailplace\Stripe\Model\Processing;
use Retailplace\MiraklMcm\Logger\Logger;
use Exception;
use Magento\Framework\App\State;
use Magento\Framework\App\Area;
use Magento\Framework\Exception\LocalizedException;

/**
 * Class UpdateMaxCreditLimit
 */
class UpdateMaxCreditLimit extends Command
{
    /**
     * Run specific order id
     * @type string
     */
    const RUN_PROCESS_OPTION_GROUP_ID = 'id';

    /**
     * Force - Run ignore pay date
     * @type string
     */
    const RUN_PROCESS_OPTION_ALL = 'all';

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
     * UpdateMaxCreditLimit constructor.
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
        $options = [
            new InputOption(
                self::RUN_PROCESS_OPTION_GROUP_ID,
                null,
                InputOption::VALUE_REQUIRED,
                'Execute a specific group id'
            ),
            new InputOption(
                self::RUN_PROCESS_OPTION_ALL,
                null,
                InputOption::VALUE_NONE,
                'update for all customer groups'
            )
        ];

        $this->setName('stripe:customer:update_max_credit_limit')
            ->setDescription('Updating the maximum credit limit 
            for clients who have selected "Use Default Max Credit Limit" = Yes')
            ->setDefinition($options);

        parent::configure();
    }

    /**
     * Execute Update Max Credit Limit Command
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @throws LocalizedException
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->processArea();

        $output->writeln(sprintf('<info>Start - Update Max Credit Limit</info>'));
        try {
            $groupId = null;

            if ($input->getOption(self::RUN_PROCESS_OPTION_ALL)) {
                $output->writeln('<info>Update All Customer Groups</info>');
            } else {
                if ($groupId = $input->getOption(self::RUN_PROCESS_OPTION_GROUP_ID)) {
                    $output->writeln(sprintf('<info>Update Customer Group id: %s</info>', $groupId));
                } else {
                    $output->writeln(sprintf('<error>Error: Not params</error>'));
                    $output->writeln(sprintf('<error>Please use (--all or --id=GROUP_ID)</error>'));
                    return;
                }
            }

            $this->processing = $this->processingFactory->create();
            $this->processing->updateCustomersCreditLimits($groupId);
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
