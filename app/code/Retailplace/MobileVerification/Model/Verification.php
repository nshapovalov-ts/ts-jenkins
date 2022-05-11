<?php

/**
 * Retailplace_MobileVerification
 *
 * @copyright   Copyright (c) 2021 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Alexander Korsun <aleksandr@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\MobileVerification\Model;

use Magento\Customer\Model\Session;

class Verification
{

    /**
     * @var Session
     */
    private $customerSession;

    /**
     * PayInvoices constructor.
     *
     * @param Session $customerSession
     */
    public function __construct(
        Session $customerSession
    ) {
        $this->customerSession = $customerSession;
    }

    /**
     * Is Customer Phone Number Confirmed
     *
     * @return bool
     */
    public function isCustomerPhoneNumberConfirmed()
    {
        //load info from customer
        $customer = $this->customerSession->getCustomer();
        if (empty($customer)) {
            return false;
        }
        $datePhoneNumberConfirmed = $customer->getDatePhoneNumberConfirmed();
        if (!empty($datePhoneNumberConfirmed)) {
            return true;
        }

        return false;
    }
}
