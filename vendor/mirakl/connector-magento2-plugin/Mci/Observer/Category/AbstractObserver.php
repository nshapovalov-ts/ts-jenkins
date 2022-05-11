<?php
namespace Mirakl\Mci\Observer\Category;

use Mirakl\Api\Helper\Config as ApiConfig;
use Mirakl\Mci\Helper\Config as MciConfigHelper;
use Mirakl\Mci\Helper\Hierarchy as HierarchyHelper;

abstract class AbstractObserver
{
    /**
     * @var ApiConfig
     */
    protected $apiConfigHelper;

    /**
     * @var MciConfigHelper
     */
    protected $mciConfigHelper;

    /**
     * @var HierarchyHelper
     */
    protected $hierarchyHelper;

    /**
     * @var bool
     */
    protected $enabled = true;

    /**
     * @param   ApiConfig       $apiConfigHelper
     * @param   MciConfigHelper $mciConfigHelper
     * @param   HierarchyHelper $hierarchyHelper
     */
    public function __construct(
        ApiConfig $apiConfigHelper,
        MciConfigHelper $mciConfigHelper,
        HierarchyHelper $hierarchyHelper
    ) {
        $this->apiConfigHelper = $apiConfigHelper;
        $this->mciConfigHelper = $mciConfigHelper;
        $this->hierarchyHelper = $hierarchyHelper;
        $this->enabled         = $this->mciConfigHelper->isSyncHierarchies();
    }

    /**
     * @return  int
     */
    protected function getRootCategoryId()
    {
        return $this->mciConfigHelper->getHierarchyRootCategoryId();
    }

    /**
     * @return  bool
     */
    protected function isEnabled()
    {
        return $this->apiConfigHelper->isEnabled() && $this->enabled;
    }
}
