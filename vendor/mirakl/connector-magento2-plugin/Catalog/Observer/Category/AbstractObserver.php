<?php
namespace Mirakl\Catalog\Observer\Category;

use Mirakl\Api\Helper\Config as ApiConfig;
use Mirakl\Catalog\Helper\Category as CategoryHelper;
use Mirakl\Catalog\Helper\Config as CatalogConfig;

abstract class AbstractObserver
{
    /**
     * @var CategoryHelper
     */
    protected $categoryHelper;

    /**
     * @var CatalogConfig
     */
    protected $catalogConfigHelper;

    /**
     * @var ApiConfig
     */
    protected $apiConfigHelper;

    /**
     * @var bool
     */
    protected $enabled = true;

    /**
     * @param   ApiConfig       $apiConfigHelper
     * @param   CatalogConfig   $catalogConfigHelper
     * @param   CategoryHelper  $categoryHelper
     */
    public function __construct(
        ApiConfig $apiConfigHelper,
        CatalogConfig $catalogConfigHelper,
        CategoryHelper $categoryHelper
    ) {
        $this->apiConfigHelper     = $apiConfigHelper;
        $this->catalogConfigHelper = $catalogConfigHelper;
        $this->categoryHelper      = $categoryHelper;
        $this->enabled             = $this->catalogConfigHelper->isSyncCategories();
    }

    /**
     * @return  bool
     */
    protected function isEnabled()
    {
        return $this->apiConfigHelper->isEnabled() && $this->enabled;
    }
}
