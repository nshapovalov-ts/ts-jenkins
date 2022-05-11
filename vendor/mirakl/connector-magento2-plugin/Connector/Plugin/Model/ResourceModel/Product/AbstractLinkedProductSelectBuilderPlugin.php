<?php
namespace Mirakl\Connector\Plugin\Model\ResourceModel\Product;

use Magento\Catalog\Model\ResourceModel\Product\BaseSelectProcessorInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Select;

abstract class AbstractLinkedProductSelectBuilderPlugin
{
    /**
     * @var ResourceConnection
     */
    protected $resource;

    /**
     * @param   ResourceConnection  $resourceConnection
     */
    public function __construct(ResourceConnection $resourceConnection)
    {
        $this->resource = $resourceConnection;
    }

    /**
     * @return  Select
     */
    protected function getOfferSubSelect()
    {
        $offerTable      = $this->resource->getTableName('mirakl_offer');
        $offerStateTable = $this->resource->getTableName('mirakl_offer_state');
        $shopTable       = $this->resource->getTableName('mirakl_shop');

        $subSelect = $this->resource->getConnection()->select();
        $subSelect->from(['offer' => $offerTable], 'offer.price')
            ->joinInner(['offer_state' => $offerStateTable], 'offer.state_code = offer_state.id', [])
            ->joinInner(['shop' => $shopTable], "offer.shop_id = shop.id AND shop.state = 'OPEN'", [])
            ->where(BaseSelectProcessorInterface::PRODUCT_TABLE_ALIAS . '.sku = offer.product_sku')
            ->where('offer.active = ?', 'true')
            ->order('offer_state.sort_order ' . Select::SQL_DESC)
            ->order('offer.price ' . Select::SQL_ASC)
            ->limit(1);

        return $subSelect;
    }

    /**
     * @param   Select[]    $result
     * @param   string      $defaultPriceField
     * @return  Select[]
     */
    protected function handleOffersMinPrice(&$result, $defaultPriceField)
    {
        // Add a sub query to find a potential best offer if price of simple product is 0
        $subSelect = $this->getOfferSubSelect();

        // Use minus sign (-) in order to sort DESC later, it will put NULL values for min_price at the end
        $minPriceExpr = sprintf('- IF(%1$s > 0, %1$s, (%2$s))', $defaultPriceField, $subSelect->__toString());

        foreach ($result as &$select) {
            $select->columns(['min_price' => new \Zend_Db_Expr($minPriceExpr)]);
            $select->reset(Select::ORDER); // reset default min price sorting
            $select->order('min_price ' . Select::SQL_DESC);

            // Need to rewrite the main select to have only 1 column selected for future UNION usage
            $select = $this->resource->getConnection()->select()
                ->from([BaseSelectProcessorInterface::PRODUCT_TABLE_ALIAS => $select], ['entity_id']);
        }

        return $result;
    }
}