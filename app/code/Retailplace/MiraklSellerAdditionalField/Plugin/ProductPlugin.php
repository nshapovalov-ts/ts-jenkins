<?php

namespace Retailplace\MiraklSellerAdditionalField\Plugin;

use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Retailplace\MiraklSellerAdditionalField\Helper\Data;

class ProductPlugin
{
    /**
     * @var Data
     */
    protected $helper;

    /**
     * @param Data $helper
     */
    public function __construct(
        Data $helper
    ) {
        $this->helper = $helper;
    }

    /**
     * @param Product $subject
     * @param int $result
     * @return int
     */
    public function afterGetStatus(Product $subject, $result)
    {
        if ($result == Status::STATUS_DISABLED) {
            return $result;
        }
        if (!$subject->getMiraklShopIds()) {
            return Status::STATUS_DISABLED;
        }
        $shopOptionIds = $this->helper->getAllowedShopOptionIds();
        $productShopIds = explode(',', $subject->getMiraklShopIds());
        if (array_intersect($productShopIds, $shopOptionIds)) {
            return $result;
        };
        return Status::STATUS_DISABLED;
    }
}
