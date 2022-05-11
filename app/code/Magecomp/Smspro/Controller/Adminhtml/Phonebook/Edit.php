<?php

namespace Magecomp\Smspro\Controller\Adminhtml\Phonebook;

use Magento\Backend\App\Action;

class Edit extends \Magento\Backend\App\Action
{
    protected $_coreRegistry = null;
    protected $resultPageFactory;

    public function __construct(
        Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Magento\Framework\Registry $registry
    )
    {
        $this->resultPageFactory = $resultPageFactory;
        $this->_coreRegistry = $registry;
        parent::__construct($context);
    }

    public function execute()
    {
        $id = $this->getRequest()->getParam('id');
        $model = $this->_objectManager->create('Magecomp\Smspro\Model\Phonebook');

        if ($id) {
            $model->load($id);
            if (!$model->getId()) {
                $this->messageManager->addError(__('This entry no longer exists.'));
                $resultRedirect = $this->resultRedirectFactory->create();
                return $resultRedirect->setPath('*/*/');
            }
        }

        $data = $this->_objectManager->get('Magento\Backend\Model\Session')->getFormData(true);
        if (!empty($data)) {
            $model->setData($data);
        }
        $this->_coreRegistry->register('phonebook', $model);
        $resultPage = $this->_initAction();
        $title = $id ? __('Edit Entry') : __('New Entry');
        $resultPage->addBreadcrumb(
            $id ? __('Edit Entry') : $title,
            $id ? __('Edit Entry') : $title
        );
        $resultPage->getConfig()->getTitle()->prepend($title);

        return $resultPage;
    }

    protected function _initAction()
    {
        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('Magecomp_Smspro::phonebook')
            ->addBreadcrumb(__('Magecomp'), __('Magecomp'))
            ->addBreadcrumb(__('Manage Entry'), __('Manage Entry'));
        return $resultPage;
    }

    protected function _isAllowed()
    {
        return true;
    }
}
