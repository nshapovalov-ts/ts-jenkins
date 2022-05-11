<?php

/**
 * Retailplace_ShippingAddressValidation
 *
 * @copyright   Copyright (c) 2022 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Evgeniy Derevyanko <evgeniy@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\ShippingAddressValidation\Plugin;

use Magento\Quote\Api\Data\AddressInterface;
use Magento\Quote\Api\ShipmentEstimationInterface;

/**
 * Class ShippingAddressValidation
 */
class ShippingAddressValidation
{
    /** @var string */
    public const PO_BOX = 'po box';

    /**
     * Validate shipping address
     *
     * @param ShipmentEstimationInterface $subject
     * @param callable $proceed
     * @param mixed $cartId
     * @param AddressInterface $address
     * @return array
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function aroundEstimateByExtendedAddress(
        ShipmentEstimationInterface $subject,
        callable $proceed,
        $cartId,
        AddressInterface $address
    ): array {
        $street = $address->getStreetFull();
        if (strripos($street, self::PO_BOX) !== false) {
            $result = [];
        } else {
            $result = $proceed($cartId, $address);
        }

        return $result;
    }
}
