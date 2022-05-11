<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Retailplace\MiraklSellerAdditionalField\Api;

use Magento\Framework\Api\SearchCriteriaInterface;

interface IndustryExclusionsRepositoryInterface
{

    /**
     * Save IndustryExclusions
     * @param \Retailplace\MiraklSellerAdditionalField\Api\Data\IndustryExclusionsInterface $industryExclusions
     * @return \Retailplace\MiraklSellerAdditionalField\Api\Data\IndustryExclusionsInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function save(
        \Retailplace\MiraklSellerAdditionalField\Api\Data\IndustryExclusionsInterface $industryExclusions
    );

    /**
     * Retrieve IndustryExclusions
     * @param string $industryexclusionsId
     * @return \Retailplace\MiraklSellerAdditionalField\Api\Data\IndustryExclusionsInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function get($industryexclusionsId);

    /**
     * Retrieve IndustryExclusions matching the specified criteria.
     * @param \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
     * @return \Retailplace\MiraklSellerAdditionalField\Api\Data\IndustryExclusionsSearchResultsInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getList(
        \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
    );

    /**
     * Delete IndustryExclusions
     * @param \Retailplace\MiraklSellerAdditionalField\Api\Data\IndustryExclusionsInterface $industryExclusions
     * @return bool true on success
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function delete(
        \Retailplace\MiraklSellerAdditionalField\Api\Data\IndustryExclusionsInterface $industryExclusions
    );

    /**
     * Delete IndustryExclusions by ID
     * @param string $industryexclusionsId
     * @return bool true on success
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function deleteById($industryexclusionsId);
}

