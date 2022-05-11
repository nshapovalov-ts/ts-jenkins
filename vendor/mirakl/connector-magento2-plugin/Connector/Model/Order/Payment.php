<?php
namespace Mirakl\Connector\Model\Order;

use Magento\Framework\Event\ManagerInterface as EventManagerInterface;
use Mirakl\Api\Helper\Payment as PaymentApi;

class Payment
{
    /**
     * @var EventManagerInterface
     */
    protected $eventManager;

    /**
     * @var PaymentApi
     */
    protected $paymentApi;

    /**
     * @param   EventManagerInterface   $eventManager
     * @param   PaymentApi              $paymentApi
     */
    public function __construct(EventManagerInterface $eventManager, PaymentApi $paymentApi)
    {
        $this->eventManager = $eventManager;
        $this->paymentApi = $paymentApi;
    }

    /**
     * Collect all order debits and send them in a specific event so they are sent to the PSP by the operator
     *
     * @return  void
     */
    public function collectDebits()
    {
        $this->eventManager->dispatch('mirakl_customer_debit_list', [
            'debits' => $this->paymentApi->getAllOrderDebits()
        ]);
    }

    /**
     * Collect all order refunds and send them in a specific event so they are sent to the PSP by the operator
     *
     * @return  void
     */
    public function collectRefunds()
    {
        $this->eventManager->dispatch('mirakl_customer_refund_list', [
            'refunds' => $this->paymentApi->getAllOrderRefunds()
        ]);
    }
}