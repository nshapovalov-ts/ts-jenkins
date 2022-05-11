<?php

/**
 * Retailplace_SellerAffiliate
 *
 * @copyright   Copyright (c) 2022 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Nikolay Shapovalov <nikolay@tradesquare.com.au>
 * @author      Evgeniy Derevyanko <evgeniy@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\SellerAffiliate\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;

/**
 * Class ConfigProvider for config
 */
class ConfigProvider
{
    /** @var string */
    const XML_PATH_TS_CONNECT_PROMO_RULE_ENABLE = 'ts_connect/ts_first_order_discount/enable';
    const XML_PATH_TS_CONNECT_SHIPPING_RULE_ENABLE = 'ts_connect/ts_free_shipping_discount/enable';
    const XML_PATH_TS_CONNECT_PROMO_RULE_LABEL = 'ts_connect/ts_first_order_discount/frontend_label';
    const XML_PATH_TS_CONNECT_SHIPPING_RULE_LABEL = 'ts_connect/ts_free_shipping_discount/frontend_label';
    const XML_PATH_TS_CONNECT_PROMO_RULE_DEFAULT_AMOUNT = 'ts_connect/ts_first_order_discount/default_amount';
    const XML_PATH_TS_CONNECT_COUPON_FILTERING_STATUS = 'ts_connect/ts_coupon_management/enable';
    const XML_PATH_TS_CONNECT_COUPON_LIST = 'ts_connect/ts_coupon_management/coupon_codes';
    const XML_PATH_TS_CONNECT_COUPON_ERROR_MESSAGE = 'ts_connect/ts_coupon_management/error_message';

    /**
     * @var ScopeConfigInterface
     */
    private $config;

    /**
     * @param ScopeConfigInterface $config
     */
    public function __construct(
        ScopeConfigInterface $config
    ) {
        $this->config = $config;
    }

    /**
     * @return bool
     */
    public function isTsConnectPromoRuleEnable(): bool
    {
        return $this->config->isSetFlag(self::XML_PATH_TS_CONNECT_PROMO_RULE_ENABLE);
    }

    /**
     * Is shipping rule enabled
     *
     * @return bool
     */
    public function isTsConnectShippingRuleEnable(): bool
    {
        return $this->config->isSetFlag(self::XML_PATH_TS_CONNECT_SHIPPING_RULE_ENABLE);
    }

    /**
     * @return string
     */
    public function getPromoRuleLabel(): string
    {
        return (string) $this->config->getValue(self::XML_PATH_TS_CONNECT_PROMO_RULE_LABEL);
    }

    /**
     * Get shipping rule label
     *
     * @return string
     */
    public function getShippingRuleLabel(): string
    {
        return (string) $this->config->getValue(self::XML_PATH_TS_CONNECT_SHIPPING_RULE_LABEL);
    }

    /**
     * @return float
     */
    public function getDefaultAmountFirstOrder(): float
    {
        return (float) $this->config->getValue(self::XML_PATH_TS_CONNECT_PROMO_RULE_DEFAULT_AMOUNT);
    }

    /**
     * @return bool
     */
    public function isCouponFilteringEnable(): bool
    {
        return $this->config->isSetFlag(self::XML_PATH_TS_CONNECT_COUPON_FILTERING_STATUS);
    }

    /**
     * @return array
     */
    public function getCouponsArray(): array
    {
        $couponString = $this->config->getValue(self::XML_PATH_TS_CONNECT_COUPON_LIST);
        return explode(",", $couponString);
    }

    /**
     * @return string
     */
    public function getErrorMessage(): string
    {
        return (string) $this->config->getValue(self::XML_PATH_TS_CONNECT_COUPON_ERROR_MESSAGE);
    }
}
