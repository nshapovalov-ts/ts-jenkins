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
use Retailplace\MiraklSellerAdditionalField\Api\Data\ExclusionsLogicInterfaceFactory;
use Retailplace\MiraklSellerAdditionalField\Api\Data\ExclusionsLogicSearchResultsInterfaceFactory;
use Retailplace\MiraklSellerAdditionalField\Api\ExclusionsLogicRepositoryInterface;
use Retailplace\MiraklSellerAdditionalField\Model\ResourceModel\ExclusionsLogic as ResourceExclusionsLogic;
use Retailplace\MiraklSellerAdditionalField\Model\ResourceModel\ExclusionsLogic\CollectionFactory as ExclusionsLogicCollectionFactory;

class ExclusionsLogicRepository implements ExclusionsLogicRepositoryInterface
{

    protected $resource;

    protected $exclusionsLogicFactory;

    protected $exclusionsLogicCollectionFactory;

    protected $searchResultsFactory;

    protected $dataObjectHelper;

    protected $dataObjectProcessor;

    protected $dataExclusionsLogicFactory;

    protected $extensionAttributesJoinProcessor;

    private $storeManager;

    private $collectionProcessor;

    protected $extensibleDataObjectConverter;

    /**
     * @param ResourceExclusionsLogic $resource
     * @param ExclusionsLogicFactory $exclusionsLogicFactory
     * @param ExclusionsLogicInterfaceFactory $dataExclusionsLogicFactory
     * @param ExclusionsLogicCollectionFactory $exclusionsLogicCollectionFactory
     * @param ExclusionsLogicSearchResultsInterfaceFactory $searchResultsFactory
     * @param DataObjectHelper $dataObjectHelper
     * @param DataObjectProcessor $dataObjectProcessor
     * @param StoreManagerInterface $storeManager
     * @param CollectionProcessorInterface $collectionProcessor
     * @param JoinProcessorInterface $extensionAttributesJoinProcessor
     * @param ExtensibleDataObjectConverter $extensibleDataObjectConverter
     */
    public function __construct(
        ResourceExclusionsLogic $resource,
        ExclusionsLogicFactory $exclusionsLogicFactory,
        ExclusionsLogicInterfaceFactory $dataExclusionsLogicFactory,
        ExclusionsLogicCollectionFactory $exclusionsLogicCollectionFactory,
        ExclusionsLogicSearchResultsInterfaceFactory $searchResultsFactory,
        DataObjectHelper $dataObjectHelper,
        DataObjectProcessor $dataObjectProcessor,
        StoreManagerInterface $storeManager,
        CollectionProcessorInterface $collectionProcessor,
        JoinProcessorInterface $extensionAttributesJoinProcessor,
        ExtensibleDataObjectConverter $extensibleDataObjectConverter
    ) {
        $this->resource = $resource;
        $this->exclusionsLogicFactory = $exclusionsLogicFactory;
        $this->exclusionsLogicCollectionFactory = $exclusionsLogicCollectionFactory;
        $this->searchResultsFactory = $searchResultsFactory;
        $this->dataObjectHelper = $dataObjectHelper;
        $this->dataExclusionsLogicFactory = $dataExclusionsLogicFactory;
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
        \Retailplace\MiraklSellerAdditionalField\Api\Data\ExclusionsLogicInterface $exclusionsLogic
    ) {
        /* if (empty($exclusionsLogic->getStoreId())) {
            $storeId = $this->storeManager->getStore()->getId();
            $exclusionsLogic->setStoreId($storeId);
        } */
        
        $exclusionsLogicData = $this->extensibleDataObjectConverter->toNestedArray(
            $exclusionsLogic,
            [],
            \Retailplace\MiraklSellerAdditionalField\Api\Data\ExclusionsLogicInterface::class
        );
        
        $exclusionsLogicModel = $this->exclusionsLogicFactory->create()->setData($exclusionsLogicData);
        
        try {
            $this->resource->save($exclusionsLogicModel);
        } catch (\Exception $exception) {
            throw new CouldNotSaveException(__(
                'Could not save the exclusionsLogic: %1',
                $exception->getMessage()
            ));
        }
        return $exclusionsLogicModel->getDataModel();
    }

    /**
     * {@inheritdoc}
     */
    public function get($exclusionsLogicId)
    {
        $exclusionsLogic = $this->exclusionsLogicFactory->create();
        $this->resource->load($exclusionsLogic, $exclusionsLogicId);
        if (!$exclusionsLogic->getId()) {
            throw new NoSuchEntityException(__('ExclusionsLogic with id "%1" does not exist.', $exclusionsLogicId));
        }
        return $exclusionsLogic->getDataModel();
    }

    /**
     * {@inheritdoc}
     */
    public function getList(
        \Magento\Framework\Api\SearchCriteriaInterface $criteria
    ) {
        $collection = $this->exclusionsLogicCollectionFactory->create();
        
        $this->extensionAttributesJoinProcessor->process(
            $collection,
            \Retailplace\MiraklSellerAdditionalField\Api\Data\ExclusionsLogicInterface::class
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
        \Retailplace\MiraklSellerAdditionalField\Api\Data\ExclusionsLogicInterface $exclusionsLogic
    ) {
        try {
            $exclusionsLogicModel = $this->exclusionsLogicFactory->create();
            $this->resource->load($exclusionsLogicModel, $exclusionsLogic->getExclusionslogicId());
            $this->resource->delete($exclusionsLogicModel);
        } catch (\Exception $exception) {
            throw new CouldNotDeleteException(__(
                'Could not delete the ExclusionsLogic: %1',
                $exception->getMessage()
            ));
        }
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function deleteById($exclusionsLogicId)
    {
        return $this->delete($this->get($exclusionsLogicId));
    }
}

