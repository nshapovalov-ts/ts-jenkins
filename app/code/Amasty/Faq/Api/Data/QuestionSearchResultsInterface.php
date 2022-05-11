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
interface QuestionSearchResultsInterface extends \Magento\Framework\Api\SearchResultsInterface
{
    /**
     * Get FAQ questions list
     *
     * @return \Amasty\Faq\Api\Data\QuestionInterface[]
     */
    public function getItems();

    /**
     * Set FAQ questions list
     *
     * @param \Amasty\Faq\Api\Data\QuestionInterface[] $items
     * @return $this
     */
    public function setItems(array $items);
}
