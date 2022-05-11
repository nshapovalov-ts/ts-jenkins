<?php
declare(strict_types=1);

/**
 * Retailplace_Stripe
 *
 * @copyright   Copyright (c) 2021 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Alexander Korsun <aleksandr@tradesquare.com.au>
 */

namespace Retailplace\Stripe\Api\Data;

use Magento\Framework\Api\ExtensibleDataInterface;

interface PaymentInfoInterface extends ExtensibleDataInterface
{
    /**
     * @var string
     */
    const AVAILABLE = 'available';

    /**
     * @var string
     */
    const DUTY = 'duty';

    /**
     * @var string
     */
    const TOTAL = 'total';

    /**
     * @return string|null
     */
    public function getAvailable(): ?string;

    /**
     * @param string|null $available
     * @return $this
     */
    public function setAvailable(?string $available): PaymentInfoInterface;

    /**
     * @return string|null
     */
    public function getDuty(): ?string;

    /**
     * @param string|null $duty
     * @return $this
     */
    public function setDuty(?string $duty): PaymentInfoInterface;

    /**
     * @return string|null
     */
    public function getTotal(): ?string;

    /**
     * @param string|null $total
     * @return $this
     */
    public function setTotal(?string $total): PaymentInfoInterface;
}
