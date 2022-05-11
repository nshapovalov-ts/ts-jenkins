<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Retailplace\MiraklSellerAdditionalField\Api;

use Magento\Framework\Api\SearchCriteriaInterface;

interface ChannelExclusionsRepositoryInterface
{

    /**
     * Save ChannelExclusions
     * @param \Retailplace\MiraklSellerAdditionalField\Api\Data\ChannelExclusionsInterface $channelExclusions
     * @return \Retailplace\MiraklSellerAdditionalField\Api\Data\ChannelExclusionsInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function save(
        \Retailplace\MiraklSellerAdditionalField\Api\Data\ChannelExclusionsInterface $channelExclusions
    );

    /**
     * Retrieve ChannelExclusions
     * @param string $channelexclusionsId
     * @return \Retailplace\MiraklSellerAdditionalField\Api\Data\ChannelExclusionsInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function get($channelexclusionsId);

    /**
     * Retrieve ChannelExclusions matching the specified criteria.
     * @param \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
     * @return \Retailplace\MiraklSellerAdditionalField\Api\Data\ChannelExclusionsSearchResultsInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getList(
        \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
    );

    /**
     * Delete ChannelExclusions
     * @param \Retailplace\MiraklSellerAdditionalField\Api\Data\ChannelExclusionsInterface $channelExclusions
     * @return bool true on success
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function delete(
        \Retailplace\MiraklSellerAdditionalField\Api\Data\ChannelExclusionsInterface $channelExclusions
    );

    /**
     * Delete ChannelExclusions by ID
     * @param string $channelexclusionsId
     * @return bool true on success
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function deleteById($channelexclusionsId);
}

