<?php
namespace Mirakl\Connector\Console\Command\Order;

use Magento\Framework\App\Area;
use Magento\Framework\App\State;
use Mirakl\Connector\Model\Order\Payment;
use Mirakl\Core\Console\Command\CommandTrait;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CollectDebitsCommand extends Command
{
    use CommandTrait;

    /**
     * @var State
     */
    private $appState;

    /**
     * @var Payment
     */
    private $payment;

    /**
     * @param   State       $state
     * @param   Payment     $payment
     * @param   string|null $name
     */
    public function __construct(State $state, Payment $payment, $name = null)
    {
        parent::__construct($name);
        $this->appState = $state;
        $this->payment = $payment;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('mirakl:order:collect-debits')
            ->setDescription('Collect all order debits that are in pending state in Mirakl');
    }

    /**
     * {@inheritdoc}
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $this->setAreaCode(Area::AREA_GLOBAL);
        $this->payment->collectDebits();
    }
}