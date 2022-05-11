<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Retailplace\MiraklSellerAdditionalField\Model\Data;

use Retailplace\MiraklSellerAdditionalField\Api\Data\ExclusionsLogicInterface;

class ExclusionsLogic extends \Magento\Framework\Api\AbstractExtensibleObject implements ExclusionsLogicInterface
{

    /**
     * Get exclusionslogic_id
     * @return string|null
     */
    public function getExclusionslogicId()
    {
        return $this->_get(self::EXCLUSIONSLOGIC_ID);
    }

    /**
     * Set exclusionslogic_id
     * @param string $exclusionslogicId
     * @return \Retailplace\MiraklSellerAdditionalField\Api\Data\ExclusionsLogicInterface
     */
    public function setExclusionslogicId($exclusionslogicId)
    {
        return $this->setData(self::EXCLUSIONSLOGIC_ID, $exclusionslogicId);
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
     * @return \Retailplace\MiraklSellerAdditionalField\Api\Data\ExclusionsLogicInterface
     */
    public function setCode($code)
    {
        return $this->setData(self::CODE, $code);
    }

    /**
     * Retrieve existing extension attributes object or create a new one.
     * @return \Retailplace\MiraklSellerAdditionalField\Api\Data\ExclusionsLogicExtensionInterface|null
     */
    public function getExtensionAttributes()
    {
        return $this->_getExtensionAttributes();
    }

    /**
     * Set an extension attributes object.
     * @param \Retailplace\MiraklSellerAdditionalField\Api\Data\ExclusionsLogicExtensionInterface $extensionAttributes
     * @return $this
     */
    public function setExtensionAttributes(
        \Retailplace\MiraklSellerAdditionalField\Api\Data\ExclusionsLogicExtensionInterface $extensionAttributes
    ) {
        return $this->_setExtensionAttributes($extensionAttributes);
    }
}

