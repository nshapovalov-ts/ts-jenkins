<?php
namespace Mirakl\Core\Model\Config\Source;

use Magento\Framework\Option\ArrayInterface;
use Mirakl\Core\Model\Shop;

class ShopStates implements ArrayInterface
{
    /**
     * @return array
     */
    public function toOptionArray()
    {
        return Shop::getStates();
    }
}
