<?php

namespace Magecomp\Smspro\Controller\Adminhtml\Bulk;

class Getnumber extends \Magento\Backend\App\Action
{
    protected $resultRedirect;
    protected $_customerFactory;
    protected $customerShippingAddress;

    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Magento\Framework\Controller\ResultFactory $result,
        \Magento\Customer\Model\ResourceModel\Customer\CollectionFactory $customerFactory,
        \Magento\Customer\Api\AddressRepositoryInterface $customerShippingAddress
    )
    {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
        $this->resultRedirect = $result;
        $this->_customerFactory = $customerFactory;
        $this->customerShippingAddress = $customerShippingAddress;
    }

    public function execute()
    {
        $customerCollection = $this->_customerFactory->create();
        $telephones = "";
        foreach ($customerCollection as $customer) {
            $shippingAddressId = $customer->getDefaultBilling();
            if ($shippingAddressId) {
                $telephones .= $this->customerShippingAddress->getById($shippingAddressId)->getTelephone() . ",";
            }

        }
        return $telephones;

    }

    protected function _isAllowed()
    {
        return true;
    }
}
