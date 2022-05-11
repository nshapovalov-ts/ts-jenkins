<?php

/**
 * Retailplace_MiraklOrder
 *
 * @copyright   Copyright (c) 2022 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Evgeniy Derevyanko <evgeniy@tradesquare.com.au>
 * @author      Dmitriy Fionov <dmitriy@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\MiraklOrder\Model;

use Exception;
use Magento\Framework\Api\Search\SearchResultInterface;
use Magento\Framework\Api\Search\SearchResultInterfaceFactory;
use Magento\Framework\Api\SearchCriteria\CollectionProcessor;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Exception\NoSuchEntityException;
use Retailplace\MiraklOrder\Api\Data\MiraklOrderInterface;
use Retailplace\MiraklOrder\Api\MiraklOrderRepositoryInterface;
use Retailplace\MiraklOrder\Model\ResourceModel\MiraklOrder as MiraklOrderResourceModel;
use Retailplace\MiraklOrder\Model\ResourceModel\MiraklOrder\Collection;
use Retailplace\MiraklOrder\Model\ResourceModel\MiraklOrder\CollectionFactory as OrderCollectionFactory;

/**
 * Class MiraklOrderRepository
 */
class MiraklOrderRepository implements MiraklOrderRepositoryInterface
{
    /** @var MiraklOrderResourceModel */
    private $miraklOrderResourceModel;

    /** @var MiraklOrderFactory */
    private $miraklOrderFactory;

    /** @var CollectionProcessor */
    private $collectionProcessor;

    /** @var SearchResultInterfaceFactory */
    private $searchResultFactory;

    /** @var OrderCollectionFactory */
    private $orderCollectionFactory;

    /** @var MiraklOrderInterface[] */
    private $miraklOrdersList;

    /** @var MiraklOrderInterface[] */
    private $miraklOrdersByOrderId;

    /**
     * OrderRepository constructor.
     *
     * @param MiraklOrderResourceModel $miraklOrderResourceModel
     * @param MiraklOrderFactory $miraklOrderFactory
     * @param CollectionProcessor $collectionProcessor
     * @param OrderCollectionFactory $orderCollectionFactory
     * @param SearchResultInterfaceFactory $searchResultFactory
     */
    public function __construct(
        MiraklOrderResourceModel $miraklOrderResourceModel,
        MiraklOrderFactory $miraklOrderFactory,
        CollectionProcessor $collectionProcessor,
        OrderCollectionFactory $orderCollectionFactory,
        SearchResultInterfaceFactory $searchResultFactory
    ) {
        $this->miraklOrderResourceModel = $miraklOrderResourceModel;
        $this->miraklOrderFactory = $miraklOrderFactory;
        $this->collectionProcessor = $collectionProcessor;
        $this->orderCollectionFactory = $orderCollectionFactory;
        $this->searchResultFactory = $searchResultFactory;
    }

    /**
     * Get Mirakl Order by ID
     *
     * @param int $entityId
     * @return MiraklOrderInterface
     * @throws NoSuchEntityException
     */
    public function getById(int $entityId): MiraklOrderInterface
    {
        if (!isset($this->miraklOrdersList[$entityId])) {
            $miraklOrder = $this->miraklOrderFactory->create();
            $this->miraklOrderResourceModel->load($miraklOrder, $entityId);
            if (!$miraklOrder->getId()) {
                throw new NoSuchEntityException(__('Unable to find Mirakl Order with ID "%1"', $entityId));
            }
            $this->miraklOrdersList[$entityId] = $miraklOrder;
        }

        return $this->miraklOrdersList[$entityId];
    }

    /**
     * Get Mirakl Order by Mirakl Order ID
     *
     * @param string $miraklOrderId
     * @return MiraklOrderInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getByMiraklOrderId(string $miraklOrderId): MiraklOrderInterface
    {
        if (!isset($this->miraklOrdersByOrderId[$miraklOrderId])) {
            $miraklOrder = $this->miraklOrderFactory->create();
            $this->miraklOrderResourceModel->load(
                $miraklOrder,
                $miraklOrderId,
                MiraklOrderInterface::MIRAKL_ORDER_ID
            );
            if (!$miraklOrder->getId()) {
                throw new NoSuchEntityException(
                    __('Unable to find Mirakl Order with Mirakl Order ID "%1"', $miraklOrderId)
                );
            }
            $this->miraklOrdersByOrderId[$miraklOrderId] = $miraklOrder;
        }

        return $this->miraklOrdersByOrderId[$miraklOrderId];
    }

    /**
     * Save Mirakl Order
     *
     * @param MiraklOrderInterface $miraklOrder
     * @return MiraklOrderInterface
     * @throws AlreadyExistsException
     */
    public function save(MiraklOrderInterface $miraklOrder): MiraklOrderInterface
    {
        $this->miraklOrderResourceModel->save($miraklOrder);

        return $miraklOrder;
    }

    /**
     * Delete Mirakl Order
     *
     * @param MiraklOrderInterface $miraklOrder
     * @throws Exception
     */
    public function delete(MiraklOrderInterface $miraklOrder)
    {
        unset($this->miraklOrdersList[$miraklOrder->getId()]);
        unset($this->miraklOrdersByOrderId[$miraklOrder->getMiraklOrderId()]);
        $this->miraklOrderResourceModel->delete($miraklOrder);
    }

    /**
     * Delete Mirakl Order by Id
     *
     * @param int $miraklOrderId
     * @throws NoSuchEntityException
     * @throws Exception
     */
    public function deleteById(int $miraklOrderId)
    {
        $miraklOrder = $this->getById($miraklOrderId);
        $this->delete($miraklOrder);
    }

    /**
     * Get Mirakl Order list
     *
     * @param SearchCriteriaInterface $searchCriteria
     * @return SearchResultInterface
     */
    public function getList(SearchCriteriaInterface $searchCriteria): SearchResultInterface
    {
        /** @var SearchResultInterface $searchResult */
        $searchResult = $this->searchResultFactory->create();

        /** @var Collection $collection */
        $collection = $this->orderCollectionFactory->create();

        $this->collectionProcessor->process($searchCriteria, $collection);
        $searchResult->setItems($collection->getItems());
        $searchResult->setTotalCount($collection->getSize());

        return $searchResult;
    }
}
