<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Retailplace\MiraklSellerAdditionalField\Model;

use Magento\Framework\Api\DataObjectHelper;
use Retailplace\MiraklSellerAdditionalField\Api\Data\ExclusionsLogicInterface;
use Retailplace\MiraklSellerAdditionalField\Api\Data\ExclusionsLogicInterfaceFactory;

class ExclusionsLogic extends \Magento\Framework\Model\AbstractModel
{

    protected $exclusionslogicDataFactory;

    protected $dataObjectHelper;

    protected $_eventPrefix = 'retailplace_miraklselleradditionalfield_exclusionslogic';

    /**
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param ExclusionsLogicInterfaceFactory $exclusionslogicDataFactory
     * @param DataObjectHelper $dataObjectHelper
     * @param \Retailplace\MiraklSellerAdditionalField\Model\ResourceModel\ExclusionsLogic $resource
     * @param \Retailplace\MiraklSellerAdditionalField\Model\ResourceModel\ExclusionsLogic\Collection $resourceCollection
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        ExclusionsLogicInterfaceFactory $exclusionslogicDataFactory,
        DataObjectHelper $dataObjectHelper,
        \Retailplace\MiraklSellerAdditionalField\Model\ResourceModel\ExclusionsLogic $resource,
        \Retailplace\MiraklSellerAdditionalField\Model\ResourceModel\ExclusionsLogic\Collection $resourceCollection,
        array $data = []
    ) {
        $this->exclusionslogicDataFactory = $exclusionslogicDataFactory;
        $this->dataObjectHelper = $dataObjectHelper;
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
    }

    /**
     * Retrieve exclusionslogic model with exclusionslogic data
     * @return ExclusionsLogicInterface
     */
    public function getDataModel()
    {
        $exclusionslogicData = $this->getData();
        
        $exclusionslogicDataObject = $this->exclusionslogicDataFactory->create();
        $this->dataObjectHelper->populateWithArray(
            $exclusionslogicDataObject,
            $exclusionslogicData,
            ExclusionsLogicInterface::class
        );
        
        return $exclusionslogicDataObject;
    }
}

