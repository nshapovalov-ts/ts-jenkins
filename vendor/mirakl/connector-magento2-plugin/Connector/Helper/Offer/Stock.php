<?php
namespace Mirakl\Connector\Helper\Offer;

use Magento\CatalogInventory\Model\Configuration;
use Magento\CatalogInventory\Model\ResourceModel\Stock as StockResource;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Store\Model\ScopeInterface;
use Mirakl\Connector\Model\OfferFactory;
use Mirakl\Connector\Model\ResourceModel\Offer\Collection as OfferCollection;
use Mirakl\Connector\Model\ResourceModel\Offer\CollectionFactory as OfferCollectionFactory;

class Stock extends AbstractHelper
{
    /**
     * Is initialized configuration flag
     *
     * @var bool
     */
    protected $_isConfig;

    /**
     * Manage Stock flag
     *
     * @var bool
     */
    protected $_isConfigManageStock;

    /**
     * Backorders
     *
     * @var bool
     */
    protected $_isConfigBackorders;

    /**
     * Minimum quantity allowed in shopping card
     *
     * @var int
     */
    protected $_configMinQty;

    /**
     * @var StockResource
     */
    protected $stockResource;

    /**
     * @var OfferFactory
     */
    protected $offerFactory;

    /**
     * @var OfferCollectionFactory
     */
    protected $offerCollectionFactory;

    /**
     * @param   Context                     $context
     * @param   StockResource               $stockResource
     * @param   OfferFactory                $offerFactory
     * @param   OfferCollectionFactory      $offerCollectionFactory
     */
    public function __construct(
        Context $context,
        StockResource $stockResource,
        OfferFactory $offerFactory,
        OfferCollectionFactory $offerCollectionFactory
    ) {
        parent::__construct($context);
        $this->stockResource = $stockResource;
        $this->offerFactory = $offerFactory;
        $this->offerCollectionFactory = $offerCollectionFactory;
    }

    /**
     * Load some inventory configuration settings
     *
     * @return  void
     */
    protected function _initConfig()
    {
        if (!$this->_isConfig) {
            $configMap = [
                '_isConfigManageStock' => Configuration::XML_PATH_MANAGE_STOCK,
                '_isConfigBackorders' => Configuration::XML_PATH_BACKORDERS,
                '_configMinQty' => Configuration::XML_PATH_MIN_QTY,
            ];

            foreach ($configMap as $field => $const) {
                $this->{$field} = (int) $this->scopeConfig->getValue(
                    $const,
                    ScopeInterface::SCOPE_STORE
                );
            }

            $this->_isConfig = true;
        }
    }

    /**
     * @return  OfferCollection
     */
    protected function getActiveOffers()
    {
        /** @var OfferCollection $offerCollection */
        $offerCollection = $this->offerCollectionFactory->create();
        $offerCollection->addActiveFilter()
            ->removeAllFieldsFromSelect()
            ->joinProductIds()
            ->removeFieldFromSelect('offer_id')
            ->distinct(true);

        return $offerCollection;
    }

    /**
     * Update In Stock status for products out of stock with active offers
     *
     * @param   array   $skus
     * @return  void
     */
    public function updateInStock(array $skus = [])
    {
        $this->_initConfig();
        $connection = $this->stockResource->getConnection();

        $offerCollection = $this->getActiveOffers();

        if (!empty($skus)) {
            $offerCollection->getSelect()->where('product_sku IN (?)', $skus);
        }

        // Retrieve products that have associated offers
        $productIds = $connection->fetchCol($offerCollection->getSelect());

        if (empty($productIds)) {
            return;
        }

        // Prepare the where condition for products currently out of stock
        $where = sprintf(
            'is_in_stock = 0' .
            ' AND ((use_config_manage_stock = 1 AND 1 = %1$d) OR (use_config_manage_stock = 0 AND manage_stock = 1))' .
            ' AND %2$s',
            $this->_isConfigManageStock,
            $connection->quoteInto('product_id IN (?)', $productIds)
        );

        // Update products stock items
        $connection->update(
            $this->stockResource->getTable('cataloginventory_stock_item'),
            ['is_in_stock' => 1, 'stock_status_changed_auto' => 1],
            $where
        );
    }

    /**
     * Update In Stock status for products without offer and without operator offer
     *
     * @param   array   $skus
     * @return  void
     */
    public function updateOutOfStock(array $skus = [])
    {
        $this->_initConfig();
        $connection = $this->stockResource->getConnection();

        // Retrieve products that have associated offers
        $select = $connection->select()
            ->from(['product' => $this->stockResource->getTable('catalog_product_entity')], 'entity_id')
            ->joinInner(
                ['offers' => $this->stockResource->getTable('mirakl_offer')],
                'product.sku = offers.product_sku',
                []
            )
            ->distinct(true);

        if (!empty($skus)) {
            $select->where('product.sku IN (?)', $skus);
        }

        $offerCollection = $this->getActiveOffers();

        // Excluded products are those that have active offers
        $excludedProductIds = $connection->fetchCol($offerCollection->getSelect());

        // Modify stock of products that do not have any active offer associated
        $includedProductIds = array_diff($connection->fetchCol($select), $excludedProductIds);

        if (empty($includedProductIds)) {
            return;
        }

        // Prepare the where condition for products currently in stock
        $where = sprintf(
            'is_in_stock = 1' .
            ' AND ((use_config_manage_stock = 1 AND 1 = %1$d) OR (use_config_manage_stock = 0 AND manage_stock = 1))' .
            ' AND ((use_config_backorders = 1 AND %2$d = %3$d) OR (use_config_backorders = 0 AND backorders = %2$d))' .
            ' AND ((use_config_min_qty = 1 AND qty <= %4$d) OR (use_config_min_qty = 0 AND qty <= min_qty))' .
            ' AND %5$s',
            $this->_isConfigManageStock,
            \Magento\CatalogInventory\Model\Stock::BACKORDERS_NO,
            $this->_isConfigBackorders,
            $this->_configMinQty,
            $connection->quoteInto('product_id IN (?)', $includedProductIds)
        );

        // Update products stock items
        $connection->update(
            $this->stockResource->getTable('cataloginventory_stock_item'),
            ['is_in_stock' => 0, 'stock_status_changed_auto' => 1],
            $where
        );
    }
}
