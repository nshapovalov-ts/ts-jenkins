<?php
namespace Mirakl\Connector\Plugin\Model\ResourceModel\Product;

use Magento\Catalog\Model\ResourceModel\Product\Indexer\LinkedProductSelectBuilderByIndexPrice;

class LinkedProductSelectBuilderByIndexPricePlugin extends AbstractLinkedProductSelectBuilderPlugin
{
    /**
     * @param   LinkedProductSelectBuilderByIndexPrice  $subject
     * @param   \Magento\Framework\DB\Select[]          $result
     * @return  \Magento\Framework\DB\Select[]
     */
    public function afterBuild(LinkedProductSelectBuilderByIndexPrice $subject, $result)
    {
        return $this->handleOffersMinPrice($result, 't.min_price');
    }
}