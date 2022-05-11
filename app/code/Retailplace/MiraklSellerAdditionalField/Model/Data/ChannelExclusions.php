<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Retailplace\MiraklSellerAdditionalField\Model\Data;

use Retailplace\MiraklSellerAdditionalField\Api\Data\ChannelExclusionsInterface;

class ChannelExclusions extends \Magento\Framework\Api\AbstractExtensibleObject implements ChannelExclusionsInterface
{

    /**
     * Get channelexclusions_id
     * @return string|null
     */
    public function getChannelexclusionsId()
    {
        return $this->_get(self::CHANNELEXCLUSIONS_ID);
    }

    /**
     * Set channelexclusions_id
     * @param string $channelexclusionsId
     * @return \Retailplace\MiraklSellerAdditionalField\Api\Data\ChannelExclusionsInterface
     */
    public function setChannelexclusionsId($channelexclusionsId)
    {
        return $this->setData(self::CHANNELEXCLUSIONS_ID, $channelexclusionsId);
    }

    /**
     * Get code
     * @return string|null
     */
    public function getCode()
    {
        return $this->_get(self::CODE);
    }

    /**
     * Set code
     * @param string $code
     * @return \Retailplace\MiraklSellerAdditionalField\Api\Data\ChannelExclusionsInterface
     */
    public function setCode($code)
    {
        return $this->setData(self::CODE, $code);
    }

    /**
     * Retrieve existing extension attributes object or create a new one.
     * @return \Retailplace\MiraklSellerAdditionalField\Api\Data\ChannelExclusionsExtensionInterface|null
     */
    public function getExtensionAttributes()
    {
        return $this->_getExtensionAttributes();
    }

    /**
     * Set an extension attributes object.
     * @param \Retailplace\MiraklSellerAdditionalField\Api\Data\ChannelExclusionsExtensionInterface $extensionAttributes
     * @return $this
     */
    public function setExtensionAttributes(
        \Retailplace\MiraklSellerAdditionalField\Api\Data\ChannelExclusionsExtensionInterface $extensionAttributes
    ) {
        return $this->_setExtensionAttributes($extensionAttributes);
    }
}

