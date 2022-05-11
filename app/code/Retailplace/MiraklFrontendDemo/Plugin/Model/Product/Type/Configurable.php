<?php
/**
 * Retailplace_MiraklFrontendDemo
 *
 * @copyright   Copyright (c) 2022 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Alexander Korsun <aleksandr@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\MiraklFrontendDemo\Plugin\Model\Product\Type;

use Magento\ConfigurableProduct\Model\Product\Type\Configurable as ProductConfigurable;
use Closure;
use Magento\Catalog\Model\Product;

class Configurable
{
    /**
     * Plugin Around for IsSalable
     *
     * @param ProductConfigurable $subject
     * @param Closure $proceed
     * @param Product $product
     * @return bool
     */
    public function aroundIsSalable(
        ProductConfigurable $subject,
        Closure $proceed,
        Product $product
    ): bool {

        if ($product->hasData('skip_saleable_check')) {
            return (bool) $product->getData('skip_saleable_check');
        }

        return $proceed($product);
    }
}
