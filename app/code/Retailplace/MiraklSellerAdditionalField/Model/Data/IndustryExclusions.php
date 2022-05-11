<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Retailplace\MiraklSellerAdditionalField\Model\Data;

use Magento\Framework\Model\AbstractExtensibleModel;
use Retailplace\MiraklSellerAdditionalField\Api\Data\IndustryExclusionsInterface;

class IndustryExclusions extends AbstractExtensibleModel implements IndustryExclusionsInterface
{
    /**
     * Get industryexclusions_id
     * @return string|null
     */
    public function getIndustryexclusionsId()
    {
        return $this->getData(self::INDUSTRYEXCLUSIONS_ID);
    }

    /**
     * Set industryexclusions_id
     * @param string $industryexclusionsId
     * @return \Retailplace\MiraklSellerAdditionalField\Api\Data\IndustryExclusionsInterface
     */
    public function setIndustryexclusionsId($industryexclusionsId)
    {
        return $this->setData(self::INDUSTRYEXCLUSIONS_ID, $industryexclusionsId);
    }

    /**
     * Get code
     * @return string|null
     */
    public function getCode()
    {
        return $this->getData(self::CODE);
    }

    /**
     * Set code
     * @param string $code
     * @return \Retailplace\MiraklSellerAdditionalField\Api\Data\IndustryExclusionsInterface
     */
    public function setCode($code)
    {
        return $this->setData(self::CODE, $code);
    }

    /**
     * Get Label
     *
     * @return string|null
     */
    public function getLabel()
    {
        return $this->getData(self::LABEL);
    }

    /**
     * Set Label
     *
     * @param string $label
     * @return \Retailplace\MiraklSellerAdditionalField\Api\Data\IndustryExclusionsInterface
     */
    public function setLabel($label)
    {
        return $this->setData(self::LABEL, $label);
    }

    /**
     * Retrieve existing extension attributes object or create a new one.
     * @return \Retailplace\MiraklSellerAdditionalField\Api\Data\IndustryExclusionsExtensionInterface|null
     */
    public function getExtensionAttributes()
    {
        return $this->_getExtensionAttributes();
    }

    /**
     * Set an extension attributes object.
     * @param \Retailplace\MiraklSellerAdditionalField\Api\Data\IndustryExclusionsExtensionInterface $extensionAttributes
     * @return $this
     */
    public function setExtensionAttributes(
        \Retailplace\MiraklSellerAdditionalField\Api\Data\IndustryExclusionsExtensionInterface $extensionAttributes
    ) {
        return $this->_setExtensionAttributes($extensionAttributes);
    }
}

