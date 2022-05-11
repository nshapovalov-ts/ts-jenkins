<?php
declare(strict_types=1);

/**
 * Retailplace_Stripe
 *
 * @copyright   Copyright (c) 2021 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Alexander Korsun <aleksandr@tradesquare.com.au>
 */

namespace Retailplace\Stripe\Cron;

use Retailplace\Stripe\Model\Processing;

/**
 * Class PayInvoices
 */
class PayInvoices
{

    /**
     * @var Processing
     */
    private $processing;

    /**
     * PayInvoices constructor.
     *
     * @param Processing $processing
     */
    public function __construct(
        Processing $processing
    ) {
        $this->processing = $processing;
    }

    /**
     * Execute
     */
    public function execute()
    {
        $this->processing->payInvoices([]);
    }
}
