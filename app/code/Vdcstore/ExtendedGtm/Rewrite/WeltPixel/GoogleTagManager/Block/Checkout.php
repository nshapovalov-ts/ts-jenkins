<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Vdcstore\ExtendedGtm\Rewrite\WeltPixel\GoogleTagManager\Block;

class Checkout extends \WeltPixel\GoogleTagManager\Block\Checkout
{
	/**
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \WeltPixel\GoogleTagManager\Helper\Data $helper
     * @param \WeltPixel\GoogleTagManager\Model\Storage $storage
     * @param \WeltPixel\GoogleTagManager\Model\CookieManager $cookieManager
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \WeltPixel\GoogleTagManager\Helper\Data $helper,
        \WeltPixel\GoogleTagManager\Model\Storage $storage,
        \WeltPixel\GoogleTagManager\Model\CookieManager $cookieManager,
        \Magento\Checkout\Helper\Cart $cartHelper,
        array $data = []
    )
    {
        $this->helper = $helper;
        $this->storage = $storage;
        $this->cookieManager = $cookieManager;
        $this->cartHelper = $cartHelper;
        parent::__construct($context, $helper, $storage, $cookieManager, $data);
    }
    /**
     * Returns the product details for the purchase gtm event
     * @return array
     */
    public function getProducts() {
        $quote = $this->getQuote();
        $products = [];
        $displayOption = $this->helper->getParentOrChildIdUsage();

        foreach ($quote->getAllVisibleItems() as $item) {
            $product = $item->getProduct();

            if ($displayOption == \WeltPixel\GoogleTagManager\Model\Config\Source\ParentVsChild::CHILD) {
                if ($item->getProductType() == \Magento\ConfigurableProduct\Model\Product\Type\Configurable::TYPE_CODE) {
                    $children = $item->getChildren();
                    foreach ($children as $child) {
                        $product = $child->getProduct();
                    }
                }
            }

            $productDetail = [];
            $productDetail['name'] = html_entity_decode($item->getName());
            $productDetail['id'] = $this->helper->getGtmProductId($product);
            $productDetail['price'] = number_format((float)$item->getPriceInclTax(), 2, '.', '');
            if ($this->helper->isBrandEnabled()) :
                $productDetail['brand'] = $this->helper->getGtmBrand($product);
            endif;
            $categoryName =  $this->helper->getGtmCategoryFromCategoryIds($product->getCategoryIds());
            $productDetail['category'] = $categoryName;
            $productDetail['quantity'] = $item->getQty();
            $products[] = $productDetail;
        }

        return $products;
    }

    public function getCartTotal()
    {
        $quote = $this->getQuote();
        return $quote->getGrandTotal();
    }

    public function getCartSummaryCount()
    {
        return $this->cartHelper->getSummaryCount();
    }
}

