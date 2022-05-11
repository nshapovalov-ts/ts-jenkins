<?php

/**
 * Retailplace_SellerAffiliate
 *
 * @copyright   Copyright (c) 2022 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Nikolay Shapovalov <nikolay@tradesquare.com.au>
 */

namespace Retailplace\SellerAffiliate\Model;

use Exception;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Api\Search\SearchResultInterface;
use Magento\Framework\Api\SearchCriteria\CollectionProcessor;
use Retailplace\SellerAffiliate\Model\SellerAffiliateFactory;
use Magento\Framework\Api\Search\SearchResultInterfaceFactory;
use Retailplace\SellerAffiliate\Api\Data\SellerAffiliateInterface;
use Retailplace\SellerAffiliate\Api\SellerAffiliateRepositoryInterface;
use Retailplace\SellerAffiliate\Model\ResourceModel\SellerAffiliate as SellerAffiliateResourceModel;
use Retailplace\SellerAffiliate\Model\ResourceModel\SellerAffiliate\CollectionFactory as SellerCollectionFactory;

/**
 * Class SellerAffiliateRepository implements repository model for seller affiliate
 * @SuppressWarnings(PHPMD.LongVariable)
 */
class SellerAffiliateRepository implements SellerAffiliateRepositoryInterface
{
    /** @var SellerAffiliateResourceModel */
    protected $sellerAffiliateResourceModel;

    /** @var SellerAffiliateFactory */
    protected $sellerAffiliateFactory;

    /** @var CollectionProcessor */
    protected $collectionProcessor;

    /** @var SearchResultInterfaceFactory */
    protected $searchResultFactory;

    /** @var SellerCollectionFactory */
    protected $sellerAffiliateCollectionFactory;

    /**
     * @param SellerAffiliateResourceModel $sellerAffiliateResourceModel
     * @param SellerAffiliateFactory $sellerAffiliateFactory
     * @param CollectionProcessor $collectionProcessor
     * @param SellerCollectionFactory $sellerAffiliateCollectionFactory
     * @param SearchResultInterfaceFactory $searchResultFactory
     */
    public function __construct(
        SellerAffiliateResourceModel $sellerAffiliateResourceModel,
        SellerAffiliateFactory $sellerAffiliateFactory,
        CollectionProcessor $collectionProcessor,
        SellerCollectionFactory $sellerAffiliateCollectionFactory,
        SearchResultInterfaceFactory $searchResultFactory
    ) {
        $this->sellerAffiliateResourceModel = $sellerAffiliateResourceModel;
        $this->sellerAffiliateFactory = $sellerAffiliateFactory;
        $this->collectionProcessor = $collectionProcessor;
        $this->sellerAffiliateCollectionFactory = $sellerAffiliateCollectionFactory;
        $this->searchResultFactory = $searchResultFactory;
    }

    /**
     * Get SellerAffiliate by id
     *
     * @param int $sellerAffiliateId
     * @return SellerAffiliateInterface
     * @throws NoSuchEntityException
     */
    public function getById(int $sellerAffiliateId): SellerAffiliateInterface
    {
        $sellerAffiliate = $this->sellerAffiliateFactory->create();
        $this->sellerAffiliateResourceModel->load($sellerAffiliate, $sellerAffiliateId);
        if (!$sellerAffiliate->getId()) {
            throw new NoSuchEntityException(__('Unable to find seller affiliate with ID "%1"', $sellerAffiliateId));
        }

        return $sellerAffiliate;
    }

    /**
     * Save SellerAffiliate
     *
     * @param SellerAffiliateInterface $sellerAffiliate
     * @return SellerAffiliateInterface
     * @throws AlreadyExistsException
     */
    public function save(SellerAffiliateInterface $sellerAffiliate): SellerAffiliateInterface
    {
        $this->sellerAffiliateResourceModel->save($sellerAffiliate);

        return $sellerAffiliate;
    }

    /**
     * Delete Seller Affiliate
     *
     * @param SellerAffiliateInterface $sellerAffiliate
     * @return bool
     * @throws Exception
     */
    public function delete(SellerAffiliateInterface $sellerAffiliate): bool
    {
        $this->sellerAffiliateResourceModel->delete($sellerAffiliate);

        return true;
    }

    /**
     * Delete Seller Affiliate by Id
     *
     * @param int $sellerAffiliateId
     * @return bool
     * @throws NoSuchEntityException
     * @throws Exception
     */
    public function deleteById(int $sellerAffiliateId): bool
    {
        $sellerAffiliate = $this->getById($sellerAffiliateId);

        return $this->delete($sellerAffiliate);
    }

    /**
     * Get Seller Affiliate list
     *
     * @param SearchCriteriaInterface $searchCriteria
     * @return SearchResultInterface
     */
    public function getList(SearchCriteriaInterface $searchCriteria): SearchResultInterface
    {
        /** @var SearchResultInterface $searchResult */
        $searchResult = $this->searchResultFactory->create();
        $collection = $this->sellerAffiliateCollectionFactory->create();
        $this->collectionProcessor->process($searchCriteria, $collection);
        $searchResult->setItems($collection->getItems());
        $searchResult->setTotalCount($collection->getSize());

        return $searchResult;
    }
}
