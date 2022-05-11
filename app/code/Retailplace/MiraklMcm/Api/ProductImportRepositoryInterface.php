<?php
/**
 * Retailplace_MiraklMcm
 *
 * @copyright   Copyright (c) 2021 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Alexander Korsun <aleksandr@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\MiraklMcm\Api;

use Retailplace\MiraklMcm\Model\ProductImport;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Api\SearchResultsInterface;

/**
 * Interface ProductImportRepositoryInterface
 */
interface ProductImportRepositoryInterface
{

    /**
     * get by id
     *
     * @param int $id
     * @return ProductImport
     * @throws NoSuchEntityException
     */
    public function getById(int $id): ProductImport;

    /**
     * get by id
     *
     * @param string $productId
     * @return ProductImport
     * @throws NoSuchEntityException
     */
    public function getByProductId(string $productId): ProductImport;

    /**
     * get by id
     *
     * @param ProductImport $subject
     * @return ProductImport
     * @throws CouldNotSaveException
     */
    public function save(ProductImport $subject): ProductImport;

    /**
     * get list
     *
     * @param SearchCriteriaInterface $criteria
     * @return SearchResultsInterface
     */
    public function getList(SearchCriteriaInterface $criteria): SearchResultsInterface;

    /**
     * delete
     *
     * @param ProductImport $subject
     * @return boolean
     */
    public function delete(ProductImport $subject): bool;

    /**
     * delete by id
     *
     * @param int $id
     * @return boolean
     */
    public function deleteById(int $id): bool;

    /**
     * get new model
     *
     * @return ProductImport
     */
    public function getModel(): ProductImport;

    /**
     * Update Product
     *
     * @param $data
     */
    public function updateProduct($data): void;
}
