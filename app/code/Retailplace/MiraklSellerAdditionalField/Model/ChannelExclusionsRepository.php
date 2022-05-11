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
use Retailplace\MiraklSellerAdditionalField\Api\ChannelExclusionsRepositoryInterface;
use Retailplace\MiraklSellerAdditionalField\Api\Data\ChannelExclusionsInterfaceFactory;
use Retailplace\MiraklSellerAdditionalField\Api\Data\ChannelExclusionsSearchResultsInterfaceFactory;
use Retailplace\MiraklSellerAdditionalField\Model\ResourceModel\ChannelExclusions as ResourceChannelExclusions;
use Retailplace\MiraklSellerAdditionalField\Model\ResourceModel\ChannelExclusions\CollectionFactory as ChannelExclusionsCollectionFactory;

class ChannelExclusionsRepository implements ChannelExclusionsRepositoryInterface
{

    protected $resource;

    protected $channelExclusionsFactory;

    protected $channelExclusionsCollectionFactory;

    protected $searchResultsFactory;

    protected $dataObjectHelper;

    protected $dataObjectProcessor;

    protected $dataChannelExclusionsFactory;

    protected $extensionAttributesJoinProcessor;

    private $storeManager;

    private $collectionProcessor;

    protected $extensibleDataObjectConverter;

    /**
     * @param ResourceChannelExclusions $resource
     * @param ChannelExclusionsFactory $channelExclusionsFactory
     * @param ChannelExclusionsInterfaceFactory $dataChannelExclusionsFactory
     * @param ChannelExclusionsCollectionFactory $channelExclusionsCollectionFactory
     * @param ChannelExclusionsSearchResultsInterfaceFactory $searchResultsFactory
     * @param DataObjectHelper $dataObjectHelper
     * @param DataObjectProcessor $dataObjectProcessor
     * @param StoreManagerInterface $storeManager
     * @param CollectionProcessorInterface $collectionProcessor
     * @param JoinProcessorInterface $extensionAttributesJoinProcessor
     * @param ExtensibleDataObjectConverter $extensibleDataObjectConverter
     */
    public function __construct(
        ResourceChannelExclusions $resource,
        ChannelExclusionsFactory $channelExclusionsFactory,
        ChannelExclusionsInterfaceFactory $dataChannelExclusionsFactory,
        ChannelExclusionsCollectionFactory $channelExclusionsCollectionFactory,
        ChannelExclusionsSearchResultsInterfaceFactory $searchResultsFactory,
        DataObjectHelper $dataObjectHelper,
        DataObjectProcessor $dataObjectProcessor,
        StoreManagerInterface $storeManager,
        CollectionProcessorInterface $collectionProcessor,
        JoinProcessorInterface $extensionAttributesJoinProcessor,
        ExtensibleDataObjectConverter $extensibleDataObjectConverter
    ) {
        $this->resource = $resource;
        $this->channelExclusionsFactory = $channelExclusionsFactory;
        $this->channelExclusionsCollectionFactory = $channelExclusionsCollectionFactory;
        $this->searchResultsFactory = $searchResultsFactory;
        $this->dataObjectHelper = $dataObjectHelper;
        $this->dataChannelExclusionsFactory = $dataChannelExclusionsFactory;
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
        \Retailplace\MiraklSellerAdditionalField\Api\Data\ChannelExclusionsInterface $channelExclusions
    ) {
        /* if (empty($channelExclusions->getStoreId())) {
            $storeId = $this->storeManager->getStore()->getId();
            $channelExclusions->setStoreId($storeId);
        } */
        
        $channelExclusionsData = $this->extensibleDataObjectConverter->toNestedArray(
            $channelExclusions,
            [],
            \Retailplace\MiraklSellerAdditionalField\Api\Data\ChannelExclusionsInterface::class
        );
        
        $channelExclusionsModel = $this->channelExclusionsFactory->create()->setData($channelExclusionsData);
        
        try {
            $this->resource->save($channelExclusionsModel);
        } catch (\Exception $exception) {
            throw new CouldNotSaveException(__(
                'Could not save the channelExclusions: %1',
                $exception->getMessage()
            ));
        }
        return $channelExclusionsModel->getDataModel();
    }

    /**
     * {@inheritdoc}
     */
    public function get($channelExclusionsId)
    {
        $channelExclusions = $this->channelExclusionsFactory->create();
        $this->resource->load($channelExclusions, $channelExclusionsId);
        if (!$channelExclusions->getId()) {
            throw new NoSuchEntityException(__('ChannelExclusions with id "%1" does not exist.', $channelExclusionsId));
        }
        return $channelExclusions->getDataModel();
    }

    /**
     * {@inheritdoc}
     */
    public function getList(
        \Magento\Framework\Api\SearchCriteriaInterface $criteria
    ) {
        $collection = $this->channelExclusionsCollectionFactory->create();
        
        $this->extensionAttributesJoinProcessor->process(
            $collection,
            \Retailplace\MiraklSellerAdditionalField\Api\Data\ChannelExclusionsInterface::class
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
        \Retailplace\MiraklSellerAdditionalField\Api\Data\ChannelExclusionsInterface $channelExclusions
    ) {
        try {
            $channelExclusionsModel = $this->channelExclusionsFactory->create();
            $this->resource->load($channelExclusionsModel, $channelExclusions->getChannelexclusionsId());
            $this->resource->delete($channelExclusionsModel);
        } catch (\Exception $exception) {
            throw new CouldNotDeleteException(__(
                'Could not delete the ChannelExclusions: %1',
                $exception->getMessage()
            ));
        }
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function deleteById($channelExclusionsId)
    {
        return $this->delete($this->get($channelExclusionsId));
    }
}

