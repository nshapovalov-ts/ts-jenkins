<?php
/**
 * Magento Magecomp_Smspro extension
 *
 * @category   Magecomp
 * @package    Magecomp_Smspro
 * @author     Magecomp
 */

namespace Magecomp\Smspro\Controller\Adminhtml\Send;


class Invoicecustom extends \Magento\Backend\App\Action
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

        $invoiceid = $this->getRequest()->getParam('current_invoice_id');
        $mobilenumber = $this->getRequest()->getParam('customsms_mob');
        $message = $this->getRequest()->getParam('customsms_message');
        $dltid = $this->getRequest()->getParam('dltid');
        try {
            $apiResponse = $this->helperapi->callApiUrl($mobilenumber, $message ,$dltid);

            if ($apiResponse === true) {
                $this->getMessageManager()->addSuccess("SMS Sent Successfully to the Customer Mobile : " . $mobilenumber);
            } else {
                $this->getMessageManager()->addError("Something Went Wrong While sending SMS");
            }

            $storeId = $this->getRequest()->getParam('store');
            $this->_redirect("sales/invoice/view/invoice_id/" . $invoiceid, ['store' => $storeId]);
            return;
        } catch (\Exception $e) {
            $this->getMessageManager()->addError("There is some Technical problem, Please tray again");
            $storeId = $this->getRequest()->getParam('store');
            $this->_redirect("sales/invoice/view/invoice_id/" . $invoiceid, ['store' => $storeId]);
            return;
        }

    }

    protected function _isAllowed()
    {
        return true;
    }
}