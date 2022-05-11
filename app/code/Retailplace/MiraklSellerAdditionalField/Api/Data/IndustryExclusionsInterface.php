<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Retailplace\MiraklSellerAdditionalField\Api\Data;

interface IndustryExclusionsInterface extends \Magento\Framework\Api\ExtensibleDataInterface
{

    const CODE = 'code';
    const LABEL = 'label';
    const INDUSTRYEXCLUSIONS_ID = 'industryexclusions_id';

    /**
     * Get industryexclusions_id
     * @return string|null
     */
    public function getIndustryexclusionsId();

    /**
     * Set industryexclusions_id
     * @param string $industryexclusionsId
     * @return \Retailplace\MiraklSellerAdditionalField\Api\Data\IndustryExclusionsInterface
     */
    public function setIndustryexclusionsId($industryexclusionsId);

    /**
     * Get code
     * @return string|null
     */
    public function getCode();

    /**
     * Set code
     * @param string $code
     * @return \Retailplace\MiraklSellerAdditionalField\Api\Data\IndustryExclusionsInterface
     */
    public function setCode($code);

    /**
     * Get Label
     *
     * @return string|null
     */
    public function getLabel();

    /**
     * Set Label
     *
     * @param string $label
     * @return \Retailplace\MiraklSellerAdditionalField\Api\Data\IndustryExclusionsInterface
     */
    public function setLabel($label);

    /**
     * Retrieve existing extension attributes object or create a new one.
     * @return \Retailplace\MiraklSellerAdditionalField\Api\Data\IndustryExclusionsExtensionInterface|null
     */
    public function getExtensionAttributes();

    /**
     * Set an extension attributes object.
     * @param \Retailplace\MiraklSellerAdditionalField\Api\Data\IndustryExclusionsExtensionInterface $extensionAttributes
     * @return $this
     */
    public function setExtensionAttributes(
        \Retailplace\MiraklSellerAdditionalField\Api\Data\IndustryExclusionsExtensionInterface $extensionAttributes
    );
}

