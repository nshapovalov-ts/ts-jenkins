<?php
namespace Mirakl\Mcm\Observer\Product;

use Mirakl\Api\Helper\Config as ApiConfig;
use Mirakl\Core\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Mirakl\Mcm\Helper\Config as ConfigHelper;
use Mirakl\Mcm\Helper\Product\Export\Process as ProcessHelper;
use Mirakl\Mcm\Helper\Product\Export\Product as ProductHelper;

abstract class AbstractObserver
{
    /**
     * @var ConfigHelper
     */
    protected $configHelper;

    /**
     * @var ApiConfig
     */
    protected $apiConfigHelper;

    /**
     * @var ProcessHelper
     */
    protected $processHelper;

    /**
     * @var ProductHelper
     */
    protected $productHelper;

    /**
     * @var ProductCollectionFactory
     */
    protected $productCollectionFactory;

    /**
     * @var bool
     */
    protected $enabled = true;

    /**
     * @param   ApiConfig                   $apiConfigHelper
     * @param   ConfigHelper                $configHelper
     * @param   ProcessHelper               $processHelper
     * @param   ProductHelper               $productHelper
     * @param   ProductCollectionFactory    $productCollectionFactory
     */
    public function __construct(
        ApiConfig $apiConfigHelper,
        ConfigHelper $configHelper,
        ProcessHelper $processHelper,
        ProductHelper $productHelper,
        ProductCollectionFactory $productCollectionFactory
    ) {
        $this->apiConfigHelper          = $apiConfigHelper;
        $this->configHelper             = $configHelper;
        $this->processHelper            = $processHelper;
        $this->productHelper            = $productHelper;
        $this->enabled                  = $this->configHelper->isMcmEnabled();
        $this->productCollectionFactory = $productCollectionFactory;
    }

    /**
     * @return  bool
     */
    protected function isEnabled()
    {
        return $this->apiConfigHelper->isEnabled() && $this->enabled;
    }
}
