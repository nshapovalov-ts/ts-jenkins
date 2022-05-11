<?php

/**
 * Retailplace_ChannelPricing
 *
 * @copyright   Copyright (c) 2022 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Dmitriy Fionov <dmitriy@tradesquare.com.au>
 * @author      Ruslan Mukhatov <ruslan@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\ChannelPricing\Plugin;

use Magento\Customer\Api\GroupRepositoryInterface;
use Magento\Customer\Model\Session;
use Mirakl\Connector\Model\ResourceModel\Offer\Collection;
use Psr\Log\LoggerInterface;
use Retailplace\MiraklConnector\Api\Data\OfferInterface;
use Retailplace\MiraklConnector\Rewrite\Helper\Offer;
use Retailplace\MiraklSellerAdditionalField\Model\SellerFilter;

/**
 * Class ConnectorOfferHelper
 */
class ConnectorOfferHelper
{
    /** @var \Magento\Customer\Model\Session */
    private $customerSession;

    /** @var \Magento\Customer\Api\GroupRepositoryInterface */
    private $customerGroupRepository;

    /** @var string */
    private $groupName;

    /** @var SellerFilter */
    private $sellerFilter;

    /** @var \Psr\Log\LoggerInterface */
    private $logger;

    /**
     * ConnectorOfferHelper constructor.
     *
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Customer\Api\GroupRepositoryInterface $customerGroupRepository
     * @param SellerFilter $sellerFilter
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        Session $customerSession,
        GroupRepositoryInterface $customerGroupRepository,
        SellerFilter $sellerFilter,
        LoggerInterface $logger
    ) {
        $this->customerSession = $customerSession;
        $this->customerGroupRepository = $customerGroupRepository;
        $this->sellerFilter = $sellerFilter;
        $this->logger = $logger;
    }

    /**
     * Add Segment Filtering to Offers Collection
     *
     * @param \Retailplace\MiraklConnector\Rewrite\Helper\Offer $subject
     * @param \Mirakl\Connector\Model\ResourceModel\Offer\Collection $collection
     * @return \Mirakl\Connector\Model\ResourceModel\Offer\Collection
     */
    public function afterGetAvailableOffersForProductSku(Offer $subject, Collection $collection)
    {
        $shopOptionIds = $this->sellerFilter->getFilteredShopOptionIds();
        if (!empty($shopOptionIds)) {
            $collection->getSelect()->where('shops.eav_option_id IN (?)', $shopOptionIds);
        }

        $groupName = $this->getCurrentGroupCode();
        if ($groupName) {
            $collection->addFieldToFilter(OfferInterface::SEGMENT, [
                ['eq' => ''],
                ['finset' => $groupName]
            ]);
            $collection = $this->clearCollectionWithSegment($collection, $groupName);
        } else {
            $collection->addFieldToFilter(OfferInterface::SEGMENT, ['eq' => '']);
        }

        return $collection;
    }

    /**
     * Exclude Offers without Segment from Collection if we have Offers with Segment
     *
     * @param \Mirakl\Connector\Model\ResourceModel\Offer\Collection $collection
     * @param string $groupName
     * @return \Mirakl\Connector\Model\ResourceModel\Offer\Collection
     */
    private function clearCollectionWithSegment(Collection $collection, string $groupName): Collection
    {
        $groupOfferFound = [];
        if ($collection->getSize() > 1) {
            /** Add all offers for the current group first */
            /** @var \Retailplace\MiraklConnector\Api\Data\OfferInterface $offer */
            foreach ($collection as $offer) {
                $offerGroups = explode(',', $offer->getSegment());
                if (in_array($groupName, $offerGroups)) {
                    $sku = $offer->getProductSku();
                    $shopId = $offer->getShopId();
                    $groupOfferFound[$sku][$shopId] = true;
                }
            }

            /** If some combination of shop_id and product_sku doesn't have a group offer, add the general offer */
            /** @var \Retailplace\MiraklConnector\Api\Data\OfferInterface $offer */
            foreach ($collection as $offer) {
                $sku = $offer->getProductSku();
                $shopId = $offer->getShopId();

                if (!$offer->getSegment() && isset($groupOfferFound[$sku][$shopId]) &&
                    $groupOfferFound[$sku][$shopId]) {
                    $collection->removeItemByKey($offer->getId());
                }
            }
        }

        return $collection;
    }

    /**
     * Get Group Name for the Current Customer
     *
     * @return string|null
     */
    private function getCurrentGroupCode(): ?string
    {
        if (!$this->groupName) {
            try {
                $groupId = $this->customerSession->getCustomerGroupId();
                $group = $this->customerGroupRepository->getById($groupId);
                $this->groupName = $group->getCode();
            } catch (\Exception $e) {
                $this->logger->error($e->getMessage());
            }
        }

        return $this->groupName;
    }
}
