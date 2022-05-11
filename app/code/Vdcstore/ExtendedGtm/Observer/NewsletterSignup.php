<?php

/**
 * Vdcstore_ExtendedGtm
 *
 * @copyright   Copyright (c) 2021 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Ruslan Mukhatov <ruslan@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Vdcstore\ExtendedGtm\Observer;

use Magento\Customer\Model\Session;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;
use Vdcstore\ExtendedGtm\Rewrite\WeltPixel\GoogleTagManager\Helper\Data;

class NewsletterSignup implements ObserverInterface
{
    /**
     * @var Data
     */
    protected $helper;

    /**
     * @var Session
     */
    private $customerSession;

    /**
     * @param Data $helper
     * @param Session $customerSession
     */
    public function __construct(
        Data $helper,
        Session $customerSession
    ) {
        $this->helper = $helper;
        $this->customerSession = $customerSession;
    }

    /**
     * @param Observer $observer
     * @return self
     */
    public function execute(Observer $observer)
    {
        if (!$this->helper->isEnabled()) {
            return $this;
        }

        $email = $observer->getEvent()->getSubscriber()->getSubscriberEmail();
        $newsletterData = $this->helper->newsletterSubscriptionPushData($email);
        $this->customerSession->setNewsLetterSubscriptionData($newsletterData);

        return $this;
    }
}
