<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Retailplace\MiraklSellerAdditionalField\Model;

use Magento\Framework\Api\DataObjectHelper;
use Magento\Framework\Api\ExtensibleDataObjectConverter;
use Magento\Framework\Api\ExtensionAttribute\JoinProcessorInterface;
use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Reflection\DataObjectProcessor;
use Magento\Store\Model\StoreManagerInterface;
use Retailplace\MiraklSellerAdditionalField\Api\Data\IndustryExclusionsInterfaceFactory;
use Retailplace\MiraklSellerAdditionalField\Api\Data\IndustryExclusionsSearchResultsInterfaceFactory;
use Retailplace\MiraklSellerAdditionalField\Api\IndustryExclusionsRepositoryInterface;
use Retailplace\MiraklSellerAdditionalField\Model\ResourceModel\IndustryExclusions as ResourceIndustryExclusions;
use Retailplace\MiraklSellerAdditionalField\Model\ResourceModel\IndustryExclusions\CollectionFactory as IndustryExclusionsCollectionFactory;

class IndustryExclusionsRepository implements IndustryExclusionsRepositoryInterface
{

    protected $resource;

    protected $industryExclusionsFactory;

    protected $industryExclusionsCollectionFactory;

    protected $searchResultsFactory;

    protected $dataObjectHelper;

    protected $dataObjectProcessor;

    protected $dataIndustryExclusionsFactory;

    protected $extensionAttributesJoinProcessor;

    private $storeManager;

    private $collectionProcessor;

    protected $extensibleDataObjectConverter;

    /**
     * @param ResourceIndustryExclusions $resource
     * @param IndustryExclusionsFactory $industryExclusionsFactory
     * @param IndustryExclusionsInterfaceFactory $dataIndustryExclusionsFactory
     * @param IndustryExclusionsCollectionFactory $industryExclusionsCollectionFactory
     * @param IndustryExclusionsSearchResultsInterfaceFactory $searchResultsFactory
     * @param DataObjectHelper $dataObjectHelper
     * @param DataObjectProcessor $dataObjectProcessor
     * @param StoreManagerInterface $storeManager
     * @param CollectionProcessorInterface $collectionProcessor
     * @param JoinProcessorInterface $extensionAttributesJoinProcessor
     * @param ExtensibleDataObjectConverter $extensibleDataObjectConverter
     */
    public function __construct(
        ResourceIndustryExclusions $resource,
        IndustryExclusionsFactory $industryExclusionsFactory,
        IndustryExclusionsInterfaceFactory $dataIndustryExclusionsFactory,
        IndustryExclusionsCollectionFactory $industryExclusionsCollectionFactory,
        IndustryExclusionsSearchResultsInterfaceFactory $searchResultsFactory,
        DataObjectHelper $dataObjectHelper,
        DataObjectProcessor $dataObjectProcessor,
        StoreManagerInterface $storeManager,
        CollectionProcessorInterface $collectionProcessor,
        JoinProcessorInterface $extensionAttributesJoinProcessor,
        ExtensibleDataObjectConverter $extensibleDataObjectConverter
    ) {
        $this->resource = $resource;
        $this->industryExclusionsFactory = $industryExclusionsFactory;
        $this->industryExclusionsCollectionFactory = $industryExclusionsCollectionFactory;
        $this->searchResultsFactory = $searchResultsFactory;
        $this->dataObjectHelper = $dataObjectHelper;
        $this->dataIndustryExclusionsFactory = $dataIndustryExclusionsFactory;
        $this->dataObjectProcessor = $dataObjectProcessor;
        $this->storeManager = $storeManager;
        $this->collectionProcessor = $collectionProcessor;
        $this->extensionAttributesJoinProcessor = $extensionAttributesJoinProcessor;
        $this->extensibleDataObjectConverter = $extensibleDataObjectConverter;
    }

    /**
     * {@inheritdoc}
     */
    public function save(
        \Retailplace\MiraklSellerAdditionalField\Api\Data\IndustryExclusionsInterface $industryExclusions
    ) {
        /* if (empty($industryExclusions->getStoreId())) {
            $storeId = $this->storeManager->getStore()->getId();
            $industryExclusions->setStoreId($storeId);
        } */
        
        $industryExclusionsData = $this->extensibleDataObjectConverter->toNestedArray(
            $industryExclusions,
            [],
            \Retailplace\MiraklSellerAdditionalField\Api\Data\IndustryExclusionsInterface::class
        );
        
        $industryExclusionsModel = $this->industryExclusionsFactory->create()->setData($industryExclusionsData);
        
        try {
            $this->resource->save($industryExclusionsModel);
        } catch (\Exception $exception) {
            throw new CouldNotSaveException(__(
                'Could not save the industryExclusions: %1',
                $exception->getMessage()
            ));
        }
        return $industryExclusionsModel->getDataModel();
    }

    /**
     * {@inheritdoc}
     */
    public function get($industryExclusionsId)
    {
        $industryExclusions = $this->industryExclusionsFactory->create();
        $this->resource->load($industryExclusions, $industryExclusionsId);
        if (!$industryExclusions->getId()) {
            throw new NoSuchEntityException(__('IndustryExclusions with id "%1" does not exist.', $industryExclusionsId));
        }
        return $industryExclusions->getDataModel();
    }

    /**
     * {@inheritdoc}
     */
    public function getList(
        \Magento\Framework\Api\SearchCriteriaInterface $criteria
    ) {
        $collection = $this->industryExclusionsCollectionFactory->create();
        
        $this->extensionAttributesJoinProcessor->process(
            $collection,
            \Retailplace\MiraklSellerAdditionalField\Api\Data\IndustryExclusionsInterface::class
        );
        
        $this->collectionProcessor->process($criteria, $collection);
        
        $searchResults = $this->searchResultsFactory->create();
        $searchResults->setSearchCriteria($criteria);
        
        $items = [];
        foreach ($collection as $model) {
            $items[] = $model->getDataModel();
        }
        
        $searchResults->setItems($items);
        $searchResults->setTotalCount($collection->getSize());
        return $searchResults;
    }

    /**
     * {@inheritdoc}
     */
    public function delete(
        \Retailplace\MiraklSellerAdditionalField\Api\Data\IndustryExclusionsInterface $industryExclusions
    ) {
        try {
            $industryExclusionsModel = $this->industryExclusionsFactory->create();
            $this->resource->load($industryExclusionsModel, $industryExclusions->getIndustryexclusionsId());
            $this->resource->delete($industryExclusionsModel);
        } catch (\Exception $exception) {
            throw new CouldNotDeleteException(__(
                'Could not delete the IndustryExclusions: %1',
                $exception->getMessage()
            ));
        }
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function deleteById($industryExclusionsId)
    {
        return $this->delete($this->get($industryExclusionsId));
    }
}

