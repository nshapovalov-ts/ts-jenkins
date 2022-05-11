<?php

/**
 * Retailplace_ChannelPricing
 *
 * @copyright   Copyright (c) 2021 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Dmitriy Fionov <dmitriy@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\ChannelPricing\Setup\Patch\Data;

use Amasty\CustomerAttributes\Api\Data\RelationDetailInterface;
use Amasty\CustomerAttributes\Api\Data\RelationDetailInterfaceFactory;
use Amasty\CustomerAttributes\Api\Data\RelationInterfaceFactory;
use Amasty\CustomerAttributes\Api\RelationRepositoryInterface;
use Exception;
use Magento\Customer\Model\Customer;
use Magento\Eav\Api\AttributeRepositoryInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Psr\Log\LoggerInterface;
use Retailplace\ChannelPricing\Model\GroupProcessor\AuPost;
use Retailplace\CustomerAccount\Block\Widget\BusinessType;
use Retailplace\CustomerAccount\Block\Widget\LpoCode;

/**
 * Class AddLpoCodeRelation
 */
class AddLpoCodeRelation implements DataPatchInterface
{
    /** @var \Amasty\CustomerAttributes\Api\RelationRepositoryInterface */
    private $relationRepository;

    /** @var \Amasty\CustomerAttributes\Api\Data\RelationInterfaceFactory */
    private $relationFactory;

    /** @var \Amasty\CustomerAttributes\Api\Data\RelationDetailInterfaceFactory */
    private $relationDetailFactory;

    /** @var \Magento\Eav\Api\AttributeRepositoryInterface */
    private $attributeRepository;

    /** @var \Psr\Log\LoggerInterface */
    private $logger;

    /**
     * AddLpoCodeRelation constructor.
     *
     * @param \Amasty\CustomerAttributes\Api\RelationRepositoryInterface $relationRepository
     * @param \Amasty\CustomerAttributes\Api\Data\RelationInterfaceFactory $relationFactory
     * @param \Amasty\CustomerAttributes\Api\Data\RelationDetailInterfaceFactory $relationDetailFactory
     * @param \Magento\Eav\Api\AttributeRepositoryInterface $attributeRepository
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        RelationRepositoryInterface $relationRepository,
        RelationInterfaceFactory $relationFactory,
        RelationDetailInterfaceFactory $relationDetailFactory,
        AttributeRepositoryInterface $attributeRepository,
        LoggerInterface $logger
    ) {
        $this->relationRepository = $relationRepository;
        $this->relationFactory = $relationFactory;
        $this->relationDetailFactory = $relationDetailFactory;
        $this->attributeRepository = $attributeRepository;
        $this->logger = $logger;
    }

    /**
     * Apply patch
     */
    public function apply()
    {
        $this->addRelation();
    }

    /**
     * Get array of patches that have to be executed prior to this.
     *
     * @return string[]
     */
    public static function getDependencies(): array
    {
        return [
            AddAuPostData::class
        ];
    }

    /**
     * Get aliases (previous names) for the patch.
     *
     * @return string[]
     */
    public function getAliases(): array
    {
        return [];
    }

    /**
     * Add Dependency for LPO Code attribute from Business Type LPO option
     */
    private function addRelation()
    {
        /** @var \Amasty\CustomerAttributes\Api\Data\RelationInterface $relation */
        $relation = $this->relationFactory->create();
        $relation->setName('LPO Code from Business Type LPO');
        $relation->setDetails([$this->getRelationDetails()]);

        try {
            $this->relationRepository->save($relation);
        } catch (Exception $e) {
            $this->logger->error($e->getMessage());
        }

    }

    /**
     * Get Relation Details
     *
     * @return \Amasty\CustomerAttributes\Api\Data\RelationDetailInterface
     */
    private function getRelationDetails(): RelationDetailInterface
    {
        /** @var \Amasty\CustomerAttributes\Api\Data\RelationDetailInterface $relationDetail */
        $relationDetail = $this->relationDetailFactory->create();

        try {
            $parentAttribute = $this->attributeRepository->get(
                Customer::ENTITY,
                BusinessType::ATTRIBUTE_CODE
            );
            $dependentAttribute = $this->attributeRepository->get(
                Customer::ENTITY,
                LpoCode::ATTRIBUTE_CODE
            );
        } catch (Exception $e) {
            $this->logger->error($e->getMessage());
            $parentAttribute = null;
            $dependentAttribute = null;
        }

        if ($parentAttribute && $dependentAttribute) {
            $optionId = null;
            $options = $parentAttribute->getOptions();
            if ($options) {
                foreach ($options as $option) {
                    if ($option->getLabel() == AuPost::BUSINESS_TYPE_LPO_LABEL) {
                        $optionId = $option->getValue();
                    }
                }
            }

            if ($optionId) {
                $relationDetail->setAttributeId($parentAttribute->getAttributeId());
                $relationDetail->setDependentAttributeId($dependentAttribute->getAttributeId());
                $relationDetail->setOptionId($optionId);
            }
        }

        return $relationDetail;
    }
}
