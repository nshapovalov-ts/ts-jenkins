<?php
namespace Mirakl\Mci\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;

class Hash extends AbstractHelper
{
    const TABLE_NAME = 'mirakl_product_import';

    /**
     * @var ResourceConnection
     */
    protected $resource;

    /**
     * @var AdapterInterface
     */
    protected $connection;

    /**
     * @var Config
     */
    protected $mciConfig;

    /**
     * @param   Context             $context
     * @param   ResourceConnection  $resource
     * @param   Config              $mciConfig
     */
    public function __construct(
        Context $context,
        ResourceConnection $resource,
        Config $mciConfig
    ) {
        parent::__construct($context);
        $this->resource = $resource;
        $this->connection = $resource->getConnection();
        $this->mciConfig = $mciConfig;
    }

    /**
     * Clear all products import hashes
     */
    public function clearHashes()
    {
        $tableName = $this->resource->getTableName(static::TABLE_NAME);
        $this->connection->truncateTable($tableName);
    }

    /**
     * Delete hash for specified shop and sku
     *
     * @param   int     $shopId
     * @param   string  $sku
     * @return  int
     */
    public function deleteShopHash($shopId, $sku)
    {
        return $this->connection->delete(
            $this->resource->getTableName(static::TABLE_NAME),
            ['shop_id = ?' => $shopId, 'sku = ?' => $sku]
        );
    }

    /**
     * Returns true if we have to check data hash for specified shop before import
     *
     * @param   int     $shopId
     * @param   string  $sku
     * @param   string  $hash
     * @return  bool
     */
    public function isShopHashExists($shopId, $sku, $hash)
    {
        if (!$this->mciConfig->isCheckDataHash()) {
            return false;
        }

        $select = $this->connection->select()
            ->from($this->resource->getTableName(static::TABLE_NAME), 'hash')
            ->where('shop_id = ?', $shopId)
            ->where('sku = ?', $sku)
            ->where('hash = ?', $hash)
            ->limit(1);

        return (bool) $this->connection->fetchOne($select);
    }

    /**
     * Saves given hash for specified shop id
     *
     * @param   int     $shopId
     * @param   string  $sku
     * @param   string  $hash
     * @return  int
     */
    public function saveShopHash($shopId, $sku, $hash)
    {
        return $this->connection->insertOnDuplicate(
            $this->resource->getTableName(static::TABLE_NAME),
            ['shop_id' => $shopId, 'sku' => $sku, 'hash' => $hash]
        );
    }
}
