<?php
namespace Kipanga\Miraklrefund\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\ManagerInterface;

class Mirakldebitsobserver implements ObserverInterface
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
	
	 
    public function execute(Observer $observer)
    {
        
        $refunds = $observer->getEvent()->getRefunds();
 
        /*foreach ($refunds as $refund) { 
            
            $this->eventManager->dispatch('mirakl_trigger_order_debit', [
				'order_id'  => $orderId,
				'remote_id' => $remoteId,
				'status' => 'OK', // default status is 'OK' if not specified, also accepts 'REFUSED'
				'transaction_number' = $transactionNumber, // Optional
				'transaction_date' = $transactionDate, // Optional
			]);
        }*/
    } 
}