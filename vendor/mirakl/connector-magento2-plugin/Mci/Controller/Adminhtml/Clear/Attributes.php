<?php
namespace Mirakl\Mci\Controller\Adminhtml\Clear;

use Mirakl\Mci\Controller\Adminhtml\Clear;

class Attributes extends Clear
{
    /**
     * Reset operator attributes from Mirakl platform
     */
    public function execute()
    {
        try {
            /** @var \Mirakl\Mci\Helper\Attribute $helper */
            $helper = $this->_objectManager->get(\Mirakl\Mci\Helper\Attribute::class);
            $importId = $helper->deleteAll();
            $this->messageManager->addSuccessMessage(
                __('Attributes have been cleared successfully (%1).', $importId)
            );
        } catch (\Exception $e) {
            $this->logger->critical($e->getMessage());
            $this->messageManager->addErrorMessage(
                __('An error occurred while clearing attributes (%1).', $e->getMessage())
            );
        }

        return $this->redirectReferer();
    }
}