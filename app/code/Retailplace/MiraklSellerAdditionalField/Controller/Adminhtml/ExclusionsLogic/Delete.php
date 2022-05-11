<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Retailplace\MiraklSellerAdditionalField\Controller\Adminhtml\ExclusionsLogic;

class Delete extends \Retailplace\MiraklSellerAdditionalField\Controller\Adminhtml\ExclusionsLogic
{

    /**
     * Delete action
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();
        // check if we know what should be deleted
        $id = $this->getRequest()->getParam('exclusionslogic_id');
        if ($id) {
            try {
                // init model and delete
                $model = $this->_objectManager->create(\Retailplace\MiraklSellerAdditionalField\Model\ExclusionsLogic::class);
                $model->load($id);
                $model->delete();
                // display success message
                $this->messageManager->addSuccessMessage(__('You deleted the Exclusionslogic.'));
                // go to grid
                return $resultRedirect->setPath('*/*/');
            } catch (\Exception $e) {
                // display error message
                $this->messageManager->addErrorMessage($e->getMessage());
                // go back to edit form
                return $resultRedirect->setPath('*/*/edit', ['exclusionslogic_id' => $id]);
            }
        }
        // display error message
        $this->messageManager->addErrorMessage(__('We can\'t find a Exclusionslogic to delete.'));
        // go to grid
        return $resultRedirect->setPath('*/*/');
    }
}

