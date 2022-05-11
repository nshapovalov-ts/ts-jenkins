<?php

/**
 * Retailplace_ChannelPricing
 *
 * @copyright   Copyright (c) 2021 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Dmitriy Fionov <dmitriy@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\ChannelPricing\Block\Adminhtml\Form\Field;

use Exception;
use Magento\Customer\Api\Data\GroupInterface;
use Magento\Customer\Api\GroupRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilderFactory;
use Magento\Framework\Api\SortOrderBuilderFactory;
use Magento\Framework\View\Element\Context;
use Magento\Framework\View\Element\Html\Select;
use Psr\Log\LoggerInterface;

/**
 * Class GroupsField
 */
class GroupsField extends Select
{
    /** @var int */
    public const HIDE_ATTRIBUTE_VALUE = -1;

    /** @var \Magento\Customer\Api\GroupRepositoryInterface */
    private $groupRepository;

    /** @var \Magento\Framework\Api\SearchCriteriaBuilderFactory */
    private $searchCriteriaBuilderFactory;

    /** @var \Magento\Framework\Api\SortOrderBuilderFactory */
    private $sortOrderBuilderFactory;

    /** @var \Psr\Log\LoggerInterface */
    private $logger;

    /**
     * GroupsField constructor.
     *
     * @param \Magento\Framework\View\Element\Context $context
     * @param \Magento\Customer\Api\GroupRepositoryInterface $groupRepository
     * @param \Magento\Framework\Api\SearchCriteriaBuilderFactory $searchCriteriaBuilderFactory
     * @param \Magento\Framework\Api\SortOrderBuilderFactory $sortOrderBuilderFactory
     * @param \Psr\Log\LoggerInterface $logger
     * @param array $data
     */
    public function __construct(
        Context $context,
        GroupRepositoryInterface $groupRepository,
        SearchCriteriaBuilderFactory $searchCriteriaBuilderFactory,
        SortOrderBuilderFactory $sortOrderBuilderFactory,
        LoggerInterface $logger,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->groupRepository = $groupRepository;
        $this->searchCriteriaBuilderFactory = $searchCriteriaBuilderFactory;
        $this->sortOrderBuilderFactory = $sortOrderBuilderFactory;
        $this->logger = $logger;
    }

    /**
     * Render Options
     *
     * @return string
     */
    protected function _toHtml()
    {
        $this->setExtraParams('multiple="multiple"');
        if (!$this->getOptions()) {
            $this->setOptions($this->getGroupsList());
        }

        return parent::_toHtml();
    }

    /**
     * Get Customer Groups list
     *
     * @return array
     */
    protected function getGroupsList(): array
    {
        $options = [
            [
                'value' => self::HIDE_ATTRIBUTE_VALUE,
                'label' => 'Hide always',
                '__disableTmpl' => 1
            ]
        ];

        /** @var \Magento\Framework\Api\SortOrderBuilder $sortOrderBuilder */
        $sortOrderBuilder = $this->sortOrderBuilderFactory->create();
        $sortOrder = $sortOrderBuilder
            ->setField(GroupInterface::CODE)
            ->setAscendingDirection()
            ->create();

        $searchCriteriaBuilder = $this->searchCriteriaBuilderFactory->create();
        $searchCriteria = $searchCriteriaBuilder
            ->addSortOrder($sortOrder)
            ->create();

        try {
            $groups = $this->groupRepository->getList($searchCriteria);
            foreach ($groups->getItems() as $group) {
                $options[] = [
                    'value' => $group->getId(),
                    'label' => $group->getCode(),
                    '__disableTmpl' => 1
                ];
            }
        } catch (Exception $e) {
            $this->logger->error($e->getMessage());
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
        return $this->setName($value . '[]');
    }
}
