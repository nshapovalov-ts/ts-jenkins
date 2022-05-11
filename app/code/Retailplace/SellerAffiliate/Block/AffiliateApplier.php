<?php

/**
 * Retailplace_SellerAffiliate
 *
 * @copyright   Copyright (c) 2022 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Dmitriy Fionov <dmitriy@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\SellerAffiliate\Block;

use Magento\Framework\View\Element\Template;
use Retailplace\SellerAffiliate\Model\SellerAffiliateManagement;

/**
 * Class AffiliateApplier
 */
class AffiliateApplier extends Template
{
    /**
     * Get Code Prefix
     *
     * @return string
     */
    public function getCodePrefix(): string
    {
        return SellerAffiliateManagement::AFFILIATE_CODE_PREFIX;
    }

    /**
     * Get Cookie Lifetime in Seconds
     *
     * @return int
     */
    public function getCookieLifetime(): int
    {
        return SellerAffiliateManagement::AFFILIATE_COOKIE_LIFETIME_SEC;
    }

    /**
     * Get Cookie Name
     *
     * @return string
     */
    public function getCookieName(): string
    {
        return SellerAffiliateManagement::AFFILIATE_COOKIE_NAME;
    }
}
