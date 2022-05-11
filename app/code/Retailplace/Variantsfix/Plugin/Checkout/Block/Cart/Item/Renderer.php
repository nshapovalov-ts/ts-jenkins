<?php

/**
 * Retailplace_Variantsfix
 *
 * @copyright   Copyright (c) 2021 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Evgeniy Derevyanko <evgeniy@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\Variantsfix\Plugin\Checkout\Block\Cart\Item;

use Magento\Checkout\Block\Cart\Item\Renderer as RendererClass;
use Magento\Quote\Model\Quote\Item\AbstractItem;

class Renderer
{
    /**
     * @param RendererClass $subject
     * @param AbstractItem $item
     * @return array
     */
    public function beforeSetItem(
        RendererClass $subject,
        AbstractItem $item
    ): array {
        $product = $item->getProduct();
        if ($product->getTypeId() == "configurable") {
            foreach ($item->getChildren() as $childItem) {
                $product->setName($childItem->getName());
                $item->setProduct($product);
            }
        }
        return [$item];
    }
}
