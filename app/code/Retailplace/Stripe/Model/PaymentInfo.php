<?php
declare(strict_types=1);

/**
 * Retailplace_Stripe
 *
 * @copyright   Copyright (c) 2021 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Alexander Korsun <aleksandr@tradesquare.com.au>
 */

namespace Retailplace\Stripe\Model;

use Retailplace\Stripe\Api\Data\PaymentInfoInterface;
use Magento\Framework\Api\AbstractSimpleObject;

/**
 * Object DiscountBreakdownLine.
 */
class PaymentInfo extends AbstractSimpleObject implements PaymentInfoInterface
{
    /**
     * @return string|null
     */
    public function getAvailable(): ?string
    {
        return $this->_get(self::AVAILABLE);
    }

    /**
     * @param string|null $available
     * @return PaymentInfoInterface
     */
    public function setAvailable(?string $available): PaymentInfoInterface
    {
        $this->setData(self::AVAILABLE, $available);
        return $this;
    }

    /**
     * @return string|null
     */
    public function getDuty(): ?string
    {
        return $this->_get(self::DUTY);
    }

    /**
     * @param string|null $duty
     * @return PaymentInfoInterface
     */
    public function setDuty(?string $duty): PaymentInfoInterface
    {
        $this->setData(self::DUTY, $duty);
        return $this;
    }

    /**
     * @return string|null
     */
    public function getTotal(): ?string
    {
        return $this->_get(self::TOTAL);
    }

    /**
     * @param string|null $total
     * @return PaymentInfoInterface
     */
    public function setTotal(?string $total): PaymentInfoInterface
    {
        $this->setData(self::TOTAL, $total);
        return $this;
    }
}
