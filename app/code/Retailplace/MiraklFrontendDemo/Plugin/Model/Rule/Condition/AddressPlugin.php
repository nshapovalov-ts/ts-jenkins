<?php

/**
 * Retailplace_MiraklFrontendDemo
 *
 * @copyright   Copyright (c) 2021 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Ruslan Mukhatov <ruslan@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\MiraklFrontendDemo\Plugin\Model\Rule\Condition;

use Magento\SalesRule\Model\Rule\Condition\Address;

class AddressPlugin
{
    /**
     * @param Address $subject
     * @param Address $result
     * @return Address
     */
    public function afterLoadAttributeOptions(
        Address $subject,
        Address $result
    ): Address {
        $attributes = $result->getAttributeOption();
        $attributes['base_subtotal_total_incl_tax'] = __('Subtotal (Incl. Tax)');

        $result->setAttributeOption($attributes);

        return $result;
    }
}
