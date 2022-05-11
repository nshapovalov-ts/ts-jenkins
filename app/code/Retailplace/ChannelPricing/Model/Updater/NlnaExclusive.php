<?php

/**
 * Retailplace_ChannelPricing
 *
 * @copyright   Copyright (c) 2021 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Dmitriy Fionov <dmitriy@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\ChannelPricing\Model\Updater;

use Magento\Framework\Api\SearchCriteriaBuilder;
use Retailplace\AttributesUpdater\Api\UpdaterInterface;
use Retailplace\AttributesUpdater\Model\Updater\AbstractUpdater;
use Retailplace\ChannelPricing\Api\Data\ProductAttributesInterface;
use Retailplace\ChannelPricing\Model\GroupProcessor\Nlna;
use Retailplace\MiraklConnector\Api\Data\OfferInterface;

/**
 * Class NlnaExclusive
 */
class NlnaExclusive extends AbstractUpdater implements UpdaterInterface
{
    /** @var string */
    protected $attributeCode = ProductAttributesInterface::NLNA_EXCLUSIVE;

    /**
     * Extend Search Criteria
     *
     * @param \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder
     * @return \Magento\Framework\Api\SearchCriteriaBuilder
     */
    protected function extendOffersSearchCriteria(SearchCriteriaBuilder $searchCriteriaBuilder): SearchCriteriaBuilder
    {
        $searchCriteriaBuilder->addFilter(OfferInterface::SEGMENT, Nlna::GROUP_CODE, 'finset');

        return $searchCriteriaBuilder;
    }
}
