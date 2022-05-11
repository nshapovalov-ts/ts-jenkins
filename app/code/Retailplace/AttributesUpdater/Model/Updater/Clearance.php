<?php

/**
 * Retailplace_AttributesUpdater
 *
 * @copyright   Copyright (c) 2021 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Dmitriy Fionov <dmitriy@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\AttributesUpdater\Model\Updater;

use Magento\Framework\Api\SearchCriteriaBuilder;
use Retailplace\AttributesUpdater\Api\UpdaterInterface;
use Retailplace\MiraklConnector\Api\Data\OfferInterface;

/**
 * Class Clearance
 */
class Clearance extends AbstractUpdater implements UpdaterInterface
{
    /** @var string */
    protected $attributeCode = 'clearance';

    /**
     * Extend Search Criteria
     *
     * @param \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder
     * @return \Magento\Framework\Api\SearchCriteriaBuilder
     */
    protected function extendOffersSearchCriteria(SearchCriteriaBuilder $searchCriteriaBuilder): SearchCriteriaBuilder
    {
        $searchCriteriaBuilder
            ->addFilter(OfferInterface::CLEARANCE, 1)
            ->addFilter(OfferInterface::SEGMENT, '');

        return $searchCriteriaBuilder;
    }
}
