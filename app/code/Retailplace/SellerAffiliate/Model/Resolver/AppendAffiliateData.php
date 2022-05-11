<?php

/**
 * Retailplace_SellerAffiliate
 *
 * @copyright   Copyright (c) 2022 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Dmitriy Fionov <dmitriy@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\SellerAffiliate\Model\Resolver;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Retailplace\SellerAffiliate\Model\SellerAffiliateManagement;

/**
 * Class AppendAffiliateData
 */
class AppendAffiliateData implements ResolverInterface
{
    /** @var \Retailplace\SellerAffiliate\Model\SellerAffiliateManagement */
    private $sellerAffiliateManagement;

    /**
     * Constructor
     *
     * @param \Retailplace\SellerAffiliate\Model\SellerAffiliateManagement $sellerAffiliateManagement
     */
    public function __construct(SellerAffiliateManagement $sellerAffiliateManagement)
    {
        $this->sellerAffiliateManagement = $sellerAffiliateManagement;
    }

    /**
     * Get and process Affiliate Data
     *
     * @param \Magento\Framework\GraphQl\Config\Element\Field $field
     * @param $context
     * @param \Magento\Framework\GraphQl\Schema\Type\ResolveInfo $info
     * @param array|null $value
     * @param array|null $args
     * @return array
     * @throws \Magento\Framework\GraphQl\Exception\GraphQlInputException
     */
    public function resolve(Field $field, $context, ResolveInfo $info, array $value = null, array $args = null)
    {
        $sellerId = $args['input']['seller_id'] ?? null;
        $affiliateUrl = $args['input']['affiliate_url'] ?? null;
        $clientsideDatetime = $args['input']['clientside_datetime'] ?? null;
        if (!$sellerId || !$affiliateUrl) {
            throw new GraphQlInputException(__('Parameters "seller_id" and "affiliate_url" are required'));
        }
        $customerId = $context->getUserId();
        $isLoggedIn = (bool) $customerId;
        if ($isLoggedIn) {
            $this->sellerAffiliateManagement->createSellerAffiliateEntity(
                (int) $sellerId,
                (int) $customerId,
                $affiliateUrl,
                $clientsideDatetime
            );
        }

        return [
            'is_logged_in' => $isLoggedIn
        ];
    }
}
