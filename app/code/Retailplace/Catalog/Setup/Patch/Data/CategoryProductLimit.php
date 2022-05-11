<?php

/**
 * Retailplace_Catalog
 *
 * @copyright   Copyright (c) 2021 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Evgeniy Derevyanko <evgeniy@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\Catalog\Setup\Patch\Data;

use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;

/**
 * Class CategoryProductLimit
 */
class CategoryProductLimit implements DataPatchInterface
{
    /** @var string */
    public const PRODUCT_PER_PAGE_LIMIT_CONFIG = 'catalog/frontend/grid_per_page';
    public const PRODUCT_PER_PAGE_LIMIT_VALUES_CONFIG = 'catalog/frontend/grid_per_page_values';

    /** @var \Magento\Framework\App\Config\Storage\WriterInterface */
    private $configWriter;

    /**
     * CategoryProductLimit Constructor
     *
     * @param \Magento\Framework\App\Config\Storage\WriterInterface $configWriter
     */
    public function __construct(
        WriterInterface $configWriter
    ) {
        $this->configWriter = $configWriter;
    }

    /**
     * Run code inside patch
     */
    public function apply()
    {
       $this->configWriter->save(self::PRODUCT_PER_PAGE_LIMIT_CONFIG, '48');
       $this->configWriter->save(self::PRODUCT_PER_PAGE_LIMIT_VALUES_CONFIG, '12,24,36,48');
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
