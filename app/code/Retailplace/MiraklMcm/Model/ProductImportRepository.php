<?php
/**
 * Retailplace_MiraklMcm
 *
 * @copyright   Copyright (c) 2021 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Alexander Korsun <aleksandr@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\MiraklMcm\Model;

use Retailplace\MiraklMcm\Api\ProductImportRepositoryInterface;
use Retailplace\MiraklMcm\Model\ResourceModel\ProductImport\CollectionFactory;
use Retailplace\MiraklMcm\Model\ResourceModel\ProductImport as ProductImportResourceModel;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Api\SortOrderBuilder;
use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SearchResultsInterfaceFactory;
use Magento\Framework\Api\SearchResults;
use Mirakl\MCM\Front\Domain\Product\Synchronization\ProductAcceptance;
use Magento\Framework\Api\Search\FilterGroupBuilder;
use Retailplace\MiraklMcm\Api\Data\ProductImportInterface;
use Exception;
use Magento\Framework\Api\SearchResultsInterface;
use DateTimeFactory;
use Magento\Framework\Stdlib\DateTime as MagentoDateTime;

/**
 * Class ProductImportRepository
 */
class ProductImportRepository implements ProductImportRepositoryInterface
{
    /**
     * @var ProductImportFactory|null
     */
    private $modelFactory = null;

    /**
     * @var CollectionFactory|null
     */
    private $collectionFactory = null;

    /**
     * @var AdapterInterface
     */
    private $connection;

    /**
     * @var CollectionProcessorInterface
     */
    private $collectionProcessor;

    /**
     * @var SearchResultsInterfaceFactory
     */
    private $searchResultFactory;

    /**
     * @var SortOrderBuilder
     */
    private $sortOrderBuilder;

    /**
     * @var FilterBuilder
     */
    private $filterBuilder;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @var FilterGroupBuilder
     */
    private $filterGroupBuilder;

    /**
     * @var ProductImportResourceModel
     */
    private $productImportResourceModel;

    /**
     * @var DateTimeFactory
     */
    private $dateTimeFactory;

    /**
     * initialize
     *
     * @param ProductImportFactory $modelFactory
     * @param CollectionFactory $collectionFactory
     * @param ResourceConnection $resource
     * @param SearchResultsInterfaceFactory $searchResultInterfaceFactory
     * @param CollectionProcessorInterface $collectionProcessor
     * @param SortOrderBuilder $sortOrderBuilder
     * @param FilterBuilder $filterBuilder
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param FilterGroupBuilder $filterGroupBuilder
     * @param ProductImportResourceModel $productImportResourceModel
     * @param DateTimeFactory $dateTimeFactory
     */
    public function __construct(
        ProductImportFactory $modelFactory,
        CollectionFactory $collectionFactory,
        ResourceConnection $resource,
        SearchResultsInterfaceFactory $searchResultInterfaceFactory,
        CollectionProcessorInterface $collectionProcessor,
        SortOrderBuilder $sortOrderBuilder,
        FilterBuilder $filterBuilder,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        FilterGroupBuilder $filterGroupBuilder,
        ProductImportResourceModel $productImportResourceModel,
        DateTimeFactory $dateTimeFactory
    ) {
        $this->modelFactory = $modelFactory;
        $this->collectionFactory = $collectionFactory;
        $this->connection = $resource->getConnection();
        $this->collectionFactory = $collectionFactory;
        $this->searchResultFactory = $searchResultInterfaceFactory;
        $this->collectionProcessor = $collectionProcessor;
        $this->sortOrderBuilder = $sortOrderBuilder;
        $this->filterBuilder = $filterBuilder;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->filterGroupBuilder = $filterGroupBuilder;
        $this->productImportResourceModel = $productImportResourceModel;
        $this->dateTimeFactory = $dateTimeFactory;
    }

    /**
     * get by id
     *
     * @param int $id
     * @return ProductImport
     * @throws NoSuchEntityException
     */
    public function getById(int $id): ProductImport
    {
        $model = $this->modelFactory->create();
        $this->productImportResourceModel->load($model, $id, ProductImportInterface::ID);
        if (!$model->getId()) {
            throw new NoSuchEntityException(__('The Data with the "%1" ID doesn\'t exist.', $id));
        }
        return $model;
    }

    /**
     * get by product Id
     *
     * @param string $productId
     * @return ProductImport
     * @throws NoSuchEntityException
     */
    public function getByProductId(string $productId): ProductImport
    {
        $model = $this->modelFactory->create();
        $this->productImportResourceModel->load($model, $productId, ProductImportInterface::MIRAKL_PRODUCT_ID);

        if (!$model->getId()) {
            throw new NoSuchEntityException(__('The Data with the "%1" Product ID doesn\'t exist.', $productId));
        }
        return $model;
    }

    /**
     * get by id
     *
     * @param ProductImport $subject
     * @return ProductImport
     * @throws CouldNotSaveException
     */
    public function save(ProductImport $subject): ProductImport
    {
        try {
            $this->productImportResourceModel->save($subject);
        } catch (Exception $exception) {
            throw new CouldNotSaveException(__($exception->getMessage()));
        }
        return $subject;
    }

    /**
     * get list
     *
     * @param SearchCriteriaInterface $criteria
     * @return SearchResultsInterface
     */
    public function getList(SearchCriteriaInterface $criteria): SearchResultsInterface
    {
        $collection = $this->collectionFactory->create();
        $this->collectionProcessor->process($criteria, $collection);
        $searchResults = $this->searchResultFactory->create();

        $searchResults->setSearchCriteria($criteria);
        $searchResults->setItems($collection->getItems());

        return $searchResults;
    }

