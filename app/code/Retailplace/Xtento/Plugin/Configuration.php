<?php

/**
 * Retailplace_Xtento
 *
 * @copyright   Copyright © 2021 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Satish Gumudavelly <satish@vdcstore.com>
 */
declare(strict_types=1);

namespace Retailplace\Xtento\Plugin;

/**
 * Class Configuration
 */
class Configuration
{

    /**
     * @param \Xtento\XtCore\Model\System\Config\Backend\Configuration $subject
     * @param callable $proceed
     * @param $updatedConfiguration
     * @return bool
     */
    public function aroundAfterUpdate(\Xtento\XtCore\Model\System\Config\Backend\Configuration $subject, callable $proceed, $updatedConfiguration)
    {
        return true;
    }
}
