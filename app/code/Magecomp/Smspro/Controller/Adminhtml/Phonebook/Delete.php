<?php

namespace Magecomp\Smspro\Controller\Adminhtml\Phonebook;

use Magecomp\Smspro\Model\PhonebookFactory;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;

class Delete extends Action
{
    protected $_modelExtensionFactory;

    public function __construct( Context $context, PhonebookFactory $modelExtensionFactory )
    {
        $this->_modelExtensionFactory = $modelExtensionFactory;
        parent::__construct($context);
    }

    public function execute()
    {
        if ($this->getRequest()->getParam('id') > 0) {
            try {
                $zipcodeModel = $this->_modelExtensionFactory->create();
                $zipcodeModel->setId($this->getRequest()->getParam('id'))
                    ->delete();
                $this->messageManager->addSuccess('Phone Number deleted successfully.');
                $this->_redirect('*/*/');
            } catch (\Exception $e) {
                $this->messageManager->addError($e->getMessage());
                $this->_redirect('*/*/edit', ['id' => $this->getRequest()->getParam('id')]);
            }
        }
        $this->_redirect('*/*/');
    }
}
