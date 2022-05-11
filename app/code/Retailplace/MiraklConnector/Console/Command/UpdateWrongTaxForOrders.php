<?php
/**
 * Retailplace_MiraklConnector
 *
 * @copyright   Copyright (c) 2022 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Alexander Korsun <aleksandr@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\MiraklConnector\Console\Command;

use Symfony\Component\Console\Command\Command;
use Magento\Framework\App\State;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\App\Area;
use Exception;
use Retailplace\MiraklConnector\Model\CalculateTaxWrongedOrders;

/**
 * Class UpdateWrongTaxForOrders
 */
class UpdateWrongTaxForOrders extends Command
{

    /** @var string */
    public const COMMAND_NAME = 'retailplace:wrong_tax_for_orders:update';

    /**
     * Run specific order_id
     * @type string
     */
    const RUN_PROCESS_OPTION_ORDER_ID = 'order_id';

    /**
     * Soft Run
     * @type string
     */
    const RUN_PROCESS_OPTION_SOFT_RUN = 'soft';

    /**
     * @var State
     */
    private $state;

    /**
     * @var CalculateTaxWrongedOrders
     */
    private $calculateTaxWrongedOrders;

    /**
     * @param State $state
     * @param CalculateTaxWrongedOrders $calculateTaxWrongedOrders
     * @param string|null $name
     */
    public function __construct(
        State $state,
        CalculateTaxWrongedOrders $calculateTaxWrongedOrders,
        string $name = null
    ) {
        $this->state = $state;
        $this->calculateTaxWrongedOrders = $calculateTaxWrongedOrders;
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
                "Execute a specific order id"
            ),
            new InputOption(
                self::RUN_PROCESS_OPTION_SOFT_RUN,
                null,
                InputOption::VALUE_NONE,
                "Show orders requiring processing"
            )
        ];

        $this->setName(self::COMMAND_NAME);
        $this->setDescription('Update wrong tax for orders');
        $this->setDefinition($options);
        $this->setHelp(
            <<<HELP
        Update wrong Tax class for Order items and other entities.

        example:
        --order_id=XXXX
        --order_id='XXX1,XXX2,XXX3'
        --soft
HELP
        );

        parent::configure();
    }

    /**
     * Execute update wrong tax for orders
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @throws LocalizedException
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->processArea();
        $start = strtotime("now");
        $output->writeln(__('<info>Update wrong tax for orders...</info>'));

        $ids = [];
        if ($params = $input->getOption(self::RUN_PROCESS_OPTION_ORDER_ID)) {
            $ids = array_map(function ($v) {
                return trim($v);
            }, explode(',', $params));
            $output->writeln(sprintf('<info>Run Update wrong tax for orders: %s</info>', implode(',', $ids)));
        }

        $isSoftRun = (bool) $input->getOption(self::RUN_PROCESS_OPTION_SOFT_RUN);

        $this->calculateTaxWrongedOrders->update($ids, $isSoftRun);
        $end = strtotime("now");
        $output->writeln(__('<info>Update wrong tax for orders has been completed.</info>'));

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
