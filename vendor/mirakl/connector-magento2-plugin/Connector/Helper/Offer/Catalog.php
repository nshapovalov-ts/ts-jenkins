<?php
namespace Mirakl\Connector\Helper\Offer;

use Magento\Catalog\Model\Product\Attribute\Repository as AttributeRepository;
use Magento\Catalog\Model\ResourceModel\Eav\Attribute as ProductAttribute;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;

class Catalog extends AbstractHelper
{
    /**
     * @var ResourceConnection
     */
    private $resource;

    /**
     * @var AdapterInterface
     */
    private $connection;

    /**
     * @var AttributeRepository
     */
    private $attributeRepository;

    /**
     * @var bool
     */
    private $isEnterprise;

    /**
     * @param   Context             $context
     * @param   ResourceConnection  $resource
     * @param   AttributeRepository $attributeRepository
     */
    public function __construct(
        Context $context,
        ResourceConnection $resource,
        AttributeRepository $attributeRepository
    ) {
        parent::__construct($context);
        $this->resource = $resource;
        $this->connection = $resource->getConnection();
        $this->attributeRepository = $attributeRepository;
        $this->isEnterprise = \Mirakl\Core\Helper\Data::isEnterprise();
    }

    /**
     * @param   string  $attrCode
     * @return  ProductAttribute
     * @throws  \Exception
     */
    private function getAttribute($attrCode)
    {
        return $this->attributeRepository->get($attrCode);
    }

    /**
     * @param   string  $tableName
     * @return  string
     */
    private function getTableName($tableName)
    {
        return $this->resource->getTableName($tableName);
    }

    /**
     * Will update mirakl_shop_ids and mirakl_offer_state_ids attributes according to mirakl_offer table data
     *
     * @param   array   $skus
     * @return  $this
     */
    public function updateAttributes(array $skus = [])
    {
        $this->updateAttribute('mirakl_shop_ids', 'shop_id', 'mirakl_shop', $skus);
        $this->updateAttribute('mirakl_offer_state_ids', 'state_code', 'mirakl_offer_state', $skus);

        return $this;
    }

    /**
     * Update product attribute values.
     * Using direct SQL queries for better performances.
     *
     * @param   string  $attrCode
     * @param   string  $offerTableField
     * @param   string  $customTable
     * @param   array   $skus
     * @return  $this
     */
    public function updateAttribute($attrCode, $offerTableField, $customTable, array $skus = [])
    {
        $attribute = $this->getAttribute($attrCode);

        $entityCol = $this->isEnterprise ? 'row_id' : 'entity_id';

        $entityIds = []; // Will be used to filter entity ids to update

        if (!empty($skus)) {
            $select = $this->connection->select()
                ->from($this->getTableName('catalog_product_entity'), [$entityCol])
                ->where('sku IN (?)', $skus);
            $entityIds = $this->connection->fetchCol($select);

            if (empty($entityIds)) {
                return $this; // Do not do anything if we cannot find any products with given skus
            }
        }

        // Reset all values of this attribute
        $params = ['attribute_id = ?' => $attribute->getAttributeId()];
        if (!empty($entityIds)) {
            $params["$entityCol IN (?)"] = $entityIds;
        }
        $this->connection->update($attribute->getBackendTable(), ['value' => null], $params);

        // Update values of this attribute
        $select = $this->connection->select()
            ->from(['o' => $this->getTableName('mirakl_offer')], '')
            ->join(
                ['c' => $this->getTableName($customTable)],
                "o.$offerTableField = c.id",
                ''
            )
            ->join(
                ['p' => $this->getTableName('catalog_product_entity')],
                'o.product_sku = p.sku',
                ''
            )
            ->columns([
                'attribute_id' => new \Zend_Db_Expr($attribute->getAttributeId()),
                'store_id' => new \Zend_Db_Expr(\Magento\Store\Model\Store::DEFAULT_STORE_ID),
                $entityCol => "p.$entityCol",
                'value' => new \Zend_Db_Expr("GROUP_CONCAT(DISTINCT c.eav_option_id SEPARATOR ',')")
            ])
            ->where('o.active = ?', 'true')
            ->group('o.product_sku');

        if (!empty($entityIds)) {
            $select->where("p.$entityCol IN (?)", $entityIds);
        }

        // Disable staging preview in EE and handle multiple row ids for unique entity_id
        if ($this->isEnterprise) {
            $select->setPart('disable_staging_preview', true);
            $select->group('p.row_id');
        }

        $sql = $this->connection->insertFromSelect(
            $select,
            $attribute->getBackendTable(),
            ['attribute_id', 'store_id', $entityCol, 'value'],
            AdapterInterface::INSERT_ON_DUPLICATE
        );
        $this->connection->query($sql);

        return $this;
    }
}
