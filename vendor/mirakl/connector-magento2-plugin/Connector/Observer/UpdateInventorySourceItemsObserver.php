<?php
namespace Mirakl\Connector\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Mirakl\Connector\Helper\Offer\MultiSourceInventory as InventoryHelper;
use Mirakl\Process\Model\Process;

class UpdateInventorySourceItemsObserver implements ObserverInterface
{
    /**
     * @var InventoryHelper
     */
    private $inventoryHelper;

    /**
     * @param InventoryHelper $inventoryHelper
     */
    public function __construct(InventoryHelper $inventoryHelper)
    {
        $this->inventoryHelper = $inventoryHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function execute(Observer $observer)
    {
        if ($process = $observer->getEvent()->getProcess()) {
            /** @var Process $process */
            $skus = $observer->getEvent()->getSkus();

            if (empty($skus)) {
                return;
            }

            $process->output(__('Updating multi source inventory...'), true);

            $this->inventoryHelper->updateOutOfStock($skus);
            $this->inventoryHelper->updateInStock($skus);

            $process->output(__('Updating inventory indexes...'), true);

            $this->inventoryHelper->updateIndexes($skus);

            $process->output(__('Done!'));
        }
    }
}