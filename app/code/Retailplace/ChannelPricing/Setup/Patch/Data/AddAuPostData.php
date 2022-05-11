<?php

/**
 * Retailplace_ChannelPricing
 *
 * @copyright   Copyright (c) 2021 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Dmitriy Fionov <dmitriy@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\ChannelPricing\Setup\Patch\Data;

use Exception;
use Magento\Customer\Api\CustomerMetadataInterface;
use Magento\Customer\Api\Data\GroupInterfaceFactory;
use Magento\Customer\Api\GroupRepositoryInterface;
use Magento\Customer\Model\Customer;
use Magento\Eav\Api\AttributeOptionManagementInterface;
use Magento\Eav\Api\Data\AttributeOptionInterfaceFactory;
use Magento\Eav\Api\Data\AttributeOptionLabelInterfaceFactory;
use Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Customer\Setup\CustomerSetupFactory;
use Magento\Customer\Model\AttributeFactory;
use Magento\Eav\Model\Entity\Attribute\SetFactory as AttributeSetFactory;
use Magento\Customer\Model\ResourceModel\Attribute as AttributeResourceModel;
use Psr\Log\LoggerInterface;
use Retailplace\ChannelPricing\Model\GroupProcessor\AuPost;
use Retailplace\CustomerAccount\Block\Widget\BusinessType;
use Retailplace\CustomerAccount\Block\Widget\LpoCode;

/**
 * Class AddAuPostData
 */
class AddAuPostData implements DataPatchInterface
{
    /** @var \Magento\Customer\Api\Data\GroupInterfaceFactory */
    private $customerGroupFactory;

    /** @var \Magento\Customer\Api\GroupRepositoryInterface */
    private $customerGroupRepository;

    /** @var \Magento\Framework\Setup\ModuleDataSetupInterface */
    private $moduleDataSetup;

    /** @var \Magento\Customer\Setup\CustomerSetupFactory */
    private $customerSetupFactory;

    /** @var \Magento\Customer\Model\AttributeFactory */
    private $customerAttributeFactory;

    /** @var \Magento\Eav\Model\Entity\Attribute\SetFactory */
    private $attributeSetFactory;

    /** @var \Magento\Customer\Model\ResourceModel\Attribute */
    private $attributeResourceModel;

    /** @var \Magento\Eav\Api\AttributeOptionManagementInterface */
    private $attributeOptionManagement;

    /** @var \Magento\Eav\Api\Data\AttributeOptionInterfaceFactory */
    private $optionFactory;

    /** @var \Magento\Eav\Api\Data\AttributeOptionLabelInterfaceFactory */
    private $optionLabelFactory;

    /** @var \Magento\Store\Model\StoreManagerInterface */
    private $storeManager;

    /** @var \Psr\Log\LoggerInterface */
    private $logger;

    /**
     * AddAuPostData constructor.
     *
     * @param \Magento\Customer\Api\Data\GroupInterfaceFactory $customerGroupFactory
     * @param \Magento\Customer\Api\GroupRepositoryInterface $customerGroupRepository
     * @param \Magento\Framework\Setup\ModuleDataSetupInterface $moduleDataSetup
     * @param \Magento\Customer\Setup\CustomerSetupFactory $customerSetupFactory
     * @param \Magento\Customer\Model\AttributeFactory $customerAttributeFactory
     * @param \Magento\Eav\Model\Entity\Attribute\SetFactory $attributeSetFactory
     * @param \Magento\Customer\Model\ResourceModel\Attribute $attributeResourceModel
     * @param \Magento\Eav\Api\AttributeOptionManagementInterface $attributeOptionManagement
     * @param \Magento\Eav\Api\Data\AttributeOptionInterfaceFactory $optionFactory
     * @param \Magento\Eav\Api\Data\AttributeOptionLabelInterfaceFactory $optionLabelFactory
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        GroupInterfaceFactory $customerGroupFactory,
        GroupRepositoryInterface $customerGroupRepository,
        ModuleDataSetupInterface $moduleDataSetup,
        CustomerSetupFactory $customerSetupFactory,
        AttributeFactory $customerAttributeFactory,
        AttributeSetFactory $attributeSetFactory,
        AttributeResourceModel $attributeResourceModel,
        AttributeOptionManagementInterface $attributeOptionManagement,
        AttributeOptionInterfaceFactory $optionFactory,
        AttributeOptionLabelInterfaceFactory $optionLabelFactory,
        StoreManagerInterface $storeManager,
        LoggerInterface $logger
    ) {
        $this->customerGroupFactory = $customerGroupFactory;
        $this->customerGroupRepository = $customerGroupRepository;
        $this->moduleDataSetup = $moduleDataSetup;
        $this->customerSetupFactory = $customerSetupFactory;
        $this->customerAttributeFactory = $customerAttributeFactory;
        $this->attributeSetFactory = $attributeSetFactory;
        $this->attributeResourceModel = $attributeResourceModel;
        $this->attributeOptionManagement = $attributeOptionManagement;
        $this->optionFactory = $optionFactory;
        $this->optionLabelFactory = $optionLabelFactory;
        $this->storeManager = $storeManager;
        $this->logger = $logger;
    }

    /**
     * Apply Patch
     */
    public function apply()
    {
        $this->addGroup();
        $this->addLpoOptionToBusinessType();
        $this->addLpoCodeAttribute();
    }

