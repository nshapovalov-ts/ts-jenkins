<?php
namespace Magecomp\Smspro\Observer\Customer;

use Magento\Framework\Event\ObserverInterface;

class ContactPostObserver implements ObserverInterface
{
    protected $helperapi;
    protected $helpercontact;
    protected $emailfilter;
    protected $storeManager;

    public function __construct(
        \Magecomp\Smspro\Helper\Apicall $helperapi,
        \Magecomp\Smspro\Helper\Contact $helpercontact,
        \Magento\Email\Model\Template\Filter $filter,
        \Magento\Store\Model\StoreManagerInterface $storeManager)
    {
        $this->helperapi = $helperapi;
        $this->helpercontact = $helpercontact;
        $this->emailfilter = $filter;
        $this->storeManager = $storeManager;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        if(!$this->helpercontact->isEnabled())
            return $this;

        $request = $observer->getRequest();
        $name = $request->getParam('name');
        $email = $request->getParam('email');
        $telephone = $request->getParam('telephone');
        $comment = $request->getParam('comment');

        $this->emailfilter->setVariables([
            'name' => $name,
            'email' => $email,
            'telephone' => $telephone,
            'comment' => $comment,
            'store_name' => $this->helpercontact->getStoreName()
        ]);

        if ($this->helpercontact->isContactNotificationForUser())
        {
            $message = $this->helpercontact->getContactNotificationUserTemplate();
            $dltid = $this->helpercontact->getContactNotificationUserDltid();
            $finalmessage = $this->emailfilter->filter($message);
            $this->helperapi->callApiUrl($telephone,$finalmessage,$dltid);
        }
        $storeId = $this->storeManager->getStore()->getId();
        if($this->helpercontact->isContactNotificationForAdmin() && $this->helpercontact->getAdminNumber($storeId))
        {
            $message = $this->helpercontact->getContactNotificationForAdminTemplate();
            $dltid = $this->helpercontact->getContactNotificationForAdminDltid();
            $finalmessage = $this->emailfilter->filter($message);
            $this->helperapi->callApiUrl($this->helpercontact->getAdminNumber($storeId),$finalmessage,$dltid);
        }

        return $this;
    }
}