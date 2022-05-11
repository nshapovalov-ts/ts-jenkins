<?php
namespace Mirakl\Catalog\Observer\Product;

use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\Framework\Registry;
use Mirakl\Api\Helper\Config as ApiConfig;
use Mirakl\Catalog\Helper\Config as CatalogConfig;
use Mirakl\Catalog\Helper\Product as ProductHelper;

abstract class AbstractObserver
{
    /**
     * @var ProductHelper
     */
    protected $productHelper;

    /**
     * @var CatalogConfig
     */
    protected $catalogConfigHelper;

    /**
     * @var ApiConfig
     */
    protected $apiConfigHelper;

    /**
     * @var ProductCollectionFactory
     */
    protected $productCollectionFactory;

    /**
     * @var Registry
     */
    protected $registry;

    /**
     * @var bool
     */
    protected $enabled = true;

    /**
     * @param   ApiConfig                   $apiConfigHelper
     * @param   CatalogConfig               $catalogConfigHelper
     * @param   ProductHelper               $productHelper
     * @param   ProductCollectionFactory    $productCollectionFactory
     * @param   Registry                    $registry
     */
    public function __construct(
        ApiConfig $apiConfigHelper,
        CatalogConfig $catalogConfigHelper,
        ProductHelper $productHelper,
        ProductCollectionFactory $productCollectionFactory,
        Registry $registry
    ) {
        $this->apiConfigHelper          = $apiConfigHelper;
        $this->catalogConfigHelper      = $catalogConfigHelper;
        $this->productHelper            = $productHelper;
        $this->productCollectionFactory = $productCollectionFactory;
        $this->registry                 = $registry;
        $this->enabled                  = $this->catalogConfigHelper->isSyncProducts();
    }

    /**
     * @return  bool
     */
    protected function isEnabled()
    {
        return ($this->apiConfigHelper->isEnabled() || $this->registry->registry('mirakl_import_working') === true) && $this->enabled;
    }
}
