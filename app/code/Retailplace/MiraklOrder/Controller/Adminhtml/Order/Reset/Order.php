<?php

/**
 * Retailplace_MiraklOrder
 *
 * @copyright   Copyright (c) 2022 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Nikolay Shapovalov <nikolay@tradesquare.com.au>
 */

declare(strict_types=1);


namespace Retailplace\MiraklOrder\Controller\Adminhtml\Order\Reset;

use Mirakl\Connector\Controller\Adminhtml\AbstractReset;

/**
 * Class Order implements controller for reset order's sync date
 */
class Order extends AbstractReset
{
    /**
     * Resets last synchronization date of shops
     */
    public function execute()
    {
        $this->connectorConfig->resetSyncDate('orders');
        $this->messageManager->addSuccessMessage(__('Last orders synchronization date has been reset successfully.'));
        return $this->redirectReferer();
    }
}
