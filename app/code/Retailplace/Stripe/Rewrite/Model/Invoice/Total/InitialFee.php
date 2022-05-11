<?php

declare(strict_types=1);

/**
 * Retailplace_Stripe
 *
 * @copyright   Copyright (c) 2021 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Ruslan Mukhatov <ruslan@tradesquare.com.au>
 */

namespace Retailplace\Stripe\Rewrite\Model\Invoice\Total;

use Magento\Sales\Model\Order\Invoice;

/**
 * Class InitialFee
 */
class InitialFee extends \StripeIntegration\Payments\Model\Invoice\Total\InitialFee
{
    /**
     * @param Invoice $invoice
     * @return $this
     */
    public function collect(
        Invoice $invoice
    ) {
        return $this;
    }
}
