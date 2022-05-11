<?php
declare(strict_types=1);

/**
 * Retailplace_Gtm
 *
 * @copyright   Copyright (c) 2021 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Alexander Korsun <aleksandr@tradesquare.com.au>
 * @author      Ruslan Mukhatov <ruslan@tradesquare.com.au>
 */

namespace Retailplace\Gtm\Observer;

use Magento\Customer\Model\Session;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;

/**
 * Class CustomerSignup
 */
class CustomerSignup implements ObserverInterface
{
    /**
     * @var Session
     */
    protected $session;

    /**
     * @param Session $session
     */
    public function __construct(
        Session $session
    ) {
        $this->session = $session;
    }

    /**
     * @param Observer $observer
     * @return $this
     */
    public function execute(Observer $observer)
    {
        $customer = $observer->getEvent()->getCustomer();
        $customerId = $customer->getId();

        if (!empty($customerId)) {
            $dataLayer = $this->session->getCustomerAccountData();
            $dataLayer = empty($dataLayer) || !is_array($dataLayer) ? [] : $dataLayer;
            $dataLayer[] = [
                'event'      => 'AccountSignup',
                'eventLabel' => 'Account Signup'
            ];
            $this->session->setCustomerAccountData($dataLayer);
        }

        return $this;
    }
}
