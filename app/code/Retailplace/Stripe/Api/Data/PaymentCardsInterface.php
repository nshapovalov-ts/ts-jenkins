<?php

/**
 * Retailplace_Stripe
 *
 * @copyright   Copyright (c) 2022 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Alexander Korsun <aleksandr@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\Stripe\Api\Data;

use Magento\Framework\Api\ExtensibleDataInterface;

/**
 * Interface PaymentCardsInterface
 */
interface PaymentCardsInterface extends ExtensibleDataInterface
{
    /**
     * @var string
     */
    const CARDS = 'cards';

    /**
     * @return array|null
     */
    public function getCards(): ?array;

    /**
     * @param array|null $cards
     * @return PaymentCardsInterface
     */
    public function setCards(?array $cards): PaymentCardsInterface;

}
