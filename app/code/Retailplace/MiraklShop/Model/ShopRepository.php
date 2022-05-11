<?php

/**
 * Retailplace_MiraklShop
 *
 * @copyright   Copyright (c) 2022 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Dmitriy Fionov <dmitriy@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\MiraklShop\Model;

use Magento\Framework\Api\SearchResults;
use Magento\Framework\Api\SearchCriteriaInterface;
use Retailplace\MiraklShop\Api\Data\ShopInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Retailplace\MiraklShop\Api\ShopRepositoryInterface;
use Retailplace\MiraklShop\Api\Data\ShopInterfaceFactory;
use Mirakl\Core\Model\ResourceModel\Shop as ShopResourceModel;
use Retailplace\MiraklShop\Api\Data\ShopSearchResultsInterfaceFactory;
use Mirakl\Core\Model\ResourceModel\Shop\CollectionFactory as ShopCollectionFactory;
use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;

/**
 * Class ShopRepository
 */
class ShopRepository implements ShopRepositoryInterface
{
    /** @var \Retailplace\MiraklShop\Api\Data\ShopInterface[] */
    private $shopsList = [];

    /** @var \Retailplace\MiraklShop\Api\Data\ShopInterfaceFactory */
    private $shopFactory;

    /** @var \Mirakl\Core\Model\ResourceModel\Shop */
    private $shopResourceModel;

    /** @var \Retailplace\MiraklShop\Api\Data\ShopSearchResultsInterfaceFactory */
    private $shopSearchResultsFactory;

    /** @var \Mirakl\Core\Model\ResourceModel\Shop\CollectionFactory */
    private $shopCollectionFactory;

    /** @var \Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface */
    private $collectionProcessor;

    /**
     * Constructor
     *
     * @param \Retailplace\MiraklShop\Api\Data\ShopInterfaceFactory $shopFactory
     * @param \Mirakl\Core\Model\ResourceModel\Shop $shopResourceModel
     * @param \Retailplace\MiraklShop\Api\Data\ShopSearchResultsInterfaceFactory $shopSearchResultsFactory
     * @param \Mirakl\Core\Model\ResourceModel\Shop\CollectionFactory $shopCollectionFactory
     * @param \Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface $collectionProcessor
     */
    public function __construct(
        ShopInterfaceFactory $shopFactory,
        ShopResourceModel $shopResourceModel,
        ShopSearchResultsInterfaceFactory $shopSearchResultsFactory,
        ShopCollectionFactory $shopCollectionFactory,
        CollectionProcessorInterface $collectionProcessor
    ) {
        $this->shopFactory = $shopFactory;
        $this->shopResourceModel = $shopResourceModel;
        $this->shopSearchResultsFactory = $shopSearchResultsFactory;
        $this->shopCollectionFactory = $shopCollectionFactory;
        $this->collectionProcessor = $collectionProcessor;
    }

    /**
     * Get Shop Entity by ID
     *
     * @param int $shopId
     * @return \Retailplace\MiraklShop\Api\Data\ShopInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getById(int $shopId): ShopInterface
    {
        if (!isset($this->shopsList[$shopId])) {
            /** @var \Retailplace\MiraklShop\Api\Data\ShopInterface $shop */
            $shop = $this->shopFactory->create();
            $this->shopResourceModel->load($shop, $shopId);
            if (!$shop->getId()) {
                throw new NoSuchEntityException(__('Unable to find Shop with ID: %1', $shopId));
            }
            $this->shopsList[$shopId] = $shop;
        }

        return $this->shopsList[$shopId];
    }

    /**
     * Save Shop Entity
     *
     * @param \Retailplace\MiraklShop\Api\Data\ShopInterface $shop
     * @return \Retailplace\MiraklShop\Api\Data\ShopInterface
     * @throws \Magento\Framework\Exception\AlreadyExistsException
     */
    public function save(ShopInterface $shop): ShopInterface
    {
        $this->shopResourceModel->save($shop);

        return $shop;
    }

    /**
     * Delete Shop Entity
     *
     * @param \Retailplace\MiraklShop\Api\Data\ShopInterface $shop
     * @return bool
     * @throws \Exception
     */
    public function delete(ShopInterface $shop): bool
    {
        unset($this->shopsList[$shop->getId()]);
        $this->shopResourceModel->delete($shop);

        return true;
    }

    /**
     * Delete Shop Entity by ID
     *
     * @param int $shopId
     * @return bool
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Exception
     */
    public function deleteById(int $shopId): bool
    {
        $shop = $this->getById($shopId);

        return $this->delete($shop);
    }

    /**
     * Get Shops List
     *
     * @param \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
     * @return \Retailplace\MiraklShop\Api\Data\ShopSearchResultsInterface|\Magento\Framework\Api\SearchResults
     */
    public function getList(SearchCriteriaInterface $searchCriteria): SearchResults
    {
        /** @var \Retailplace\MiraklShop\Api\Data\ShopSearchResultsInterface $searchResult */
        $searchResult = $this->shopSearchResultsFactory->create();

        /** @var \Mirakl\Core\Model\ResourceModel\Shop\Collection $collection */
        $collection = $this->shopCollectionFactory->create();

        $this->collectionProcessor->process($searchCriteria, $collection);

        /** @var \Retailplace\MiraklShop\Api\Data\ShopInterface[] $items */
        $items = $collection->getItems();

        $searchResult->setSearchCriteria($searchCriteria);
        $searchResult->setItems($items);
        $searchResult->setTotalCount($collection->getSize());

        return $searchResult;
    }
}
