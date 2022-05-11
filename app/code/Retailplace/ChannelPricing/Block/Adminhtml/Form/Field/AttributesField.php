<?php

/**
 * Retailplace_ChannelPricing
 *
 * @copyright   Copyright (c) 2021 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Dmitriy Fionov <dmitriy@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\ChannelPricing\Block\Adminhtml\Form\Field;

use Magento\Catalog\Model\Product;
use Magento\Eav\Api\AttributeRepositoryInterface;
use Magento\Eav\Api\Data\AttributeInterface;
use Magento\Framework\Api\SearchCriteriaBuilderFactory;
use Magento\Framework\Api\SortOrderBuilderFactory;
use Magento\Framework\View\Element\Context;
use Magento\Framework\View\Element\Html\Select;

/**
 * Class AttributesField
 */
class AttributesField extends Select
{
    /** @var \Magento\Eav\Api\AttributeRepositoryInterface */
    private $attributeRepository;

    /** @var \Magento\Framework\Api\SearchCriteriaBuilderFactory */
    private $searchCriteriaBuilderFactory;

    /** @var \Magento\Framework\Api\SortOrderBuilderFactory */
    private $sortOrderBuilderFactory;

    /**
     * AttributesField constructor.
     *
     * @param \Magento\Framework\View\Element\Context $context
     * @param \Magento\Eav\Api\AttributeRepositoryInterface $attributeRepository
     * @param \Magento\Framework\Api\SearchCriteriaBuilderFactory $searchCriteriaBuilderFactory
     * @param \Magento\Framework\Api\SortOrderBuilderFactory $sortOrderBuilderFactory
     * @param array $data
     */
    public function __construct(
        Context $context,
        AttributeRepositoryInterface $attributeRepository,
        SearchCriteriaBuilderFactory $searchCriteriaBuilderFactory,
        SortOrderBuilderFactory $sortOrderBuilderFactory,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->attributeRepository = $attributeRepository;
        $this->searchCriteriaBuilderFactory = $searchCriteriaBuilderFactory;
        $this->sortOrderBuilderFactory = $sortOrderBuilderFactory;
    }

    /**
     * Render Options
     *
     * @return string
     */
    protected function _toHtml()
    {
        if (!$this->getOptions()) {
            $this->setOptions($this->getAttributesList());
        }

        return parent::_toHtml();
    }

    /**
     * Get list of Product Attributes
     *
     * @return array
     */
    protected function getAttributesList(): array
    {
        $options = [];

        /** @var \Magento\Framework\Api\SortOrderBuilder $sortOrderBuilder */
        $sortOrderBuilder = $this->sortOrderBuilderFactory->create();
        $sortOrder = $sortOrderBuilder
            ->setField(AttributeInterface::FRONTEND_LABEL)
            ->setAscendingDirection()
            ->create();

        $searchCriteriaBuilder = $this->searchCriteriaBuilderFactory->create();
        $searchCriteria = $searchCriteriaBuilder
            ->addSortOrder($sortOrder)
            ->create();

        $attributes = $this->attributeRepository->getList(Product::ENTITY, $searchCriteria);

        foreach ($attributes->getItems() as $attribute) {
            if ($attribute->getDefaultFrontendLabel()) {
                $options[] = [
                    'value' => $attribute->getAttributeCode(),
                    'label' => $attribute->getDefaultFrontendLabel(),
                    '__disableTmpl' => 1
                ];
            }
        }

        return $options;
    }

    /**
     * Sets name for input element
     *
     * @param string $value
     * @return $this
     */
    public function setInputName($value)
    {
        return $this->setName($value);
    }
}
