<?php

/**
 * Retailplace_Stripe
 *
 * @copyright   Copyright (c) 2022 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Alexander Korsun <aleksandr@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\Stripe\Model;

use Retailplace\Stripe\Api\Data\PaymentCardsInterface;
use Magento\Framework\Api\AbstractSimpleObject;

/**
 * Class PaymentCards
 */
class PaymentCards extends AbstractSimpleObject implements PaymentCardsInterface
{
    /**
     * @return array|null
     */
    public function getCards(): ?array
    {
        return $this->_get(self::CARDS);
    }

    /**
     * @param array|null $cards
     * @return PaymentCardsInterface
     */
    public function setCards(?array $cards): PaymentCardsInterface
    {
        return $this->setData(self::CARDS, $cards);
    }
}
