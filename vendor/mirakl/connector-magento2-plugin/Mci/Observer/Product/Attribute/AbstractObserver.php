<?php
namespace Mirakl\Mci\Observer\Product\Attribute;

use Mirakl\Api\Helper\Config as ApiConfig;
use Mirakl\Mci\Helper\Config as MciConfigHelper;
use Mirakl\Mci\Helper\Attribute as AttributeHelper;
use Mirakl\Mci\Helper\ValueList as ValueListHelper;

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
     * @var AttributeHelper
     */
    protected $attributeHelper;

    /**
     * @var ValueListHelper
     */
    protected $valueListHelper;

    /**
     * @param   ApiConfig       $apiConfigHelper
     * @param   MciConfigHelper $mciConfigHelper
     * @param   AttributeHelper $attributeHelper
     * @param   ValueListHelper $valueListHelper
     */
    public function __construct(
        ApiConfig $apiConfigHelper,
        MciConfigHelper $mciConfigHelper,
        AttributeHelper $attributeHelper,
        ValueListHelper $valueListHelper
    ) {
        $this->apiConfigHelper = $apiConfigHelper;
        $this->mciConfigHelper = $mciConfigHelper;
        $this->attributeHelper = $attributeHelper;
        $this->valueListHelper = $valueListHelper;
    }

    /**
     * @return  bool
     */
    protected function isApiEnabled()
    {
        return $this->apiConfigHelper->isEnabled();
    }
}
