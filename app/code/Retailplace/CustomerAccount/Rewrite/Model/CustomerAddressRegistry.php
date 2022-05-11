<?php

/**
 * Retailplace_CustomerAccount
 *
 * @copyright   Copyright (c) 2021 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Dmitriy Fionov <dmitriy@tradesquare.com.au>
 */

namespace Retailplace\CustomerAccount\Rewrite\Model;

use Magento\Customer\Model\Address;
use Magento\Customer\Model\AddressRegistry;
use Magento\Framework\Exception\NoSuchEntityException;
use Retailplace\CustomerAccount\Block\Widget\BusinessName;

/**
 * Class CustomerAddressRegistry
 */
class CustomerAddressRegistry extends AddressRegistry
{
    /**
     * Get instance of the Address Model identified by id
     *
     * @param int $addressId
     * @return Address
     * @throws NoSuchEntityException
     */
    public function retrieve($addressId)
    {
        if (isset($this->registry[$addressId])) {
            return $this->registry[$addressId];
        }
        $address = $this->addressFactory->create();
        $address->load($addressId);
        if (!$address->getId()) {
            throw NoSuchEntityException::singleField('addressId', $addressId);
        }

        if (!$address->getCompany()) {
            $customer = $address->getCustomer();
            if ($customer) {
                $address->setCompany($customer->getData(BusinessName::ATTRIBUTE_CODE));
            }
        }

        $this->registry[$addressId] = $address;

        return $address;
    }
}
