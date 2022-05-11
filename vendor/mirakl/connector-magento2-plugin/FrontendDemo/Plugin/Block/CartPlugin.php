<?php
namespace Mirakl\FrontendDemo\Plugin\Block;

use Magento\Checkout\Block\Cart;
use Magento\Quote\Model\Quote\Item as QuoteItem;
use Mirakl\FrontendDemo\Block\Cart\Item\Shipping as ShippingBlock;

class CartPlugin
{
    /**
     * Display shipping types before cart items
     *
     * @param   Cart        $subject
     * @param   \Closure    $proceed
     * @param   QuoteItem   $item
     * @return  string
     */
    public function aroundGetItemHtml(Cart $subject, \Closure $proceed, QuoteItem $item)
    {
        /** @var ShippingBlock $shippingBlock */
        $shippingBlock = $subject->getLayout()
            ->createBlock(ShippingBlock::class);

        $shippingHtml = $shippingBlock
            ->setItem($item)
            ->toHtml();

        return $shippingHtml . $proceed($item);
    }
}