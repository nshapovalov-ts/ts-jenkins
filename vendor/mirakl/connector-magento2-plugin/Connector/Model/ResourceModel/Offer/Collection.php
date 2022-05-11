<?php
namespace Mirakl\Connector\Model\ResourceModel\Offer;

use Magento\Catalog\Model\Product\Attribute\Source\Status as ProductStatus;
use Magento\Catalog\Model\ResourceModel\Product as ProductResource;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Mirakl\Core\Model\Shop;

class Collection extends AbstractCollection
{
    /**
     * @var string
     */
    protected $_idFieldName = \Mirakl\Connector\Model\Offer::OFFER_ID;

    /**
     * @var bool
     */
    protected $_isEnterprise;

    /**
     * @var ProductResource
     */
    protected $productResource;

    /**
     * Set resource model
     */
    protected function _construct()
    {
        $this->_init(\Mirakl\Connector\Model\Offer::class, \Mirakl\Connector\Model\ResourceModel\Offer::class);
        $this->_isEnterprise = \Mirakl\Core\Helper\Data::isEnterprise();
        $this->productResource = $this->_entityFactory->create(ProductResource::class);
    }

    /**
     * @return  $this
     */
    public function addActiveFilter()
    {
        return $this->addFieldToFilter('active', 'true');
    }

    /**
     * @return  $this
     */
    public function addAvailableFilter()
    {
        $this->addActiveFilter();

        $this->joinShops(['shop_state' => 'shops.state']);
        $this->getSelect()->where('shops.state = ?', Shop::STATE_OPEN);

        return $this;
    }

    /**
     * @param   string  $currencyCode
     * @return  $this
     */
    public function addCurrencyCodeFilter($currencyCode)
    {
        return $this->addFieldToFilter('currency_iso_code', $currencyCode);
    }

    /**
     * @return  $this
     */
    public function addProductNames()
    {
        $this->joinProductIds();

        $entityCol = $this->productResource->getLinkField();
        $attribute = $this->productResource->getAttribute('name');
        $attributeId = $attribute->getId();
        $this->getSelect()->joinLeft(
            ['names' => $attribute->getBackendTable()],
            "names.attribute_id = $attributeId AND products.$entityCol = names.$entityCol AND names.store_id = 0",
            ['product_name' => 'value']
        );

        return $this;
    }

    /**
     * @param   int|null    $storeId
     * @return  $this
     */
    public function addProductsEnabledFilter($storeId = null)
    {
        $this->joinProductIds();

        $defaultStoreId = \Magento\Store\Model\Store::DEFAULT_STORE_ID;
        if (null === $storeId) {
            $storeId = $defaultStoreId;
        }

        $entityCol = $this->productResource->getLinkField();
        $attribute = $this->productResource->getAttribute('status');
        $attributeId = $attribute->getId();

        $colsExpr = ['status' => $this->_conn->getIfNullSql('status.value', 'status_default.value')];

        $this->getSelect()->joinLeft(
            ['status_default' => $attribute->getBackendTable()],
            "status_default.attribute_id = $attributeId AND products.$entityCol = status_default.$entityCol" .
                " AND status_default.store_id = $defaultStoreId",
            []
        );

        $this->getSelect()->joinLeft(
            ['status' => $attribute->getBackendTable()],
            "status.attribute_id = $attributeId AND products.$entityCol = status.$entityCol" .
                " AND status.store_id = $storeId",
            $colsExpr
        );

        $enabled = $this->_conn->quote(ProductStatus::STATUS_ENABLED);
        $this->getSelect()->where("status.value = $enabled OR (status.value IS NULL AND status_default.value = $enabled)");

        return $this;
    }

    /**
     * @param   string|array    $sku
     * @return  $this
     */
    public function addProductSkuFilter($sku)
    {
        return $this->addFieldToFilter('product_sku', ['in' => (array) $sku]);
    }

    /**
     * @param   string|array    $offerIds
     * @return  $this
     */
    public function excludeOfferIdsFilter($offerIds)
    {
        return $this->addFieldToFilter('offer_id', ['nin' => (array) $offerIds]);
    }

    /**
     * @param   bool    $leftJoin
     * @param   bool    $withNames  @deprecated Use addProductNames() instead
     * @return  $this
     */
    public function joinProductIds($leftJoin = false, $withNames = false)
    {
        if ($this->getFlag('join_products')) {
            return $this;
        }

        $joinType = $leftJoin ? 'joinLeft' : 'join';
        $this->getSelect()->$joinType(
            ['products' => $this->getTable('catalog_product_entity')],
            'main_table.product_sku = products.sku',
            ['product_id' => 'entity_id']
        );

        if ($withNames) {
            $this->addProductNames();
        }

        $this->setFlag('join_products', true);

        return $this;
    }

    /**
     * @param   string|array    $cols
     * @return  $this
     */
    public function joinShops($cols = '*')
    {
        $this->getSelect()->join(
            ['shops' => $this->getTable('mirakl_shop')],
            'main_table.shop_id = shops.id',
            $cols
        );

        return $this;
    }
}