    /**
     * delete
     *
     * @param ProductImport $subject
     * @return boolean
     * @throws CouldNotDeleteException
     */
    public function delete(ProductImport $subject): bool
    {
        try {
            $this->productImportResourceModel->delete($subject);
        } catch (Exception $exception) {
            throw new CouldNotDeleteException(__($exception->getMessage()));
        }
        return true;
    }

    /**
     * delete by id
     *
     * @param int $id
     * @return boolean
     * @throws CouldNotDeleteException|NoSuchEntityException
     */
    public function deleteById(int $id): bool
    {
        return $this->delete($this->getById($id));
    }

    /**
     * get new model
     *
     * @return ProductImport
     */
    public function getModel(): ProductImport
    {
        return $this->modelFactory->create();
    }

    /**
     * Get Products Object For Send Report To Mirakl
     *
     * @return array
     */
    public function getProductsObjectForSendReportToMirakl(): array
    {
        $result = [];

        $filters = [
            $this->filterBuilder->setField(ProductImportInterface::STATUS)
                ->setConditionType('in')
                ->setValue([ProductImport::STATUS_SUCCESS, ProductImport::STATUS_ERROR])
                ->create(),
            $this->filterBuilder->setField(ProductImportInterface::SEND_STATUS)
                ->setConditionType('eq')
                ->setValue(ProductImport::SEND_STATUS_NOT_SENT)
                ->create()
        ];

        $filterGroups = [];

        foreach ($filters as $filter) {
            $filterGroups[] = $this->filterGroupBuilder
                ->addFilter($filter)
                ->create();
        }

        $sortOrder = $this->sortOrderBuilder
            ->setField(ProductImportInterface::UPDATED_AT)
            ->setDirection('DESC')->create();

        $searchCriteria = $this->searchCriteriaBuilder
            ->setFilterGroups($filterGroups)
            ->setSortOrders([$sortOrder])
            ->create();

        foreach ($this->getList($searchCriteria)->getItems() as $item) {
            $data = [
                'mirakl_product_id' => $item->getMiraklProductId(),
                'product_sku'       => $item->getSku(),
            ];

            if ($item->getStatus() == ProductImport::STATUS_SUCCESS) {
                $data['acceptance'] = ['status' => ProductAcceptance::STATUS_ACCEPTED];
            } else {
                $data['integration_errors'] = [['message' => $item->getError()]];
            }

            $result[] = $data;
        }

        return $result;
    }

    /**
     * Get Products Object For Resend To Queue
     *
     * @return array
     */
    public function getProductsObjectForResendToQueue(): array
    {
        $result = [];
        $datetime = $this->dateTimeFactory->create();
        $datetime->modify('-1 day');
        $date = $datetime->format(MagentoDateTime::DATETIME_PHP_FORMAT);

        $filters = [
            $this->filterBuilder->setField(ProductImportInterface::STATUS)
                ->setConditionType('in')
                ->setValue([ProductImport::STATUS_PENDING, ProductImport::STATUS_IN_PROGRESS])
                ->create(),
            $this->filterBuilder->setField(ProductImportInterface::SEND_STATUS)
                ->setConditionType('eq')
                ->setValue(ProductImport::SEND_STATUS_NOT_SENT)
                ->create(),
            $this->filterBuilder->setField(ProductImportInterface::UPDATED_AT)
                ->setConditionType("lteq")
                ->setValue($date)
                ->create()
        ];

        $filterGroups = [];

        foreach ($filters as $filter) {
            $filterGroups[] = $this->filterGroupBuilder
                ->addFilter($filter)
                ->create();
        }

        $sortOrder = $this->sortOrderBuilder
            ->setField(ProductImportInterface::UPDATED_AT)
            ->setDirection('DESC')->create();

        $searchCriteria = $this->searchCriteriaBuilder
            ->setFilterGroups($filterGroups)
            ->setSortOrders([$sortOrder])
            ->create();

        foreach ($this->getList($searchCriteria)->getItems() as $item) {
            $result[] = ['mirakl_product_id' => $item->getMiraklProductId()];
        }

        return $result;
    }

    /**
     * Update Product
     *
     * @param $data
     */
    public function updateProduct($data): void
    {
        $fields = [
            ProductImportInterface::MIRAKL_PRODUCT_ID,
            ProductImportInterface::SKU,
            ProductImportInterface::CREATED_AT,
            ProductImportInterface::MIRAKL_CREATED_AT,
            ProductImportInterface::MIRAKL_UPDATED_AT,
            ProductImportInterface::DATA,
            ProductImportInterface::STATUS,
            ProductImportInterface::SEND_STATUS,
            ProductImportInterface::ERROR
        ];

        $tableName = $this->connection->getTableName(ProductImportResourceModel::TABLE_NAME);
        foreach (array_chunk($data, 1000) as $dataChunk) {
            $this->connection->insertOnDuplicate($tableName, $dataChunk, $fields);
        }
    }

    /**
     * Update Product
     *
     * @param array $ids
     */
    public function updateProductStatus(array $ids)
    {
        $this->connection->update(
            $this->connection->getTableName(ProductImportResourceModel::TABLE_NAME),
            [ProductImportInterface::SEND_STATUS => ProductImport::SEND_STATUS_SENT],
            [ProductImportInterface::MIRAKL_PRODUCT_ID . " IN (?)" => $ids]
        );
    }
}
