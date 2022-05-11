<?php
namespace Magecomp\Smspro\Observer\Customer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Store\Model\StoreManagerInterface;

class RegisterSuccessObserver implements ObserverInterface
{
    protected $helperapi;
    protected $helpercustomer;
    protected $smsmodel;
    protected $emailfilter;
    protected $customerRepository;
    protected $_storeManager;
    protected $_customerModel;
    public function __construct(
        \Magecomp\Smspro\Helper\Apicall $helperapi,
        \Magecomp\Smspro\Helper\Customer $helpercustomer,
        \Magecomp\Smspro\Model\SmsproFactory $smsmodel,
        \Magento\Email\Model\Template\Filter $filter,
        \Magento\Customer\Model\Customer $customerModel,
        CustomerRepositoryInterface $customerRepository,
        StoreManagerInterface $storeManager)
    {
        $this->helperapi = $helperapi;
        $this->helpercustomer = $helpercustomer;
        $this->smsmodel = $smsmodel;
        $this->emailfilter = $filter;
        $this->_customerModel = $customerModel;
        $this->customerRepository = $customerRepository;
        $this->_storeManager = $storeManager;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        if(!$this->helpercustomer->isEnabled())
            return $this;

        $customer = $observer->getEvent()->getCustomer();
        $storeId = $this->_storeManager->getStore()->getId();

        $controller = $observer->getAccountController();
        $mobilenumber = $controller->getRequest()->getParam('mobilenumber');

        if($this->helpercustomer->isSignUpConfirmationForUser() &&  $controller->getRequest()->getParam('otp')){
            $customer->setCustomAttribute('ismobverify', 1);
            $this->customerRepository->save($customer);
        }
        $tempcustomer =  $this->_customerModel->load($customer->getId());
        $this->emailfilter->setVariables([
            'customer' => $tempcustomer,
            'mobilenumber' => $mobilenumber
        ]);

        if($this->helpercustomer->isSignUpNotificationForAdmin() && $this->helpercustomer->getAdminNumber($storeId))
        {
            $message = $this->helpercustomer->getSignUpNotificationForAdminTemplate();
            $dltid = $this->helpercustomer->getSignUpNotificationForAdminDltid();
            $finalmessage = $this->emailfilter->filter($message);
            $this->helperapi->callApiUrl($this->helpercustomer->getAdminNumber($storeId),$finalmessage,$dltid);
        }

        if($mobilenumber == '' || $mobilenumber == null)
            return $this;

        $smsModel = $this->smsmodel->create();
        $smscollection = $smsModel->getCollection()
                       ->addFieldToFilter('mobile_number', $mobilenumber);
        foreach ($smscollection as $sms)
        {
            $sms->delete();
        }

        if ($this->helpercustomer->isSignUpNotificationForUser())
        {
            $message = $this->helpercustomer->getSignUpNotificationForUserTemplate();
            $dltid = $this->helpercustomer->getSignUpConfirmationUserDltid();
            $finalmessage = $this->emailfilter->filter($message);
            $this->helperapi->callApiUrl($mobilenumber,$finalmessage,$dltid);
        }
        return $this;
    }
}
