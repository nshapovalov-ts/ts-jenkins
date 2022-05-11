<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Retailplace\MiraklSellerAdditionalField\Api;

use Magento\Framework\Api\SearchCriteriaInterface;

interface ExclusionsLogicRepositoryInterface
{

    /**
     * Save ExclusionsLogic
     * @param \Retailplace\MiraklSellerAdditionalField\Api\Data\ExclusionsLogicInterface $exclusionsLogic
     * @return \Retailplace\MiraklSellerAdditionalField\Api\Data\ExclusionsLogicInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function save(
        \Retailplace\MiraklSellerAdditionalField\Api\Data\ExclusionsLogicInterface $exclusionsLogic
    );

    /**
     * Retrieve ExclusionsLogic
     * @param string $exclusionslogicId
     * @return \Retailplace\MiraklSellerAdditionalField\Api\Data\ExclusionsLogicInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function get($exclusionslogicId);

    /**
     * Retrieve ExclusionsLogic matching the specified criteria.
     * @param \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
     * @return \Retailplace\MiraklSellerAdditionalField\Api\Data\ExclusionsLogicSearchResultsInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getList(
        \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
    );

    /**
     * Delete ExclusionsLogic
     * @param \Retailplace\MiraklSellerAdditionalField\Api\Data\ExclusionsLogicInterface $exclusionsLogic
     * @return bool true on success
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function delete(
        \Retailplace\MiraklSellerAdditionalField\Api\Data\ExclusionsLogicInterface $exclusionsLogic
    );

    /**
     * Delete ExclusionsLogic by ID
     * @param string $exclusionslogicId
     * @return bool true on success
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function deleteById($exclusionslogicId);
}

