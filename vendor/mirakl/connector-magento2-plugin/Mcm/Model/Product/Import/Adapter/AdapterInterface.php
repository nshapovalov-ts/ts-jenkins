<?php
namespace Mirakl\Mcm\Model\Product\Import\Adapter;

use Magento\Catalog\Model\Product;

interface AdapterInterface
{
    /**
     * @param   array   $data
     * @return  Product
     */
    public function import(array $data);
}
