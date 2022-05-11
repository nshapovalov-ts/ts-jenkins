<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2021 Amasty (https://www.amasty.com)
 * @package Amasty_Faq
 */


namespace Amasty\Faq\Api\Data;

/**
 * @api
 */
interface CategorySearchResultsInterface extends \Magento\Framework\Api\SearchResultsInterface
{
    /**
     * Get FAQ categories list
     *
     * @return \Amasty\Faq\Api\Data\CategoryInterface[]
     */
    public function getItems();

    /**
     * Set FAQ categories list
     *
     * @param \Amasty\Faq\Api\Data\CategoryInterface[] $items
     * @return $this
     */
    public function setItems(array $items);
}
