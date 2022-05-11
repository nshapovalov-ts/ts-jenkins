<?php
namespace Retailplace\CategorySidemenu\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\Request\Http;

class Data extends AbstractHelper
{
    /**
     * @var \Amasty\Shopby\Helper\Config
     */
    private $amastyConfig;
    /**
     * @var Http
     */
    private $request;

    /**
     * Data constructor.
     * @param Context $context
     * @param \Amasty\Shopby\Helper\Config $amastyConfig
     * @param Http $request
     */
    public function __construct(
        Context $context,
        \Amasty\Shopby\Helper\Config $amastyConfig,
        Http $request
    ) {
        parent::__construct($context);
        $this->amastyConfig = $amastyConfig;
        $this->request = $request;
    }

    /**
     * @return bool
     */
    public function isCategoryBucketDisabledOnCategoryPage()
    {
        return $this->request->getFullActionName() == 'catalog_category_view' && (bool) $this->amastyConfig->getModuleConfig('category_filter/disable_on_category');
    }
}
