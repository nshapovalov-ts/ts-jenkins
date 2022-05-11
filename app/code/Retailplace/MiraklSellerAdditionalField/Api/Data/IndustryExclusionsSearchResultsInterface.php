<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Retailplace\MiraklSellerAdditionalField\Api\Data;

interface IndustryExclusionsSearchResultsInterface extends \Magento\Framework\Api\SearchResultsInterface
{

    /**
     * Get IndustryExclusions list.
     * @return \Retailplace\MiraklSellerAdditionalField\Api\Data\IndustryExclusionsInterface[]
     */
    public function getItems();

    /**
     * Set code list.
     * @param \Retailplace\MiraklSellerAdditionalField\Api\Data\IndustryExclusionsInterface[] $items
     * @return $this
     */
    public function setItems(array $items);
}

