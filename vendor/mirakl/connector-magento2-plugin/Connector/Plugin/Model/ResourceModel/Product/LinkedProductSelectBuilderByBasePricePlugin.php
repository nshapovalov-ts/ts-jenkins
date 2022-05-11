<?php
namespace Mirakl\Connector\Plugin\Model\ResourceModel\Product;

use Magento\Catalog\Model\ResourceModel\Product\LinkedProductSelectBuilderByBasePrice;

class LinkedProductSelectBuilderByBasePricePlugin extends AbstractLinkedProductSelectBuilderPlugin
{
    /**
     * @param   LinkedProductSelectBuilderByBasePrice   $subject
     * @param   \Magento\Framework\DB\Select[]          $result
     * @return  \Magento\Framework\DB\Select[]
     */
    public function afterBuild(LinkedProductSelectBuilderByBasePrice $subject, $result)
    {
        return $this->handleOffersMinPrice($result, 't.value');
    }
}