<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Retailplace\MiraklSellerAdditionalField\Api\Data;

interface ExclusionsLogicInterface extends \Magento\Framework\Api\ExtensibleDataInterface
{

    const CODE = 'code';
    const EXCLUSIONSLOGIC_ID = 'exclusionslogic_id';

    /**
     * Get exclusionslogic_id
     * @return string|null
     */
    public function getExclusionslogicId();

    /**
     * Set exclusionslogic_id
     * @param string $exclusionslogicId
     * @return \Retailplace\MiraklSellerAdditionalField\Api\Data\ExclusionsLogicInterface
     */
    public function setExclusionslogicId($exclusionslogicId);

    /**
     * Get code
     * @return string|null
     */
    public function getCode();

    /**
     * Set code
     * @param string $code
     * @return \Retailplace\MiraklSellerAdditionalField\Api\Data\ExclusionsLogicInterface
     */
    public function setCode($code);

    /**
     * Retrieve existing extension attributes object or create a new one.
     * @return \Retailplace\MiraklSellerAdditionalField\Api\Data\ExclusionsLogicExtensionInterface|null
     */
    public function getExtensionAttributes();

    /**
     * Set an extension attributes object.
     * @param \Retailplace\MiraklSellerAdditionalField\Api\Data\ExclusionsLogicExtensionInterface $extensionAttributes
     * @return $this
     */
    public function setExtensionAttributes(
        \Retailplace\MiraklSellerAdditionalField\Api\Data\ExclusionsLogicExtensionInterface $extensionAttributes
    );
}

