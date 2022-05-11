<?php
namespace Mirakl\Mci\Model\System\Config\Source\Attribute\Product;

use Magento\Catalog\Model\ResourceModel\ProductFactory as ProductResourceFactory;

class Tax
{
    /**
     * @var ProductResourceFactory
     */
    protected $productResourceFactory;

    /**
     * @param   ProductResourceFactory  $productResourceFactory
     */
    public function __construct(ProductResourceFactory $productResourceFactory)
    {
        $this->productResourceFactory = $productResourceFactory;
    }

    /**
     * Retrieves product tax classes
     *
     * @return  array
     */
    public function toOptionArray()
    {
        return $this->productResourceFactory->create()
            ->getAttribute('tax_class_id')
            ->getSource()
            ->getAllOptions();
    }
}
