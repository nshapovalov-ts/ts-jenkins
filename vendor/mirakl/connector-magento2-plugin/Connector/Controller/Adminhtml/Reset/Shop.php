<?php
namespace Mirakl\Connector\Controller\Adminhtml\Reset;

use Mirakl\Connector\Controller\Adminhtml\AbstractReset;

class Shop extends AbstractReset
{
    /**
     * Resets last synchronization date of shops
     */
    public function execute()
    {
        $this->connectorConfig->resetSyncDate('shops');

        $this->messageManager->addSuccessMessage(__('Last shops synchronization date has been reset successfully.'));

        return $this->redirectReferer();
    }
}
