<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Retailplace\MiraklSellerAdditionalField\Api\Data;

interface ExclusionsLogicSearchResultsInterface extends \Magento\Framework\Api\SearchResultsInterface
{

    /**
     * Get ExclusionsLogic list.
     * @return \Retailplace\MiraklSellerAdditionalField\Api\Data\ExclusionsLogicInterface[]
     */
    public function getItems();

    /**
     * Set code list.
     * @param \Retailplace\MiraklSellerAdditionalField\Api\Data\ExclusionsLogicInterface[] $items
     * @return $this
     */
    public function setItems(array $items);
}

