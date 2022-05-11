<?php
/**
 * Retailplace_MiraklMcm
 *
 * @copyright   Copyright (c) 2021 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Alexander Korsun <aleksandr@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\MiraklMcm\Model\Queue;

use Retailplace\MiraklMcm\Api\Data\ProductImportMessageInterface;
use Magento\Framework\Model\AbstractModel;

/**
 * Class ProductImportMessage
 */
class ProductImportMessage extends AbstractModel implements ProductImportMessageInterface
{

    /**
     * Set Id
     *
     * @param string $id
     * @return ProductImportMessageInterface
     */
    public function setId($id): ProductImportMessageInterface
    {
        return $this->setData(self::ID, $id);
    }

    /**
     * Get Id
     *
     * @return string
     */
    public function getId(): string
    {
        return parent::getData(self::ID);
    }


    /**
     * Set Retry Count
     *
     * @param int $count
     * @return ProductImportMessageInterface
     */
    public function setRetryCount(int $count): ProductImportMessageInterface
    {
        return $this->setData(self::RETRY_COUNT, $count);
    }

    /**
     * Get Retry Count
     *
     * @return int
     */
    public function getRetryCount(): int
    {
        return parent::getData(self::RETRY_COUNT);
    }

}
