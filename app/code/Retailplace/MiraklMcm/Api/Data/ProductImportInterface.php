<?php
/**
 * Retailplace_MiraklMcm
 *
 * @copyright   Copyright (c) 2021 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Alexander Korsun <aleksandr@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\MiraklMcm\Api\Data;

/**
 * Interface ProductImportInterface
 */
interface ProductImportInterface
{

    /**
     * @var string
     */
    const ID = 'id';
    const MIRAKL_PRODUCT_ID = 'mirakl_product_id';
    const SKU = 'sku';
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';
    const TOTAL_COUNT = 'total_count';
    const MIRAKL_CREATED_AT = 'mirakl_created_at';
    const MIRAKL_UPDATED_AT = 'mirakl_updated_at';
    const DATA = 'data';
    const STATUS = 'status';
    const SEND_STATUS = 'send_status';
    const ERROR = 'error';

    /**
     * Set Id
     *
     * @param int $value
     * @return ProductImportInterface
     */
    public function setId(int $value): ProductImportInterface;

    /**
     * Get Id
     *
     * @return int
     */
    public function getId();

    /**
     * Set MiraklProductId
     *
     * @param string $miraklProductId
     * @return ProductImportInterface
     */
    public function setMiraklProductId(string $miraklProductId): ProductImportInterface;

    /**
     * Get MiraklProductId
     *
     * @return string
     */
    public function getMiraklProductId(): string;

    /**
     * Set Sku
     *
     * @param string|null $sku
     * @return ProductImportInterface
     */
    public function setSku(?string $sku): ProductImportInterface;

    /**
     * Get Sku
     *
     * @return string|null
     */
    public function getSku(): ?string;

    /**
     * Set CreatedAt
     *
     * @param string $createdAt
     * @return ProductImportInterface
     */
    public function setCreatedAt(string $createdAt): ProductImportInterface;

    /**
     * Get CreatedAt
     *
     * @return string
     */
    public function getCreatedAt(): string;

    /**
     * Set UpdatedAt
     *
     * @param string $updatedAt
     * @return ProductImportInterface
     */
    public function setUpdatedAt(string $updatedAt): ProductImportInterface;

    /**
     * Get UpdatedAt
     *
     * @return string
     */
    public function getUpdatedAt(): string;

    /**
     * Set TotalCount
     *
     * @param int $totalCount
     * @return ProductImportInterface
     */
    public function setTotalCount(int $totalCount): ProductImportInterface;

    /**
     * Get TotalCount
     *
     * @return int
     */
    public function getTotalCount(): int;

    /**
     * Set MiraklCreatedAt
     *
     * @param string $miraklCreatedAt
     * @return ProductImportInterface
     */
    public function setMiraklCreatedAt(string $miraklCreatedAt): ProductImportInterface;

    /**
     * Get MiraklCreatedAt
     *
     * @return string
     */
    public function getMiraklCreatedAt(): string;

    /**
     * Set MiraklUpdatedAt
     *
     * @param string $miraklUpdatedAt
     * @return ProductImportInterface
     */
    public function setMiraklUpdatedAt(string $miraklUpdatedAt): ProductImportInterface;

    /**
     * Get MiraklUpdatedAt
     *
     * @return string
     */
    public function getMiraklUpdatedAt(): string;

    /**
     * Set Product Data
     *
     * @param string $data
     * @return ProductImportInterface
     */
    public function setProductData(string $data): ProductImportInterface;

    /**
     * Get Product Data
     *
     * @return string
     */
    public function getProductData(): string;

    /**
     * Set Status
     *
     * @param int $status
     * @return ProductImportInterface
     */
    public function setStatus(int $status): ProductImportInterface;

    /**
     * Get Status
     *
     * @return int
     */
    public function getStatus(): int;

    /**
     * Set SendStatus
     *
     * @param int $sendStatus
     * @return ProductImportInterface
     */
    public function setSendStatus(int $sendStatus): ProductImportInterface;

    /**
     * Get SendStatus
     *
     * @return int
     */
    public function getSendStatus(): int;

    /**
     * Set Error
     *
     * @param string|null $error
     * @return ProductImportInterface
     */
    public function setError(?string $error): ProductImportInterface;

    /**
     * Get Error
     *
     * @return string|null
     */
    public function getError(): ?string;
}
