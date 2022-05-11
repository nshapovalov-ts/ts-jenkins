<?php
declare(strict_types=1);

/**
 * Retailplace_Stripe
 *
 * @copyright   Copyright (c) 2021 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Alexander Korsun <aleksandr@tradesquare.com.au>
 * @author      Ruslan Mukhatov <ruslan@tradesquare.com.au>
 */

namespace Retailplace\Stripe\Ui\Component\Listing\Column\Invoice\Status;

use Magento\Framework\Data\OptionSourceInterface;
use Retailplace\Stripe\Rewrite\Model\Method\Invoice;

/**
 * Class Options
 */
class Options implements OptionSourceInterface
{
    /** @var string */
    const STRIPE_INVOICE_NOT_PAID_LABEL = 'Pending payment';
    const STRIPE_INVOICE_PAID_LABEL = 'Paid';
    const STRIPE_INVOICE_NOT_PAID_ERROR_LABEL = 'Payment failed';
    const STRIPE_INVOICE_PAID_NOT_APPLICABLE_LABEL = 'Not Applicable';

    /**
     * @var array
     */
    protected $options;

    /**
     * Get options
     *
     * @return array
     */
    public function toOptionArray()
    {
        if ($this->options === null) {
            $this->options = [
                [
                    'value' => Invoice::STRIPE_INVOICE_NOT_PAID,
                    'label' => self::STRIPE_INVOICE_NOT_PAID_LABEL
                ],
                [
                    'value' => Invoice::STRIPE_INVOICE_PAID,
                    'label' => self::STRIPE_INVOICE_PAID_LABEL
                ],
                [
                    'value' => Invoice::STRIPE_INVOICE_NOT_PAID_ERROR,
                    'label' => self::STRIPE_INVOICE_NOT_PAID_ERROR_LABEL
                ],
                [
                    'value' => Invoice::STRIPE_INVOICE_PAID_NOT_APPLICABLE,
                    'label' => self::STRIPE_INVOICE_PAID_NOT_APPLICABLE_LABEL
                ],
            ];
        }

        return $this->options;
    }
}
