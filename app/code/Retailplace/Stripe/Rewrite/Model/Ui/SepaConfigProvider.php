<?php

declare(strict_types=1);

/**
 * Retailplace_Stripe
 *
 * @copyright   Copyright (c) 2021 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Ruslan Mukhatov <ruslan@tradesquare.com.au>
 */

namespace Retailplace\Stripe\Rewrite\Model\Ui;

/**
 * Class SepaConfigProvider
 */
class SepaConfigProvider extends \StripeIntegration\Payments\Model\Ui\SepaConfigProvider
{
    /**
     * @return array
     */
    public function getConfig()
    {
        return [];
    }
}
