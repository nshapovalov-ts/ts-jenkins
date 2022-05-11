<?php
namespace Magecomp\Smspro\Observer\Customer;

use Magento\Framework\Event\ObserverInterface;

class CheckUserCreateObserver implements ObserverInterface
{
    protected $messageManager;
    protected $session;
    protected $_urlManager;
    protected $redirect;
    protected $helpercustomer;
    protected $smsmodel;
    protected $_responseFactory;

    public function __construct(
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Magento\Framework\Session\SessionManagerInterface $session,
        \Magento\Framework\UrlInterface $urlManager,
        \Magento\Framework\App\Response\RedirectInterface $redirect,
        \Magecomp\Smspro\Helper\Customer $helpercustomer,
        \Magecomp\Smspro\Model\SmsproFactory $smsmodel,
        \Magento\Framework\App\ResponseFactory $responseFactory
    ) {

        $this->messageManager = $messageManager;
        $this->session = $session;
        $this->_urlManager = $urlManager;
        $this->redirect = $redirect;
        $this->helpercustomer = $helpercustomer;
        $this->smsmodel = $smsmodel;
        $this->_responseFactory = $responseFactory;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        if ($this->helpercustomer->isSignUpConfirmationForUser()) {
            try {
                $postdata = $observer->getRequest()->getPost();
                $finalnumber = $postdata['countryreg'].$postdata['mobilenumber'];
                $smsModel = $this->smsmodel->create();
                $smscollection = $smsModel->getCollection()
                    ->addFieldToFilter('mobile_number', $finalnumber)
                    ->addFieldToFilter('otp', $postdata['otp']);
                if (count($smscollection) > 0)
                    return $this;
                else
                    $this->messageManager->addError(__('Invalid OTP.'));
            } catch (\Exception $e) {
                $this->messageManager->addError($e->getMessage());
            }
            $url = $this->_urlManager->getUrl('*/*/create', ['_secure' => true]);
            $this->_responseFactory->create()->setRedirect($url)->sendResponse();
            $this->setRefererUrl($url);
        }
        return $this;
    }
}
