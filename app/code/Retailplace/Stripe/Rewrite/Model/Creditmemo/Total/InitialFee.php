<?php

declare(strict_types=1);

/**
 * Retailplace_Stripe
 *
 * @copyright   Copyright (c) 2021 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Ruslan Mukhatov <ruslan@tradesquare.com.au>
 */

namespace Retailplace\Stripe\Rewrite\Model\Creditmemo\Total;

use Magento\Sales\Model\Order\Creditmemo;

/**
 * Class InitialFee
 */
class InitialFee extends \StripeIntegration\Payments\Model\Creditmemo\Total\InitialFee
{
    /**
     * @param Creditmemo $creditmemo
     * @return $this
     */
    public function collect(
        Creditmemo $creditmemo
    ) {
        return $this;
    }
}
