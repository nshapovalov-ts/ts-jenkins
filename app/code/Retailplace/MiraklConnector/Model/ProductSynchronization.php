<?php

/**
 * Retailplace_MiraklConnector
 *
 * @copyright   Copyright © 2022 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Dmitriy Kosykh <dmitriykosykh@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\MiraklConnector\Model;

use Mirakl\MMP\Common\Request\Product\Export\ExportProductsRequest;
use Mirakl\Api\Helper\Mcm\Product as ProductApiHelper;
use Magento\Framework\App\ResourceConnection;

/**
 * Class ProductSynchronization is used to remove products that are not in mirakl
 */
class ProductSynchronization
{
    /**
     * @var ProductApiHelper
     */
    private $productApiHelper;

    /**
     * @var array
     */
    private $productFromMirakl = [];

    /**
     * @var \Magento\Framework\DB\Adapter\AdapterInterface
     */
    private $connection;

    /**
     * @param ProductApiHelper $productApiHelper
     * @param ResourceConnection $resourceConnection
     */
    public function __construct(
        ProductApiHelper $productApiHelper,
        ResourceConnection $resourceConnection
    ) {
        $this->productApiHelper = $productApiHelper;
        $this->connection = $resourceConnection->getConnection();
    }

    /**
     * @throws \Exception
     */
    public function getAllProductFromMirakl(): void
    {
        try {
            $request = new ExportProductsRequest();

            $apiFile = $this->productApiHelper->send($request);
            if (!$apiFile->count()) {
                throw new \Exception('Empty file');
            }

            $this->productFromMirakl = [];
            foreach ($apiFile->getItems() as $item) {
                $this->productFromMirakl[] = $item->getSku();
            }
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }
    }

    public function deleteSimpleProducts(): void
    {
        $select = $this->connection->select()
            ->from(['cpe' => 'catalog_product_entity'], 'sku')
            ->where('cpe.sku NOT IN (?)', $this->productFromMirakl)
            ->where('cpe.type_id = ?', 'simple');

        $skusSimpleNotExistInMirakl = $this->connection->fetchCol($select, 'sku');

        foreach (array_chunk($skusSimpleNotExistInMirakl, 1000) as $skus) {
            $this->connection->delete('catalog_product_entity', [
                'sku IN (?)' => $skus
            ]);
        }
    }

    public function deleteConfigurableWithoutChildren(): void
    {
        $select = $this->connection->select()
            ->from(['cpe' => 'catalog_product_entity'], 'sku')
            ->joinLeft(['l' => 'catalog_product_super_link'], 'parent_id = entity_id', ['*'])
            ->where('cpe.type_id = ?', 'configurable')
            ->where('l.parent_id IS NULL');

        $configurableWithoutChildren = $this->connection->fetchCol($select, 'sku');

        foreach (array_chunk($configurableWithoutChildren, 1000) as $skus) {
            $this->connection->delete('catalog_product_entity', [
                'sku IN (?)' => $skus
            ]);
        }
    }
}
