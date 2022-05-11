<?php

namespace Magecomp\Smspro\Controller\Adminhtml\Bulk;

class Getphonebooknumber extends \Magento\Backend\App\Action
{
    protected $resultRedirect;
    protected $_phonebookFactory;


    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Magento\Framework\Controller\ResultFactory $result,
        \Magecomp\Smspro\Model\ResourceModel\Phonebook\CollectionFactory $phonebookFactory

    )
    {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
        $this->resultRedirect = $result;
        $this->_phonebookFactory = $phonebookFactory;

    }

    public function execute()
    {
        $customerCollection = $this->_phonebookFactory->create();
        $telephones = "";
        foreach ($customerCollection as $customer) {
            $telephones .= $customer->getMobile() . ",";
        }
        return $telephones;
    }

    protected function _isAllowed()
    {
        return true;
    }
}