    /**
     * Add new option to Business Type attribute
     */
    private function addLpoOptionToBusinessType()
    {
        $newValueData = [
            'label' => 'LPO',
            'sortOrder' => 6,
            'isDefault' => false
        ];

        $attribute = $this->customerAttributeFactory->create();
        $this->attributeResourceModel->load(
            $attribute,
            BusinessType::ATTRIBUTE_CODE,
            'attribute_code'
        );

        try {
            $storeId = $this->storeManager->getStore()->getId();
        } catch (Exception $e) {
            $this->logger->error($e->getMessage());
            $storeId = 0;
        }

        /** @var \Magento\Eav\Api\Data\AttributeOptionLabelInterface $optionLabel */
        $optionLabel = $this->optionLabelFactory->create();
        $optionLabel->setStoreId($storeId);
        $optionLabel->setLabel($newValueData['label']);

        /** @var \Magento\Eav\Api\Data\AttributeOptionInterface|\Magento\Eav\Model\Entity\Attribute\Option $option */
        $option = $this->optionFactory->create();
        $option->setLabel($optionLabel->getLabel());
        $option->setStoreLabels([$optionLabel]);
        $option->setSortOrder($newValueData['sortOrder']);
        $option->setIsDefault($newValueData['isDefault']);

        try {
            $this->attributeOptionManagement->add(Customer::ENTITY, $attribute->getAttributeId(), $option);
        } catch (Exception $e) {
            $this->logger->error($e->getMessage());
        }
    }

    /**
     * Add new attribute
     */
    private function addLpoCodeAttribute()
    {
        $this->moduleDataSetup->getConnection()->startSetup();

        $attributeData = [
            'type' => 'varchar',
            'label' => 'Store code',
            'input' => 'text',
            'required' => false,
            'frontend_class' => 'validate-length maximum-length-6 minimum-length-6',
            'visible' => true,
            'position' => 500,
            'global' => ScopedAttributeInterface::SCOPE_GLOBAL,
            'backend' => '',
            'system' => false,
            'user_defined' => true
        ];

        try {
            /** @var \Magento\Customer\Setup\CustomerSetup $customerSetup */
            $customerSetup = $this->customerSetupFactory->create(['setup' => $this->moduleDataSetup]);
            $customerSetup->addAttribute(
                Customer::ENTITY,
                LpoCode::ATTRIBUTE_CODE,
                $attributeData
            );

            $attribute = $this->customerAttributeFactory->create();
            $this->attributeResourceModel->load(
                $attribute,
                LpoCode::ATTRIBUTE_CODE,
                'attribute_code'
            );

            $customerEntity = $customerSetup
                ->getEavConfig()
                ->getEntityType(CustomerMetadataInterface::ENTITY_TYPE_CUSTOMER);
            $attributeSetId = $customerEntity->getDefaultAttributeSetId();

            /** @var \Magento\Eav\Model\Entity\Attribute\Set $attributeSet */
            $attributeSet = $this->attributeSetFactory->create();
            $attributeGroupId = $attributeSet->getDefaultGroupId($attributeSetId);

            $data = [
                'attribute_set_id' => $attributeSetId,
                'attribute_group_id' => $attributeGroupId,
                'used_in_forms' => [
                    'adminhtml_customer',
                    'customer_account_create',
                    'customer_account_edit'
                ],
            ];

            $attribute->addData($data);
            $this->attributeResourceModel->save($attribute);
        } catch (Exception $e) {
            $this->logger->error($e->getMessage());
        }
    }

    /**
     * Add new Customer Group
     */
    private function addGroup()
    {
        /** @var \Magento\Customer\Api\Data\GroupInterface $customerGroup */
        $customerGroup = $this->customerGroupFactory->create();
        $customerGroup->setCode(AuPost::GROUP_CODE);

        try {
            $this->customerGroupRepository->save($customerGroup);
        } catch (Exception $e) {
            $this->logger->error($e->getMessage());
        }
    }

    /**
     * Get array of patches that have to be executed prior to this
     *
     * @return string[]
     */
    public static function getDependencies(): array
    {
        return [];
    }

    /**
     * Get aliases (previous names) for the patch
     *
     * @return string[]
     */
    public function getAliases(): array
    {
        return [];
    }
}
