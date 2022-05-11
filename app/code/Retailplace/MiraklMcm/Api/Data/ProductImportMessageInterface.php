<?php
/**
 * Retailplace_MiraklMcm
 *
 * @copyright   Copyright (c) 2021 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Alexander Korsun <aleksandr@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\MiraklMcm\Api\Data;

/**
 * Interface ProductImportMessageInterface
 */
interface ProductImportMessageInterface
{
    /**
     * @var string
     */
    const ID = 'id';
    const RETRY_COUNT = 'retry_count';

    /**
     * Set Id
     *
     * @param string $id
     * @return ProductImportMessageInterface
     */
    public function setId(string $id): ProductImportMessageInterface;

    /**
     * Get Id
     *
     * @return string
     */
    public function getId(): string;

    /**
     * Set Retry Count
     *
     * @param int $count
     * @return ProductImportMessageInterface
     */
    public function setRetryCount(int $count): ProductImportMessageInterface;

    /**
     * Get Retry Count
     *
     * @return int
     */
    public function getRetryCount(): int;
}
