<?php

/**
 * Retailplace_MiraklQuote
 *
 * @copyright   Copyright (c) 2022 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Dmitriy Fionov <dmitriy@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\MiraklQuote\Plugin\Checkout;

use Closure;
use Magento\Checkout\Api\Data\PaymentDetailsInterface;
use Magento\Checkout\Api\Data\ShippingInformationInterface;
use Magento\Checkout\Model\ShippingInformationManagement;
use Magento\Framework\Exception\StateException;
use Magento\Framework\Message\AbstractMessage;
use Magento\Framework\Phrase;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\ResourceModel\Quote\AddressFactory as QuoteAddressResourceFactory;
use Mirakl\Connector\Helper\Quote as QuoteHelper;
use Mirakl\Connector\Model\Quote\Updater as QuoteUpdater;
use Retailplace\MiraklQuote\Api\Data\MiraklQuoteAttributes;

/**
 * Class ShippingInformationManagementPlugin
 *
 * @see \Mirakl\Connector\Plugin\Model\Checkout\ShippingInformationManagementPlugin
 */
class ShippingInformationManagementPlugin
{
    /** @var \Magento\Quote\Api\CartRepositoryInterface */
    protected $quoteRepository;

    /** @var \Mirakl\Connector\Helper\Quote */
    protected $quoteHelper;

    /** @var \Magento\Quote\Model\ResourceModel\Quote\AddressFactory */
    protected $quoteAddressResourceFactory;

    /** @var \Mirakl\Connector\Model\Quote\Updater */
    protected $quoteUpdater;

    /**
     * Constructor
     *
     * @param \Magento\Quote\Api\CartRepositoryInterface $quoteRepository
     * @param \Mirakl\Connector\Helper\Quote $quoteHelper
     * @param \Magento\Quote\Model\ResourceModel\Quote\AddressFactory $quoteAddressResourceFactory
     * @param \Mirakl\Connector\Model\Quote\Updater $quoteUpdater
     */
    public function __construct(
        CartRepositoryInterface $quoteRepository,
        QuoteHelper $quoteHelper,
        QuoteAddressResourceFactory $quoteAddressResourceFactory,
        QuoteUpdater $quoteUpdater
    ) {
        $this->quoteRepository = $quoteRepository;
        $this->quoteHelper = $quoteHelper;
        $this->quoteAddressResourceFactory = $quoteAddressResourceFactory;
        $this->quoteUpdater = $quoteUpdater;
    }

    /**
     * @param \Magento\Checkout\Model\ShippingInformationManagement $subject
     * @param \Closure $proceed
     * @param int $cartId
     * @param \Magento\Checkout\Api\Data\ShippingInformationInterface $addressInformation
     * @return PaymentDetailsInterface
     * @throws \Magento\Framework\Exception\AlreadyExistsException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\StateException
     */
    public function aroundSaveAddressInformation(
        ShippingInformationManagement $subject,
        Closure $proceed,
        $cartId,
        ShippingInformationInterface $addressInformation
    ) {
        /** @var \Magento\Quote\Model\Quote $quote */
        $quote = $this->quoteRepository->getActive($cartId);

        $shippingAddress = $addressInformation->getShippingAddress();
        if ($shippingAddress && !$quote->getData(MiraklQuoteAttributes::MIRAKL_QUOTE_ID)) {
            $quote->setShippingAddress($shippingAddress);
            if ($extAttributes = $shippingAddress->getExtensionAttributes()) {
                parse_str($extAttributes->getAdditionalMethods(), $additionalMethods);
                if (isset($additionalMethods['shipping_method'])) {
                    $this->quoteUpdater->updateOffersShippingTypes($additionalMethods['shipping_method'], $quote);
                }
            }
        }

        $shippingAddress = $quote->getShippingAddress();
        if ($shippingAddress && !$shippingAddress->getShippingMethod()) {
            $shippingCarrierCode = $addressInformation->getShippingCarrierCode();
            $shippingMethodCode = $addressInformation->getShippingMethodCode();
            if (strlen($shippingCarrierCode) && strlen($shippingMethodCode)) {
                $shippingAddress->setShippingMethod($shippingCarrierCode . '_' . $shippingMethodCode);
                $this->quoteAddressResourceFactory->create()->save($shippingAddress);
            }
        }

        // Default Magento process
        $paymentDetails = $proceed($cartId, $addressInformation);

        if ($this->quoteHelper->isMiraklQuote($quote)) {
            // Verify that SH02 is still valid
            $this->quoteUpdater->synchronize($quote);

            if ($quote->getHasError()) {
                // Throw an exception if quote has been flagged as error
                $messages = $quote->getMessages();
                if (count($messages)) {
                    $message = current($messages);
                    if ($message instanceof AbstractMessage) {
                        $message = $message->getText();
                    }

                    throw new StateException(new Phrase((string) $message));
                }
            }
        }

        return $paymentDetails;
    }
}
