<?php namespace Retailplace\CategorySidemenu\Plugin\Amasty\Shopby\Helper;

use Retailplace\CategorySidemenu\Helper\Data;

class Config
{
    /**
     * @var Data
     */
    private $categorySidemenuHelper;

    /**
     * SearchEngineInterface constructor.
     * @param Data $categorySidemenuHelper
     */
    public function __construct(
        Data $categorySidemenuHelper
    ) {
        $this->categorySidemenuHelper = $categorySidemenuHelper;

    }

    /**
     * @param \Amasty\Shopby\Helper\Config $subject
     * @param $result
     * @return false
     */
    public function afterIsCategoryFilterEnabled(
        \Amasty\Shopby\Helper\Config $subject,
        $result
    ) {
        if ($this->categorySidemenuHelper->isCategoryBucketDisabledOnCategoryPage()) {
            return false;
        } else {
            return $result;
        }
    }
}
