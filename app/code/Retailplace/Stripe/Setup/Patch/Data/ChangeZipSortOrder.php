<?php

/**
 * Retailplace_Stripe
 *
 * @copyright   Copyright (c) 2021 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Evgeniy Derevyanko <evgeniy@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\Stripe\Setup\Patch\Data;

use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;

/**
 * Class ChangeZipSortOrder
 */
class ChangeZipSortOrder implements DataPatchInterface
{
    /** @var WriterInterface */
    private $configWriter;

    /**
     * ChangeZipSortOrder constructor.
     *
     * @param WriterInterface $configWriter
     */
    public function __construct(
        WriterInterface $configWriter
    ) {
        $this->configWriter = $configWriter;
    }

    /**
     * Apply Patch
     */
    public function apply()
    {
        $this->configWriter->save('payment/zipmoneypayment/sort_order', 100);
    }

    /**
     * Get array of patches that have to be executed prior to this
     *
     * @return string[]
     */
    public static function getDependencies(): array
    {
        return [];
    }

    /**
     * Get aliases (previous names) for the patch
     *
     * @return string[]
     */
    public function getAliases(): array
    {
        return [];
    }
}
