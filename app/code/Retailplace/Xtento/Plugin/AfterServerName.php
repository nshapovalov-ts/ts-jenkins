<?php

/**
 * Retailplace_Xtento
 *
 * @copyright   Copyright © 2021 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Satish Gumudavelly <satish@vdcstore.com>
 */
declare(strict_types=1);

namespace Retailplace\Xtento\Plugin;

use Xtento\XtCore\Helper\Server;

/**
 * Class AfterServerName
 */
class AfterServerName
{
    const TRADESQUARE_COM_AU = "tradesquare.com.au";

    /**
     * @param Server $subject
     * @param $result
     */
    public function afterGetFirstName(Server $subject, $result): string
    {
        return self::TRADESQUARE_COM_AU;
    }

    /**
     * @param Server $subject
     * @param $result
     */
    public function afterGetSecondName(Server $subject, $result): string
    {
        return self::TRADESQUARE_COM_AU;
    }

    /**
     * @param Server $subject
     * @param callable $proceed
     * @param array $configuration
     * @param false $updateConfiguration
     * @return bool
     */
    public function aroundConfirm(Server $subject, callable $proceed, array $configuration, $updateConfiguration = false): bool
    {
        return true;
    }
}
