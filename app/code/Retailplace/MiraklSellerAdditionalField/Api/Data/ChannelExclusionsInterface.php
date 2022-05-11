<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Retailplace\MiraklSellerAdditionalField\Api\Data;

interface ChannelExclusionsInterface extends \Magento\Framework\Api\ExtensibleDataInterface
{

    const CODE = 'code';
    const CHANNELEXCLUSIONS_ID = 'channelexclusions_id';

    /**
     * Get channelexclusions_id
     * @return string|null
     */
    public function getChannelexclusionsId();

    /**
     * Set channelexclusions_id
     * @param string $channelexclusionsId
     * @return \Retailplace\MiraklSellerAdditionalField\Api\Data\ChannelExclusionsInterface
     */
    public function setChannelexclusionsId($channelexclusionsId);

    /**
     * Get code
     * @return string|null
     */
    public function getCode();

    /**
     * Set code
     * @param string $code
     * @return \Retailplace\MiraklSellerAdditionalField\Api\Data\ChannelExclusionsInterface
     */
    public function setCode($code);

    /**
     * Retrieve existing extension attributes object or create a new one.
     * @return \Retailplace\MiraklSellerAdditionalField\Api\Data\ChannelExclusionsExtensionInterface|null
     */
    public function getExtensionAttributes();

    /**
     * Set an extension attributes object.
     * @param \Retailplace\MiraklSellerAdditionalField\Api\Data\ChannelExclusionsExtensionInterface $extensionAttributes
     * @return $this
     */
    public function setExtensionAttributes(
        \Retailplace\MiraklSellerAdditionalField\Api\Data\ChannelExclusionsExtensionInterface $extensionAttributes
    );
}

