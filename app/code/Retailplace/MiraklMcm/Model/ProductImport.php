<?php
/**
 * Retailplace_MiraklMcm
 *
 * @copyright   Copyright (c) 2021 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Alexander Korsun <aleksandr@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\MiraklMcm\Model;

use Magento\Framework\DataObject\IdentityInterface;
use Retailplace\MiraklMcm\Api\Data\ProductImportInterface;
use Magento\Framework\Model\AbstractModel;

/**
 * ProductImport Class
 */
class ProductImport extends AbstractModel implements IdentityInterface, ProductImportInterface
{

    /**
     * @var int
     */
    const STATUS_PENDING = 0;
    const STATUS_IN_PROGRESS = 1;
    const STATUS_SUCCESS = 2;
    const STATUS_ERROR = 3;
    const SEND_STATUS_NOT_SENT = 0;
    const SEND_STATUS_SENT = 1;

    /**
     * @var string
     */
    const CACHE_TAG = 'retailplace_miraklmcm_productimport';

    /**
     * @var string
     */
    protected $_cacheTag = 'retailplace_miraklmcm_productimport';

    /**
     * @var string
     */
    protected $_eventPrefix = 'retailplace_miraklmcm_productimport';

    /**
     * set resource model
     */
    public function _construct()
    {
        $this->_init(ResourceModel\ProductImport::class);
    }

    /**
     * Get identities.
     *
     * @return string[]
     */
    public function getIdentities(): array
    {
        return [self::CACHE_TAG . '_' . $this->getId()];
    }

    /**
     * Set Id
     *
     * @param int $value
     * @return ProductImportInterface
     */
    public function setId($value): ProductImportInterface
    {
        return $this->setData(self::ID, $value);
    }

    /**
     * Get Id
     *
     * @return mixed
     */
    public function getId()
    {
        return parent::getData(self::ID);
    }

    /**
     * Set MiraklProductId
     *
     * @param string $miraklProductId
     * @return ProductImportInterface
     */
    public function setMiraklProductId(string $miraklProductId): ProductImportInterface
    {
        return $this->setData(self::MIRAKL_PRODUCT_ID, $miraklProductId);
    }

    /**
     * Get MiraklProductId
     *
     * @return string
     */
    public function getMiraklProductId(): string
    {
        return parent::getData(self::MIRAKL_PRODUCT_ID);
    }

    /**
     * Set Sku
     *
     * @param string|null $sku
     * @return ProductImportInterface
     */
    public function setSku(?string $sku): ProductImportInterface
    {
        return $this->setData(self::SKU, $sku);
    }

    /**
     * Get Sku
     *
     * @return string|null
     */
    public function getSku(): ?string
    {
        return parent::getData(self::SKU);
    }

    /**
     * Set CreatedAt
     *
     * @param string $createdAt
     * @return ProductImportInterface
     */
    public function setCreatedAt(string $createdAt): ProductImportInterface
    {
        return $this->setData(self::CREATED_AT, $createdAt);
    }

    /**
     * Get CreatedAt
     *
     * @return string
     */
    public function getCreatedAt(): string
    {
        return parent::getData(self::CREATED_AT);
    }

    /**
     * Set UpdatedAt
     *
     * @param string $updatedAt
     * @return ProductImportInterface
     */
    public function setUpdatedAt(string $updatedAt): ProductImportInterface
    {
        return $this->setData(self::UPDATED_AT, $updatedAt);
    }

    /**
     * Get UpdatedAt
     *
     * @return string
     */
    public function getUpdatedAt(): string
    {
        return parent::getData(self::UPDATED_AT);
    }

    /**
     * Set TotalCount
     *
     * @param int $totalCount
     * @return ProductImportInterface
     */
    public function setTotalCount(int $totalCount): ProductImportInterface
    {
        return $this->setData(self::TOTAL_COUNT, $totalCount);
    }

    /**
     * Get TotalCount
     *
     * @return int
     */
    public function getTotalCount(): int
    {
        return parent::getData(self::TOTAL_COUNT);
    }

    /**
     * Set MiraklCreatedAt
     *
     * @param string $miraklCreatedAt
     * @return ProductImportInterface
     */
    public function setMiraklCreatedAt(string $miraklCreatedAt): ProductImportInterface
    {
        return $this->setData(self::MIRAKL_CREATED_AT, $miraklCreatedAt);
    }

    /**
     * Get MiraklCreatedAt
     *
     * @return string
     */
    public function getMiraklCreatedAt(): string
    {
        return parent::getData(self::MIRAKL_CREATED_AT);
    }

    /**
     * Set MiraklUpdatedAt
     *
     * @param string $miraklUpdatedAt
     * @return ProductImportInterface
     */
    public function setMiraklUpdatedAt(string $miraklUpdatedAt): ProductImportInterface
    {
        return $this->setData(self::MIRAKL_UPDATED_AT, $miraklUpdatedAt);
    }

    /**
     * Get MiraklUpdatedAt
     *
     * @return string
     */
    public function getMiraklUpdatedAt(): string
    {
        return parent::getData(self::MIRAKL_UPDATED_AT);
    }

    /**
     * Set Product Data
     *
     * @param string $data
     * @return ProductImportInterface
     */
    public function setProductData(string $data): ProductImportInterface
    {
        return $this->setData(self::DATA, $data);
    }

    /**
     * Get Product Data
     *
     * @return string
     */
    public function getProductData(): string
    {
        return parent::getData(self::DATA);
    }

    /**
     * Set Status
     *
     * @param int $status
     * @return ProductImportInterface
     */
    public function setStatus(int $status): ProductImportInterface
    {
        return $this->setData(self::STATUS, $status);
    }

    /**
     * Get Status
     *
     * @return int
     */
    public function getStatus(): int
    {
        return (int) parent::getData(self::STATUS);
    }

    /**
     * Set SendStatus
     *
     * @param int $sendStatus
     * @return ProductImportInterface
     */
    public function setSendStatus(int $sendStatus): ProductImportInterface
    {
        return $this->setData(self::SEND_STATUS, $sendStatus);
    }

    /**
     * Get SendStatus
     *
     * @return int
     */
    public function getSendStatus(): int
    {
        return (int) parent::getData(self::SEND_STATUS);
    }

    /**
     * Set Error
     *
     * @param string|null $error
     * @return ProductImportInterface
     */
    public function setError(?string $error): ProductImportInterface
    {
        return $this->setData(self::ERROR, $error);
    }

    /**
     * Get Error
     *
     * @return string|null
     */
    public function getError(): ?string
    {
        return parent::getData(self::ERROR);
    }
}
