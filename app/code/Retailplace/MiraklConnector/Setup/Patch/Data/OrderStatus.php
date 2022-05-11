<?php

/**
 * Retailplace_MiraklConnector
 *
 * @copyright   Copyright (c) 2021 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Dmitriy Fionov <dmitriy@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\MiraklConnector\Setup\Patch\Data;

use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\App\Config\Storage\WriterInterface as ConfigWriter;

/**
 * Class OrderStatus
 */
class OrderStatus implements DataPatchInterface
{
    /** @var string */
    public const XML_PATH_MIRAKL_CONNECTOR_TRIGGER_ORDER_STATUSES = 'mirakl_connector/order_workflow/auto_create_order_statuses';

    /** @var \Magento\Framework\App\Config\Storage\WriterInterface */
    private $configWriter;

    /**
     * OrderStatus constructor
     *
     * @param \Magento\Framework\App\Config\Storage\WriterInterface $configWriter
     */
    public function __construct(ConfigWriter $configWriter)
    {
        $this->configWriter = $configWriter;
    }

    /**
     * Apply patch
     */
    public function apply()
    {
        $this->configWriter->save(
            self::XML_PATH_MIRAKL_CONNECTOR_TRIGGER_ORDER_STATUSES,
            'processing,pending'
        );
    }

    /**
     * Get dependencies
     *
     * @return string[]
     */
    public static function getDependencies(): array
    {
        return [];
    }

    /**
     * Get aliases
     *
     * @return string[]
     */
    public function getAliases(): array
    {
        return [];
    }
}
