<?php

/**
 * Retailplace_CustomerAccount
 *
 * @copyright   Copyright (c) 2021 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Dmitriy Fionov <dmitriy@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\CustomerAccount\Block\Widget;

use Amasty\CustomerAttributes\Helper\Image as ImageHelper;
use Magento\Customer\Api\CustomerMetadataInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Helper\Address;
use Magento\Customer\Helper\Session\CurrentCustomer;
use Magento\Framework\View\Element\Template\Context;

/**
 * Class LpoCode
 */
class LpoCode extends AbstractWidgetOption
{
    /** @var string */
    const ATTRIBUTE_CODE = 'lpo_code';

    /**
     * LpoCode constructor.
     *
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Customer\Helper\Address $addressHelper
     * @param \Magento\Customer\Helper\Session\CurrentCustomer $currentCustomer
     * @param \Amasty\CustomerAttributes\Helper\Image $imageHelper
     * @param \Magento\Customer\Api\CustomerMetadataInterface $customerMetadata
     * @param array $data
     */
    public function __construct(
        Context $context,
        Address $addressHelper,
        CurrentCustomer $currentCustomer,
        ImageHelper $imageHelper,
        CustomerMetadataInterface $customerMetadata,
        array $data = []
    ) {
        parent::__construct($context, $addressHelper, $currentCustomer, $imageHelper, $customerMetadata, $data);
    }

    /**
     * @return void
     */
    public function _construct()
    {
        parent::_construct();
        $this->setAttributeCode(self::ATTRIBUTE_CODE);
    }

    /**
     * Get attribute value
     *
     * @return string
     */
    public function getLpoCode(): string
    {
        $lpoCode = $this->getCustomer()->getCustomAttribute(self::ATTRIBUTE_CODE);
        $value = '';
        if ($lpoCode) {
            $value = $lpoCode->getValue();
        }

        return $value;
    }

    /**
     * Get attribute value
     *
     * @return string
     */
    public function getAttributeCode()
    {
        $attribute = $this->getAttribute();
        $code = '';
        if ($attribute) {
            $code = $attribute->getAttributeCode();
        }

        return $code;
    }

    /**
     * Get attribute frontent class
     *
     * @return string
     */
    public function getFrontendClass()
    {
        $attribute = $this->getAttribute();
        $class = '';
        if ($attribute) {
            $class = $attribute->getFrontendClass();
        }

        return $class;
    }

    /**
     * Get current Customer
     *
     * @return \Magento\Customer\Api\Data\CustomerInterface
     */
    private function getCustomer(): CustomerInterface
    {
        return $this->currentCustomer->getCustomer();
    }
}
