<?php

/**
 * Retailplace_MiraklShop
 *
 * @copyright   Copyright (c) 2022 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Natalia Sekulich <natalia@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\MiraklShop\Model\AttributeUpdater;

use Exception;
use Psr\Log\LoggerInterface;
use Magento\Framework\App\ResourceConnection;
use Retailplace\MiraklShop\Model\HasNewProducts;
use Retailplace\MiraklShop\Api\Data\ShopInterface;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Retailplace\MiraklShop\Model\ResourceModel\Shop;
use Magento\Framework\Exception\AlreadyExistsException;
use Retailplace\MiraklShop\Api\ShopRepositoryInterface;

/**
 * Class HasNewProducts attribute updater
 */
class HasNewProductsAttributeUpdater
{
    /** @var int Has new product values */
    public const HAS_NEW_PRODUCTS_VALUE = 1;
    public const HAS_NO_NEW_PRODUCTS_VALUE = 0;

    /**
     * @var HasNewProducts
     */
    private $hasNewProducts;

    /**
     * @var ShopRepositoryInterface
     */
    private $shopRepository;

    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @var string
     */
    private $table;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param HasNewProducts $hasNewProducts
     * @param ResourceConnection $resourceConnection
     * @param ShopRepositoryInterface $shopRepository
     * @param LoggerInterface $logger
     */
    public function __construct(
        HasNewProducts $hasNewProducts,
        ResourceConnection $resourceConnection,
        ShopRepositoryInterface $shopRepository,
        LoggerInterface $logger
    ) {
        $this->hasNewProducts = $hasNewProducts;
        $this->shopRepository = $shopRepository;
        $this->resourceConnection = $resourceConnection;
        $this->logger = $logger;
    }

    /**
     * Update attribute for all shops
     *
     * @return void
     */
    public function updateAll(): void
    {
        $adapter = $this->getConnection();
        $this->table = $adapter->getTableName(Shop::TABLE_NAME);
        $shopsWithNewProducts = $this->hasNewProducts->getShopsWithNewProducts();
        $this->restoreValues();
        $this->updateField($shopsWithNewProducts, true);
    }

    /**
     * Restore 'has_new_products' value for all shops
     *
     * @return void
     */
    private function restoreValues(): void
    {
        try {
            $sql = sprintf(
                'UPDATE `%1s` SET `%2s` = %1d',
                $this->table,
                ShopInterface::HAS_NEW_PRODUCTS,
                self::HAS_NO_NEW_PRODUCTS_VALUE
            );
            $adapter = $this->getConnection();
            $adapter->query($sql);
        } catch (Exception $e) {
            $this->logger->error($e->getMessage());
        }
    }

    /**
     * Method for update by product import
     *
     * @param string $shopIds
     *
     * @return void
     */
    public function updateOnProductImport(string $shopIds): void
    {
        $adapter = $this->getConnection();
        $this->table = $adapter->getTableName(Shop::TABLE_NAME);
        $sql = sprintf(
            'UPDATE `%1s` SET `%2s` = %1d WHERE `%3s` IN (%4s)',
            $this->table,
            ShopInterface::HAS_NEW_PRODUCTS,
            self::HAS_NEW_PRODUCTS_VALUE,
            ShopInterface::EAV_OPTION_ID,
            $shopIds
        );
        try {
            $adapter->query($sql);
        } catch (Exception $e) {
            $this->logger->error('Mirakl Shop table update error ' . $e->getMessage());
        }
    }

    /**
     * Update field
     *
     * @param array $shops
     * @param bool $hasNewProducts
     *
     * @return void
     */
    private function updateField(array $shops, bool $hasNewProducts): void
    {
        /** @var ShopInterface $shop */
        foreach ($shops as $shop) {
            try {
                $shop->setHasNewProducts($hasNewProducts);
                $this->shopRepository->save($shop);
            } catch (AlreadyExistsException $e) {
                $this->logger->error($e->getMessage());
            }
        }
    }

    /**
     * Get Adapter
     *
     * @return AdapterInterface
     */
    private function getConnection(): AdapterInterface
    {
        return $this->resourceConnection->getConnection();
    }
}
