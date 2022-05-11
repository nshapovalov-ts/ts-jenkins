<?php
namespace Magecomp\Smspro\Controller\Customer;
 
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;

class Checkoutotpnumber extends \Magento\Framework\App\Action\Action
{
    protected $smsmodel;
    protected $checkoutSession;

    public function __construct(Context $context,
                                \Magecomp\Smspro\Model\SmsproFactory $smsmodel,
                                \Magento\Checkout\Model\Session $checkoutSession)
    {
        $this->smsmodel = $smsmodel;
        $this->checkoutSession = $checkoutSession;
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
                $this->checkoutSession->setGuestOrderConfirm('1');
                $this->checkoutSession->setGuestOrderMobile($mobilenumber);

                $smsdata->delete();

                $data = ['success' => true];
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