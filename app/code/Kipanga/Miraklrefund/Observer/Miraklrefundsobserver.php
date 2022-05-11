<?php
namespace Kipanga\Miraklrefund\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\ManagerInterface;

class Miraklrefundsobserver implements ObserverInterface
{
	/**
     * @var ManagerInterface
     */
    protected $eventManager;

    /**
     * @param   ManagerInterface    $eventManager
     */
    public function __construct(ManagerInterface $eventManager)
    {
        $this->eventManager = $eventManager;
    }

	/**
     * Method used by PSP to trigger order refund on an order by just dispatching an event
     * called 'mirakl_trigger_order_refund' with the following required parameters:
     *
     * increment_id: Magento order number
     * mirakl_order_id: Mirakl order id
     *
     * @param   Observer   $observer
     */
    public function execute(Observer $observer)
    {
        /** @var \Mirakl\MMP\FrontOperator\Domain\Collection\Payment\Refund\RefundOrderCollection $refunds */
        $refunds = $observer->getEvent()->getRefunds();

        foreach ($refunds as $refund) {
            // Custom operator code that will call the PSP to pay the refund

            /** @var \Mirakl\MMP\FrontOperator\Domain\Payment\Refund\RefundOrder $refund */
            // Dispatch order refund event that will call PA02 automatically

            $this->eventManager->dispatch(
                'mirakl_trigger_order_refund',
                [
                    'increment_id' => $refund->getOrderCommercialId(),
                    'mirakl_order_id' => $refund->getOrderId(),
                    'status' => 'OK', // 'OK' or 'REFUSED'
                ]
            );
        }
    }
}
