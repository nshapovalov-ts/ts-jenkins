<?php
/**
 * Retailplace_MiraklMcm
 *
 * @copyright   Copyright (c) 2021 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Alexander Korsun <aleksandr@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\MiraklMcm\Model\Queue\Consumer;

use Retailplace\MiraklMcm\Api\Data\ProductImportMessageInterface;
use Mirakl\Mcm\Model\Product\Import\Handler\Csv as Import;

/**
 * Class Product
 */
class Product
{
    /**
     * @var Import
     */
    private $import;

    /**
     * Consumer Constructor
     *
     * @param Import $import
     */
    public function __construct(
        Import $import
    ) {
        $this->import = $import;
    }

    /**
     * Process
     *
     * @param ProductImportMessageInterface $message
     * @return void
     */
    public function process(ProductImportMessageInterface $message)
    {
        $this->import->importProduct($message);
    }
}
