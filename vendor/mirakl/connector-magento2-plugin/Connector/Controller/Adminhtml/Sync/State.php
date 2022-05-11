<?php
namespace Mirakl\Connector\Controller\Adminhtml\Sync;

use Mirakl\Connector\Controller\Adminhtml\AbstractSync;

class State extends AbstractSync
{
    /**
     * @see _isAllowed()
     */
    const ADMIN_RESOURCE = 'Mirakl_Config::sync';

    /**
     * Synchronize Mirakl offer states into Magento
     */
    public function execute()
    {
        try {
            if (!$this->checkConnectorEnabled()) {
                return $this->redirectReferer();
            }

            /** @var \Mirakl\Api\Helper\Offer $helper */
            $helper = $this->_objectManager->create('Mirakl\Api\Helper\Offer');
            $states = $helper->getStates();

            if (!$states || !$states->count()) {
                $this->messageManager->addErrorMessage(__('No offer condition found.'));
            } else {
                /** @var \Mirakl\Core\Model\ResourceModel\Offer\State $resource */
                $resource = $this->_objectManager->create('Mirakl\Core\Model\ResourceModel\Offer\State');
                $resource->synchronize($states);
                $this->messageManager->addSuccessMessage(__('Offer conditions have been synchronized successfully.'));
            }
        } catch (\Exception $e) {
            $this->logger->critical($e);
            $this->messageManager->addErrorMessage(
                __('An error occurred while synchronizing offer conditions (%1).', $e->getMessage())
            );
        }

        return $this->redirectReferer();
    }
}
