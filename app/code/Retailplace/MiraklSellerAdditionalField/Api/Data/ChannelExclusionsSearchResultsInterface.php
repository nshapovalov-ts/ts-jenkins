<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Retailplace\MiraklSellerAdditionalField\Api\Data;

interface ChannelExclusionsSearchResultsInterface extends \Magento\Framework\Api\SearchResultsInterface
{

    /**
     * Get ChannelExclusions list.
     * @return \Retailplace\MiraklSellerAdditionalField\Api\Data\ChannelExclusionsInterface[]
     */
    public function getItems();

    /**
     * Set code list.
     * @param \Retailplace\MiraklSellerAdditionalField\Api\Data\ChannelExclusionsInterface[] $items
     * @return $this
     */
    public function setItems(array $items);
}

