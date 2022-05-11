<?php
namespace Mirakl\Core\Controller\Adminhtml\Document\Type;

use Magento\Framework\Exception\LocalizedException;
use Mirakl\Core\Controller\Adminhtml\Document\Type;

class Save extends Type
{
    /**
     * {@inheritdoc}
     */
    public function execute()
    {
        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();
        $data = $this->getRequest()->getPostValue();
        if ($data) {
            /** @var \Magento\Cms\Model\Block $model */
            $model = $this->_objectManager->create(\Mirakl\Core\Model\Document\Type::class);

            $id = $this->getRequest()->getParam('id');
            if ($id) {
                $this->_documentTypeResourceFactory->create()->load($model, $id);
                if ($id != $model->getId()) {
                    $this->messageManager->addErrorMessage(__('This document type no longer exists.'));
                    return $resultRedirect->setPath('*/*/');
                }
            }

            $this->_eventManager->dispatch(
                'adminhtml_controller_document_type_prepare_save',
                ['request' => $this->getRequest()]
            );

            $model->setData($data);
        
            try {
                $this->_documentTypeResourceFactory->create()->save($model);
                $this->messageManager->addSuccessMessage(__('You saved the document type.'));
        
                if ($this->getRequest()->getParam('back')) {
                    return $resultRedirect->setPath('*/*/edit', ['id' => $model->getId()]);
                }

                return $resultRedirect->setPath('*/*/');
            } catch (LocalizedException $e) {
                $this->messageManager->addErrorMessage($e->getMessage());
            } catch (\Exception $e) {
                $this->messageManager
                    ->addExceptionMessage($e, __('Something went wrong while saving the document type.'));
            }
        
            return $resultRedirect->setPath('*/*/edit', ['id' => $this->getRequest()->getParam('id')]);
        }

        return $resultRedirect->setPath('*/*/');
    }
}
