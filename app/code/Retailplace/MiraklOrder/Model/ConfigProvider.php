<?php

/**
 * Retailplace_MiraklOrder
 *
 * @copyright   Copyright (c) 2022 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Nikolay Shapovalov <nikolay@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\MiraklOrder\Model;

use Mirakl\Connector\Helper\Config;

/**
 * Class ConfigProvider implements config provider for MiraklOrder module
 */
class ConfigProvider extends Config
{
    /** @var string */
    public const XML_PATH_ORDER_SYNC_ENABLED = 'mirakl_sync/orders/enable_import';

    /**
     * @return bool
     */
    public function isMiraklOrderSyncEnable(): bool
    {
        return (bool) $this->getValue(self::XML_PATH_ORDER_SYNC_ENABLED);
    }
}
