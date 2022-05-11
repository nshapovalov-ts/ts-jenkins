<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Retailplace\MiraklSellerAdditionalField\Model;

use Magento\Framework\Api\DataObjectHelper;
use Retailplace\MiraklSellerAdditionalField\Api\Data\IndustryExclusionsInterface;
use Retailplace\MiraklSellerAdditionalField\Api\Data\IndustryExclusionsInterfaceFactory;

class IndustryExclusions extends \Magento\Framework\Model\AbstractModel
{

    protected $industryexclusionsDataFactory;

    protected $dataObjectHelper;

    protected $_eventPrefix = 'retailplace_miraklselleradditionalfield_industryexclusions';

    /**
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param IndustryExclusionsInterfaceFactory $industryexclusionsDataFactory
     * @param DataObjectHelper $dataObjectHelper
     * @param \Retailplace\MiraklSellerAdditionalField\Model\ResourceModel\IndustryExclusions $resource
     * @param \Retailplace\MiraklSellerAdditionalField\Model\ResourceModel\IndustryExclusions\Collection $resourceCollection
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        IndustryExclusionsInterfaceFactory $industryexclusionsDataFactory,
        DataObjectHelper $dataObjectHelper,
        \Retailplace\MiraklSellerAdditionalField\Model\ResourceModel\IndustryExclusions $resource,
        \Retailplace\MiraklSellerAdditionalField\Model\ResourceModel\IndustryExclusions\Collection $resourceCollection,
        array $data = []
    ) {
        $this->industryexclusionsDataFactory = $industryexclusionsDataFactory;
        $this->dataObjectHelper = $dataObjectHelper;
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
    }

    /**
     * Retrieve industryexclusions model with industryexclusions data
     * @return IndustryExclusionsInterface
     */
    public function getDataModel()
    {
        $industryexclusionsData = $this->getData();
        
        $industryexclusionsDataObject = $this->industryexclusionsDataFactory->create();
        $this->dataObjectHelper->populateWithArray(
            $industryexclusionsDataObject,
            $industryexclusionsData,
            IndustryExclusionsInterface::class
        );
        
        return $industryexclusionsDataObject;
    }
}

