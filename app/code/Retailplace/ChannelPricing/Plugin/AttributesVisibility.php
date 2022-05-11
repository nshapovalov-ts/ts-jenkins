<?php

/**
 * Retailplace_ChannelPricing
 *
 * @copyright   Copyright (c) 2021 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Dmitriy Fionov <dmitriy@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\ChannelPricing\Plugin;

use Magento\Catalog\Model\Layer\FilterList;
use Retailplace\ChannelPricing\Model\AttributesVisibilityManagement;

/**
 * Class AttributesVisibility
 */
class AttributesVisibility
{
    /** @var \Retailplace\ChannelPricing\Model\AttributesVisibilityManagement */
    private $attributesVisibilityManagement;

    /**
     * AttributesVisibility constructor.
     *
     * @param \Retailplace\ChannelPricing\Model\AttributesVisibilityManagement $attributesVisibilityManagement
     */
    public function __construct(
        AttributesVisibilityManagement $attributesVisibilityManagement
    ) {
        $this->attributesVisibilityManagement = $attributesVisibilityManagement;
    }

    /**
     * Hide filters from Layer Navigation depends on Customer Group
     *
     * @param \Magento\Catalog\Model\Layer\FilterList $subject
     * @param array $result
     * @return array
     */
    public function afterGetFilters(FilterList $subject, array $result): array
    {
        foreach ($result as $key => $filter) {
            if (!$this->attributesVisibilityManagement->checkAttributeVisibility($filter->getRequestVar())) {
                unset($result[$key]);
            }
        }

        return $result;
    }
}
