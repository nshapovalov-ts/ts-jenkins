<?php
namespace Mirakl\Mci\Controller\Adminhtml\Clear;

use Mirakl\Mci\Controller\Adminhtml\Clear;

class ValuesLists extends Clear
{
    /**
     * Reset operator attribute value lists from Mirakl platform
     */
    public function execute()
    {
        try {
            /** @var \Mirakl\Mci\Helper\ValueList $helper */
            $helper = $this->_objectManager->get(\Mirakl\Mci\Helper\ValueList::class);
            $importId = $helper->deleteAttributes();
            $this->messageManager->addSuccessMessage(
                __('Attribute value lists have been cleared successfully (%1).', $importId)
            );
        } catch (\Exception $e) {
            $this->logger->critical($e->getMessage());
            $this->messageManager->addErrorMessage(
                __('An error occurred while clearing attribute value lists (%1).', $e->getMessage())
            );
        }

        return $this->redirectReferer();
    }
}