<?php
namespace Mirakl\Mcm\Controller\Adminhtml\Reset;

use Mirakl\Mcm\Controller\Adminhtml\AbstractController;

class Products extends AbstractController
{
    /**
     * Resets last synchronization date of MCM products import
     */
    public function execute()
    {
        $this->connectorConfig->resetSyncDate('mcm_products_import');

        $this->messageManager->addSuccessMessage(__('Last MCM products synchronization date has been reset successfully.'));

        return $this->redirectReferer();
    }
}
