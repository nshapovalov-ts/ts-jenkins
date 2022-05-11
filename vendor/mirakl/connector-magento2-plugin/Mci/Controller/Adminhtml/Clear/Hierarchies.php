<?php
namespace Mirakl\Mci\Controller\Adminhtml\Clear;

use Mirakl\Mci\Controller\Adminhtml\Clear;

class Hierarchies extends Clear
{
    /**
     * Reset operator hierarchies from Mirakl platform
     */
    public function execute()
    {
        try {
            /** @var \Mirakl\Mci\Helper\Hierarchy $helper */
            $helper = $this->_objectManager->get(\Mirakl\Mci\Helper\Hierarchy::class);
            $importId = $helper->deleteAll();
            $this->messageManager->addSuccessMessage(
                __('Catalog categories have been cleared successfully (%1).', $importId)
            );
        } catch (\Exception $e) {
            $this->logger->critical($e->getMessage());
            $this->messageManager->addErrorMessage(
                __('An error occurred while clearing Catalog categories (%1).', $e->getMessage())
            );
        }

        return $this->redirectReferer();
    }
}