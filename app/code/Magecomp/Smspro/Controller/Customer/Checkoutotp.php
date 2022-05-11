<?php
namespace Magecomp\Smspro\Controller\Customer;
 
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;

class Checkoutotp extends \Magento\Framework\App\Action\Action
{
    protected $helperapi;
    protected $helpercustomer;
    protected $smsmodel;
    protected $emailfilter;

    public function __construct(Context $context,
                                \Magecomp\Smspro\Helper\Apicall $helperapi,
                                \Magecomp\Smspro\Helper\Customer $helpercustomer,
                                \Magecomp\Smspro\Model\SmsproFactory $smsmodel,
                                \Magento\Email\Model\Template\Filter $filter)
    {
        $this->helperapi = $helperapi;
        $this->helpercustomer = $helpercustomer;
        $this->smsmodel = $smsmodel;
        $this->emailfilter = $filter;
        parent::__construct($context);
    }

    public function execute()
    {
        try
        {
            $mobilenumber = $this->getRequest()->getParam('mobile');
            $otp = $this->helpercustomer->getOtp();

            $this->emailfilter->setVariables(['otp' => $otp]);
            $message = $this->helpercustomer->getOrderConfirmationUserTemplate();
            $dltid = $this->helpercustomer->getOrderConfirmationUserDltid();
            $finalmessage = $this->emailfilter->filter($message);

            $responce = $this->helperapi->callApiUrl($mobilenumber,$finalmessage,$dltid);
            if($responce === true)
            {
                $smsModel = $this->smsmodel->create();
                $smscollection = $smsModel->getCollection();
                $smscollection->addFieldToFilter('mobile_number', $mobilenumber);

                if(count($smscollection)>0)
                {
                    $smsModel = $this->smsmodel->create()->load($mobilenumber,'mobile_number');
                }
                $smsModel->setMobileNumber($mobilenumber)
                        ->setOtp($otp)
                        ->setIsverify(0)
                        ->save();

                $data = ['success' => true, 'mobileNumber' => $mobilenumber];
                $resultJson = $this->resultFactory->create(ResultFactory::TYPE_JSON);
                $resultJson->setData($data);
                return $resultJson;
            }
            else
            {
                $resultJson = $this->resultFactory->create(ResultFactory::TYPE_JSON);
                $resultJson->setData($responce);
                return $resultJson;
            }
        }
        catch (\Exception $e) {
            $data = array("error");
            $resultJson = $this->resultFactory->create(ResultFactory::TYPE_JSON);
            $resultJson->setData($data);
            return $resultJson;
        }
    }
}