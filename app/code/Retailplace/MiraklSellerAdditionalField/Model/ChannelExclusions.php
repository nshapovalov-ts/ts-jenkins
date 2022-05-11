<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Retailplace\MiraklSellerAdditionalField\Model;

use Magento\Framework\Api\DataObjectHelper;
use Retailplace\MiraklSellerAdditionalField\Api\Data\ChannelExclusionsInterface;
use Retailplace\MiraklSellerAdditionalField\Api\Data\ChannelExclusionsInterfaceFactory;

class ChannelExclusions extends \Magento\Framework\Model\AbstractModel
{

    protected $channelexclusionsDataFactory;

    protected $dataObjectHelper;

    protected $_eventPrefix = 'retailplace_miraklselleradditionalfield_channelexclusions';

    /**
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param ChannelExclusionsInterfaceFactory $channelexclusionsDataFactory
     * @param DataObjectHelper $dataObjectHelper
     * @param \Retailplace\MiraklSellerAdditionalField\Model\ResourceModel\ChannelExclusions $resource
     * @param \Retailplace\MiraklSellerAdditionalField\Model\ResourceModel\ChannelExclusions\Collection $resourceCollection
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        ChannelExclusionsInterfaceFactory $channelexclusionsDataFactory,
        DataObjectHelper $dataObjectHelper,
        \Retailplace\MiraklSellerAdditionalField\Model\ResourceModel\ChannelExclusions $resource,
        \Retailplace\MiraklSellerAdditionalField\Model\ResourceModel\ChannelExclusions\Collection $resourceCollection,
        array $data = []
    ) {
        $this->channelexclusionsDataFactory = $channelexclusionsDataFactory;
        $this->dataObjectHelper = $dataObjectHelper;
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
    }

    /**
     * Retrieve channelexclusions model with channelexclusions data
     * @return ChannelExclusionsInterface
     */
    public function getDataModel()
    {
        $channelexclusionsData = $this->getData();
        
        $channelexclusionsDataObject = $this->channelexclusionsDataFactory->create();
        $this->dataObjectHelper->populateWithArray(
            $channelexclusionsDataObject,
            $channelexclusionsData,
            ChannelExclusionsInterface::class
        );
        
        return $channelexclusionsDataObject;
    }
}

