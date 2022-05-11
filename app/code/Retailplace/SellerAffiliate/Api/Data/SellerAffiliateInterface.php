<?php

/**
 * Retailplace_SellerAffiliate
 *
 * @copyright   Copyright (c) 2022 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Evgeniy Derevyanko <evgeniy@tradesquare.com.au>
 * @author      Nikolay Shapovalov <nikolay@tradesquare.com.au>
 * @author      Dmitriy Fionov <dmitriy@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\SellerAffiliate\Api\Data;

/**
 * Interface SellerInterface implements interface for affiliate customers
 */
interface SellerAffiliateInterface
{
    /** @var string */
    const SELLERAFFILIATE_ID = 'selleraffiliate_id';
    const CUSTOMER_ID = 'customer_id';
    const SELLER_ID = 'seller_id';
    const CLICK_DATE_TIME = 'click_datetime';
    const IP_ADDRESS = 'ip_address';
    const AFFILIATE_URL = 'affiliate_url';
    const CLIENT_SIDE_DATE_TIME = 'clientside_datetime';
    const IS_USER_AFFILIATED = 'is_user_affiliated';

    /**
     * Get selleraffiliate_id value
     *
     * @return int|null
     */
    public function getSelleraffiliateId(): int;

    /**
     * Set selleraffiliate_id value
     *
     * @param int $selleraffiliateId
     * @return $this
     */
    public function setSelleraffiliateId(int $selleraffiliateId): SellerAffiliateInterface;

    /**
     * Get customer_id value
     *
     * @return int|null
     */
    public function getCustomerId(): int;

    /**
     * Set customer_id value
     *
     * @param int $customerId
     * @return $this
     */
    public function setCustomerId(int $customerId): SellerAffiliateInterface;

    /**
     * Get seller_id value
     *
     * @return int|null
     */
    public function getSellerId(): int;

    /**
     * Set seller_id value
     *
     * @param int $sellerId
     * @return $this
     */
    public function setSellerId(int $sellerId): SellerAffiliateInterface;

    /**
     * Get click_datetime value
     *
     * @return string|null
     */
    public function getClickDateTime(): ?string;

    /**
     * Set click_datetime value
     *
     * @param string $clickDateTime
     * @return $this
     */
    public function setClickDateTime(string $clickDateTime): SellerAffiliateInterface;

    /**
     * Get ip_address value
     *
     * @return string|null
     */
    public function getIpAddress(): ?string;

    /**
     * Set ip_address value
     *
     * @param string $ipAddress
     * @return $this
     */
    public function setIpAddress(string $ipAddress): SellerAffiliateInterface;

    /**
     * Get affiliate_url value
     *
     * @return string|null
     */
    public function getAffiliateUrl(): ?string;

    /**
     * Set affiliate_url value
     *
     * @param string $affiliateUrl
     * @return $this
     */
    public function setAffiliateUrl(string $affiliateUrl): SellerAffiliateInterface;

    /**
     * Get clientside_datetime value
     *
     * @return string|null
     */
    public function getClientSideDateTime(): ?string;

    /**
     * Set clientside_datetime
     *
     * @param string $clientSideDateTime
     * @return $this
     */
    public function setClientSideDateTime(string $clientSideDateTime): SellerAffiliateInterface;

    /**
     * Get is_user_affiliated value
     *
     * @return bool
     */
    public function getIsUserAffiliated(): bool;

    /**
     * Set is_user_affiliated value
     *
     * @param bool $isUserAffiliated
     * @return $this
     */
    public function setIsUserAffiliated(bool $isUserAffiliated): SellerAffiliateInterface;
}
