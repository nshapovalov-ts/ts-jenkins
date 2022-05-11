<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Retailplace\CheckoutOverride\Plugin\Magento\Checkout\Block\Checkout;

use Magento\Store\Model\ScopeInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;

class LayoutProcessor
{
    const CHECKOUT_OPTIONS_STREET_LABEL = 'checkout/options/street_label';
    const CHECKOUT_OPTIONS_STREET_PLACEHOLDER = 'checkout/options/street_placeholder';
    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * LayoutProcessor constructor.
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(ScopeConfigInterface $scopeConfig)
    {
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * @param \Magento\Checkout\Block\Checkout\LayoutProcessor $subject
     * @param array $result
     * @return array
     */
    public function afterProcess(
        \Magento\Checkout\Block\Checkout\LayoutProcessor $subject,
        array $result
    ) {
        $streetLabel = __($this->getStreetLabel());
        $streetPlaceHolder = __($this->getStreetPlaceholder());

        $result['components']['checkout']['children']['steps']['children']['shipping-step']['children']
        ['shippingAddress']['children']['shipping-address-fieldset']['children']['street']['label'] = $streetLabel;
        $result['components']['checkout']['children']['steps']['children']['shipping-step']['children']
        ['shippingAddress']['children']['shipping-address-fieldset']['children']['street']['children'][0]['placeholder'] = $streetPlaceHolder;

        $result['components']['checkout']['children']['steps']['children']['billing-step']['children']
        ['payment']['children']['payments-list']['children']['free-form']['children']['form-fields']['children']['street']['label'] = $streetLabel;
        $result['components']['checkout']['children']['steps']['children']['billing-step']['children']
        ['payment']['children']['payments-list']['children']['free-form']['children']['form-fields']['children']['street']['children'][0]['placeholder'] = $streetPlaceHolder;
        return $result;
    }

    public function getStreetLabel()
    {
        return $this->scopeConfig->getValue(self::CHECKOUT_OPTIONS_STREET_LABEL, ScopeInterface::SCOPE_STORE);
    }

    public function getStreetPlaceholder()
    {
        return $this->scopeConfig->getValue(self::CHECKOUT_OPTIONS_STREET_PLACEHOLDER, ScopeInterface::SCOPE_STORE);
    }
}
