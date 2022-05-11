<?php

/**
 * Retailplace_SellerAffiliate
 *
 * @copyright   Copyright (c) 2022 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Nikolay Shapovalov <nikolay@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\SellerAffiliate\Model;

use Magento\Framework\Model\AbstractModel;
use Retailplace\SellerAffiliate\Api\Data\SellerAffiliateInterface;
use Retailplace\SellerAffiliate\Model\ResourceModel\SellerAffiliate as SellerAffiliateResourceModel;

/**
 * Class SellerAffiliate implements model for Seller Affiliate
 * @SuppressWarnings(PHPMD.CamelCaseMethodName)
 */
class SellerAffiliate extends AbstractModel implements SellerAffiliateInterface
{
    /**
     * @return void
     */
    public function _construct()
    {
        $this->_init(SellerAffiliateResourceModel::class);
    }

    /**
     * Get selleraffiliate_id value
     *
     * @return int
     */
    public function getSelleraffiliateId(): int
    {
        return (int) $this->getData(self::SELLERAFFILIATE_ID);
    }

    /**
     * Set selleraffiliate_id value
     *
     * @param int $selleraffiliateId
     * @return $this
     */
    public function setSelleraffiliateId(int $selleraffiliateId): SellerAffiliateInterface
    {
        return $this->setData(self::SELLERAFFILIATE_ID, $selleraffiliateId);
    }

    /**
     * Get customer_id value
     *
     * @return int
     */
    public function getCustomerId(): int
    {
        return (int) $this->getData(self::CUSTOMER_ID);
    }

    /**
     * Set customer_id value
     *
     * @param int $customerId
     * @return $this
     */
    public function setCustomerId(int $customerId): SellerAffiliateInterface
    {
        return $this->setData(self::CUSTOMER_ID, $customerId);
    }

    /**
     * Get seller_id value
     *
     * @return int
     */
    public function getSellerId(): int
    {
        return (int) $this->getData(self::SELLER_ID);
    }

    /**
     * Set seller_id value
     *
     * @param int $sellerId
     * @return $this
     */
    public function setSellerId(int $sellerId): SellerAffiliateInterface
    {
        return $this->setData(self::SELLER_ID, $sellerId);
    }

    /**
     * Get click_datetime value
     *
     * @return string|null
     */
    public function getClickDateTime(): ?string
    {
        return $this->getData(self::CLICK_DATE_TIME);
    }

    /**
     * Set click_datetime value
     *
     * @param string $clickDateTime
     * @return $this
     */
    public function setClickDateTime(string $clickDateTime): SellerAffiliateInterface
    {
        return $this->setData(self::CLICK_DATE_TIME, $clickDateTime);
    }

    /**
     * Get ip_address value
     *
     * @return string|null
     */
    public function getIpAddress(): ?string
    {
        return $this->getData(self::IP_ADDRESS);
    }

    /**
     * Set ip_address value
     *
     * @param string $ipAddress
     * @return $this
     */
    public function setIpAddress(string $ipAddress): SellerAffiliateInterface
    {
        return $this->setData(self::IP_ADDRESS, $ipAddress);
    }

    /**
     * Get affiliate_url value
     *
     * @return string|null
     */
    public function getAffiliateUrl(): ?string
    {
        return $this->getData(self::AFFILIATE_URL);
    }

    /**
     * Set affiliate_url value
     *
     * @param string $affiliateUrl
     * @return $this
     */
    public function setAffiliateUrl(string $affiliateUrl): SellerAffiliateInterface
    {
        return $this->setData(self::AFFILIATE_URL, $affiliateUrl);
    }

    /**
     * Get clientside_datetime value
     *
     * @return string|null
     */
    public function getClientSideDateTime(): ?string
    {
        return $this->getData(self::CLIENT_SIDE_DATE_TIME);
    }

    /**
     * Set clientside_datetime
     *
     * @param string $clientSideDateTime
     * @return $this
     */
    public function setClientSideDateTime(string $clientSideDateTime): SellerAffiliateInterface
    {
        return $this->setData(self::CLIENT_SIDE_DATE_TIME, $clientSideDateTime);
    }

    /**
     * Get is_user_affiliated value
     *
     * @return bool
     */
    public function getIsUserAffiliated(): bool
    {
        return (bool) $this->getData(self::IS_USER_AFFILIATED);
    }

    /**
     * Set is_user_affiliated value
     *
     * @param bool $isUserAffiliated
     * @return $this
     */
    public function setIsUserAffiliated(bool $isUserAffiliated): SellerAffiliateInterface
    {
        return $this->setData(self::IS_USER_AFFILIATED, $isUserAffiliated);
    }
}
