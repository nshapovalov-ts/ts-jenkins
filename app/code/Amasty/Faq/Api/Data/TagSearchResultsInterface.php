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
interface TagSearchResultsInterface extends \Magento\Framework\Api\SearchResultsInterface
{
    /**
     * Get FAQ tags list
     *
     * @return \Amasty\Faq\Api\Data\TagInterface[]
     */
    public function getItems();

    /**
     * Set FAQ tags list
     *
     * @param \Amasty\Faq\Api\Data\TagInterface[] $items
     * @return $this
     */
    public function setItems(array $items);
}
