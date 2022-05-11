<?php
namespace Magecomp\Smspro\Controller\Customer;
 
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;

class Updateotpnumber extends \Magento\Framework\App\Action\Action
{
    protected $smsmodel;
    protected $customersession;
    protected $customermodel;
    protected $customerresourcefactory;

    public function __construct(Context $context,
                                \Magecomp\Smspro\Model\SmsproFactory $smsmodel,
                                \Magento\Customer\Model\Session $customersession,
                                \Magento\Customer\Model\Customer $customermodel,
                                \Magento\Customer\Model\ResourceModel\CustomerFactory $customerresourcefactory)
    {
        $this->smsmodel = $smsmodel;
        $this->customersession = $customersession;
        $this->customermodel = $customermodel;
        $this->customerresourcefactory = $customerresourcefactory;
        parent::__construct($context);
    }

    public function execute()
    {
        try
        {
            $mobilenumber = $this->getRequest()->getParam('mobile');
            $otp = $this->getRequest()->getParam('otp');

            if($mobilenumber == '' || $mobilenumber == null || $otp == '' || $otp == null)
            {
                $data = array(__("Mobile Number & OTP Required For Verification."));
                $resultJson = $this->resultFactory->create(ResultFactory::TYPE_JSON);
                $resultJson->setData($data);
                return $resultJson;
            }

            $smsModel = $this->smsmodel->create();
            $smscollection = $smsModel->getCollection();
            $smscollection->addFieldToFilter('mobile_number', $mobilenumber)
                          ->addFieldToFilter('otp', $otp);

            foreach($smscollection as $smsdata)
            {
                $customerId = $this->customersession->getCustomer()->getId();

                $customer = $this->customermodel->load($customerId);
                $customerData = $customer->getDataModel();
                $customerData->setCustomAttribute('mobilenumber', $mobilenumber);
                $customer->updateData($customerData);
                $customerResource = $this->customerresourcefactory->create();
                $customerResource->saveAttribute($customer, 'mobilenumber');
                $customer->save();

                $smsdata->delete();

                $data = array("success");
                $resultJson = $this->resultFactory->create(ResultFactory::TYPE_JSON);
                $resultJson->setData($data);
                return $resultJson;
            }

            $data = array(__("Invalid OTP."));
            $resultJson = $this->resultFactory->create(ResultFactory::TYPE_JSON);
            $resultJson->setData($data);
            return $resultJson;
        }
        catch (\Exception $e)
        {
            $data = array("error");
            $resultJson = $this->resultFactory->create(ResultFactory::TYPE_JSON);
            $resultJson->setData($data);
            return $resultJson;
        }
    }
}