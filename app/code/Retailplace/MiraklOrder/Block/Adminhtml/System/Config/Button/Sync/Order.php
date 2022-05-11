<?php

/**
 * Retailplace_MiraklOrder
 *
 * @copyright   Copyright (c) 2022 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Nikolay Shapovalov <nikolay@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\MiraklOrder\Block\Adminhtml\System\Config\Button\Sync;

use Mirakl\Connector\Block\Adminhtml\System\Config\Button\AbstractButtons;

/**
 * Class Order implements buttons for import and reset import date
 */
class Order extends AbstractButtons
{
    /** @var array */
    protected $buttonsConfig = [
        [
            'label' => 'Import in Magento',
            'url' => 'retailplace_mirakl_order/order/sync_order/',
            'confirm' => 'Are you sure? This will update all modified orders since the last synchronization.',
            'class' => 'scalable',
        ],
        [
            'label' => 'Reset Date',
            'url' => 'retailplace_mirakl_order/order/reset_order/',
            'confirm' => 'Are you sure? This will reset the last synchronization date.',
            'class' => 'scalable primary',
        ],
    ];
}
