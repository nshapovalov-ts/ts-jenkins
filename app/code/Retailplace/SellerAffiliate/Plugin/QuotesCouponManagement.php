<?php

/**
 * Retailplace_SellerAffiliate
 *
 * @copyright   Copyright (c) 2022 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Nikolay Shapovalov <nikolay@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\SellerAffiliate\Plugin;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Model\CouponManagement;
use Retailplace\SellerAffiliate\Model\ConfigProvider;

/**
 * Class QuotesCouponManagement
 */
class QuotesCouponManagement
{
    /** @var ConfigProvider */
    private $configProvider;

    /**
     * @param ConfigProvider $configProvider
     */
    public function __construct(
        ConfigProvider $configProvider
    ) {
        $this->configProvider = $configProvider;
    }

    /**
     * @param \Magento\Quote\Model\CouponManagement $subject
     * @param $cartId
     * @param $couponCode
     * @return mixed
     * @throws NoSuchEntityException
     */
    public function beforeSet(CouponManagement $subject, $cartId, $couponCode)
    {
        if ($this->configProvider->isCouponFilteringEnable()) {
            $errorMessage = $this->configProvider->getErrorMessage();
            if (in_array($couponCode, $this->configProvider->getCouponsArray())) {
                throw new NoSuchEntityException(__($errorMessage));
            }
        }
        return [$cartId, $couponCode];
    }
}
