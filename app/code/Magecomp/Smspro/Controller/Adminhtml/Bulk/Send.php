<?php

namespace Magecomp\Smspro\Controller\Adminhtml\Bulk;

class Send extends \Magento\Backend\App\Action
{
    protected $resultRedirect;
    protected $helperapi;

    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Magento\Framework\Controller\ResultFactory $result,
        \Magecomp\Smspro\Helper\Apicall $helperapi
    )
    {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
        $this->resultRedirect = $result;
        $this->helperapi = $helperapi;
    }

    public function execute()
    {
        $mobileNumbers = $this->getRequest()->getParam('bulksms_numbers');
        $message = $this->getRequest()->getParam('bulksms_message');
        if (!empty($mobileNumbers) && !empty($message)) {

            $mobileArray = explode(",", $mobileNumbers);
            $i = 0;
            $notereceived = "";

            foreach ($mobileArray as $mobilenumber) {
                if (!empty($mobilenumber)) { //If customer put "," at last so we should not sent message.
                    $apiResponse = $this->helperapi->callApiUrl($mobilenumber, $message);
                    if ($apiResponse === true) {
                        $i++;
                    } else {
                        $notereceived = $mobilenumber . ",";
                    }
                }
            }
            if (!empty($notereceived)) {
                $this->messageManager->addError($notereceived . ' Has some problem, Please resend.');
            }
            $this->messageManager->addSuccess($i . ' SMS Sent Successfully.');
        } else {
            $this->messageManager->addError('MobileNumber Or Message is not valid, or Blank');
        }
        $storeId = $this->getRequest()->getParam('store');
        $this->_redirect("*/*/", ['store' => $storeId]);
        return;

    }

    protected function _isAllowed()
    {
        return true;
    }
}