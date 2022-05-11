<?php

/**
 * Retailplace_ChannelPricing
 *
 * @copyright   Copyright (c) 2021 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Dmitriy Fionov <dmitriy@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\ChannelPricing\Model;

use Exception;
use Magento\Catalog\Model\Product;
use Magento\Customer\Model\Group;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Eav\Api\AttributeRepositoryInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Psr\Log\LoggerInterface;
use Retailplace\ChannelPricing\Block\Adminhtml\Form\Field\AttributesVisibility;
use Retailplace\ChannelPricing\Block\Adminhtml\Form\Field\GroupsField;

/**
 * Class AttributesVisibilityManagement
 */
class AttributesVisibilityManagement
{
    /** @var string */
    public const XML_PATH_CHANNEL_PRICING_ATTRIBUTE_VISIBILITY_MAPPING = 'retailplace_channel_pricing/attributes_visibility/mapping';

    /** @var \Retailplace\ChannelPricing\Block\Adminhtml\Form\Field\AttributesVisibility */
    private $attributesVisibility;

    /** @var array */
    private $attributesVisibilityMapping;

    /** @var \Magento\Framework\App\Config\ScopeConfigInterface */
    private $config;

    /** @var \Magento\Customer\Model\Session */
    private $customerSession;

    /** @var \Magento\Eav\Api\AttributeRepositoryInterface */
    private $attributeRepository;

    /** @var string[] */
    private $attributeLabels;

    /** @var \Psr\Log\LoggerInterface */
    private $logger;

    /**
     * AttributesVisibilityManagement constructor.
     *
     * @param \Retailplace\ChannelPricing\Block\Adminhtml\Form\Field\AttributesVisibility $attributesVisibility
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $config
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Eav\Api\AttributeRepositoryInterface $attributeRepository
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        AttributesVisibility $attributesVisibility,
        ScopeConfigInterface $config,
        CustomerSession $customerSession,
        AttributeRepositoryInterface $attributeRepository,
        LoggerInterface $logger
    ) {
        $this->attributesVisibility = $attributesVisibility;
        $this->config = $config;
        $this->customerSession = $customerSession;
        $this->attributeRepository = $attributeRepository;
        $this->logger = $logger;
    }

    /**
     * Check if we can show Attribute depends on Customer Group
     *
     * @param string $attributeCode
     * @return bool
     */
    public function checkAttributeVisibility(string $attributeCode): bool
    {
        $result = true;
        $mapping = $this->getAttributesVisibilityMapping();
        foreach ($mapping as $row) {
            if ($row['attribute_code'] == $attributeCode) {
                if ($row['groups'][0] == GroupsField::HIDE_ATTRIBUTE_VALUE
                    || !in_array($this->getCustomerGroup(), $row['groups'])) {
                    $result = false;
                    break;
                }
            }
        }

        return $result;
    }

    /**
     * Get Attribute Label by Code
     *
     * @param string $attributeCode
     * @return string
     */
    public function getAttributeLabelByCode(string $attributeCode): string
    {
        if (!isset($this->attributeLabels[$attributeCode])) {
            $label = '';
            try {
                $attribute = $this->attributeRepository->get(Product::ENTITY, $attributeCode);
                if ($attribute) {
                    $label = $attribute->getDefaultFrontendLabel();
                }
            } catch (Exception $e) {
                $this->logger->error($e->getMessage());
            }

            $this->attributeLabels[$attributeCode] = $label;
        }

        return $this->attributeLabels[$attributeCode];
    }

    /**
     * Get current Customer Group Id
     *
     * @return int
     */
    private function getCustomerGroup(): int
    {
        try {
            $groupId = $this->customerSession->getCustomerGroupId();
        } catch (Exception $e) {
            $this->logger->error($e->getMessage());
            $groupId = Group::NOT_LOGGED_IN_ID;
        }

        return (int) $groupId;
    }

    /**
     * Get Attributes visibility mapping fron Config
     *
     * @return array
     */
    private function getAttributesVisibilityMapping(): array
    {
        if (!$this->attributesVisibilityMapping) {
            $mapping = $this->config->getValue(self::XML_PATH_CHANNEL_PRICING_ATTRIBUTE_VISIBILITY_MAPPING);
            $this->attributesVisibilityMapping = $this->attributesVisibility->getValuesArray($mapping);
        }

        return $this->attributesVisibilityMapping;
    }
}
