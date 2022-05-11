<?php declare(strict_types=1);

namespace Retailplace\Wishlist\Plugin\CustomerData;

use Magento\Wishlist\Helper\Data as HelperData;

class WishlistPlugin
{
    /**
     * @var string
     */
    const ITEMS_NUMBER = 30;

    /**
     * @var HelperData
     */
    private $wishlistHelper;

    /**
     * WishlistPlugin constructor.
     *
     * @param HelperData $wishlistHelper
     */
    public function __construct(
        HelperData $wishlistHelper
    ) {
        $this->wishlistHelper = $wishlistHelper;
    }

    /**
     * @param $subject
     * @param $result
     * @return array
     */
    public function afterGetSectionData($subject, $result)
    {
        if (is_array($result)) {
            $result['wishlist_item_ids'] = $this->getItemIds();
            $result['counterItems'] = $this->wishlistHelper->getWishlistItemCollection()->getSize();
        }

        return $result;
    }

    /**
     * Get wishlist item ids
     *
     * @return array
     */
    protected function getItemIds()
    {
        $collection = $this->wishlistHelper->getWishlistItemCollection();
        $collection->setPageSize(self::ITEMS_NUMBER);
        $collection->clear()->setInStockFilter(true)->setOrder('added_at');
        $items = [];
        foreach ($collection->getItems() as $item) {
            $items[$item['product_id']] = $item['seller_id'];
        }

        return $items;
    }
}
