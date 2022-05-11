<?php

namespace Retailplace\Shopby\Plugin\ShopbySeo\Helper;

use Amasty\Shopby\Helper\Category;

class Url
{
    const SHOPBY_EXTRA_PARAM = 'amshopby';

    /**
     * @var Category
     */
    private $shopbyCategoryHelper;

    public function __construct(
        Category $categoryHelper
    ) {
        $this->shopbyCategoryHelper = $categoryHelper;
    }

    /**
     * @param \Amasty\ShopbySeo\Helper\Url $subject
     * @param array $result
     * @return array
     */
    public function afterParseQuery(\Amasty\ShopbySeo\Helper\Url $subject, $result)
    {
        if ($subject->getParam(self::SHOPBY_EXTRA_PARAM)) {
            foreach ($subject->getParam(self::SHOPBY_EXTRA_PARAM) as $name => $value) {
                $subject->setParam($name, implode(',', $value));
            }
            $subject->setParam(self::SHOPBY_EXTRA_PARAM, null);
        }
        return $result;
    }

    /**
     * @param \Amasty\ShopbySeo\Helper\Url $subject
     * @param bool $result
     * @return bool
     */
    public function afterHasCategoryFilterParam(\Amasty\ShopbySeo\Helper\Url $subject, $result)
    {
        return $result && !$this->shopbyCategoryHelper->isMultiselect();
    }
}
