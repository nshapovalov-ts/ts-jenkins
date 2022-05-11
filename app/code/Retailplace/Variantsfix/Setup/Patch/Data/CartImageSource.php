<?php

/**
 * Retailplace_Variantsfix
 *
 * @copyright   Copyright (c) 2021 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Evgeniy Derevyanko <evgeniy@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\Variantsfix\Setup\Patch\Data;

use Magento\Catalog\Model\Config\Source\Product\Thumbnail;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;

/**
 * Class CartImageSource
 */
class CartImageSource implements DataPatchInterface
{
    /** @var string */
    public const XML_CART_CONFIGURABLE_IMAGES = 'checkout/cart/configurable_product_image';

    /** @var WriterInterface */
    private $configWriter;

    /**
     * CartImageSource constructor.
     *
     * @param WriterInterface $configWriter
     */
    public function __construct(
        WriterInterface $configWriter
    ) {
        $this->configWriter = $configWriter;
    }

    /**
     * Update configuration
     *
     * @return $this
     */
    public function apply(): self
    {
        $this->configWriter->save(self::XML_CART_CONFIGURABLE_IMAGES, Thumbnail::OPTION_USE_OWN_IMAGE);

        return $this;
    }

    /**
     * Get array of patches that have to be executed prior to this.
     *
     * @return string[]
     */
    public static function getDependencies(): array
    {
        return [];
    }

    /**
     * Get aliases (previous names) for the patch.
     *
     * @return string[]
     */
    public function getAliases(): array
    {
        return [];
    }
}
