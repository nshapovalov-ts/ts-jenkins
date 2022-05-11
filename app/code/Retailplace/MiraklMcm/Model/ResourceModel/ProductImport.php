<?php
/**
 * Retailplace_MiraklMcm
 *
 * @copyright   Copyright (c) 2021 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Alexander Korsun <aleksandr@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\MiraklMcm\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use Retailplace\MiraklMcm\Api\Data\ProductImportInterface;

/**
 * ProductImport Class
 */
class ProductImport extends AbstractDb
{
    /**
     * @var string
     */
    const TABLE_NAME = 'retailplace_product_import';

    /**
     * Initialize resource model
     *
     * @return void
     */
    public function _construct()
    {
        $this->_init(self::TABLE_NAME, ProductImportInterface::ID);
    }
}
