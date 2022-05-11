<?php

/**
 * Retailplace_Stripe
 *
 * @copyright   Copyright (c) 2022 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Alexander Korsun <aleksandr@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\Stripe\Model;

use StripeIntegration\Payments\Helper\Generic;
use Retailplace\Stripe\Api\Data\PaymentCardsInterface;
use Retailplace\Stripe\Api\Data\PaymentCardsInterfaceFactory;
use StripeIntegration\Payments\Model\StripeCustomer;
use Psr\Log\LoggerInterface;
use Magento\Framework\Serialize\Serializer\Json as Serializer;

/**
 * Class PaymentCardsManagement
 */
class PaymentCardsManagement
{

    /**
     * @var Generic
     */
    private $generic;

    /**
     * @var PaymentCardsInterfaceFactory
     */
    private $responseFactory;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var StripeCustomer
     */
    private $stripeCustomer;

    /**
     * @var Serializer
     */
    private $serializer;

    /**
     * @param Generic $generic
     * @param StripeCustomer $stripeCustomer
     * @param PaymentCardsInterfaceFactory $responseFactory
     * @param LoggerInterface $logger
     * @param Serializer $serializer
     */
    public function __construct(
        Generic                      $generic,
        StripeCustomer               $stripeCustomer,
        PaymentCardsInterfaceFactory $responseFactory,
        LoggerInterface              $logger,
        Serializer $serializer
    ) {
        $this->generic = $generic;
        $this->stripeCustomer = $stripeCustomer;
        $this->responseFactory = $responseFactory;
        $this->logger = $logger;
        $this->serializer = $serializer;
    }

    /**
     * Get List Cards
     *
     * @return PaymentCardsInterface
     */
    public function getListCards(): PaymentCardsInterface
    {
        $cards = [];

        $response = $this->responseFactory->create();
        try {
            $customer = $this->generic->getCustomerModel();
            if ($customer) {
                $stripeCustomer = $this->stripeCustomer->retrieveByStripeID($customer->getStripeId());
                if ($stripeCustomer) {
                    $result = $this->generic->listCards($stripeCustomer);
                    if ($result) {
                        foreach ($result as $card) {
                            $cards[] = $this->serializer->serialize($card);
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            $this->logger->critical($e->getMessage());
        }

        $response->setCards($cards);

        return $response;
    }
}
