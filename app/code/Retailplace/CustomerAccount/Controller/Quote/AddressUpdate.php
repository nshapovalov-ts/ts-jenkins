<?php

/**
 * Retailplace_CustomerAccount
 *
 * @copyright   Copyright (c) 2021 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Dmitriy Fionov <dmitriy@tradesquare.com.au>
 * @author      Evgeniy Derevyanko <evgeniy@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\CustomerAccount\Controller\Quote;

use Exception;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Customer\Api\AddressRepositoryInterface;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Quote\Model\ResourceModel\Quote;
use Magento\Quote\Model\ResourceModel\Quote\Address;
use Psr\Log\LoggerInterface;
use Retailplace\OneSellerCheckout\Api\Data\OneSellerQuoteAttributes;
use Retailplace\MultiQuote\Model\QuoteHandlers;

/**
 * Address Update
 */
class AddressUpdate extends Action
{
    /** @var \Magento\Customer\Model\Session */
    private $customerSession;

    /** @var \Magento\Checkout\Model\Session */
    private $checkoutSession;

    /** @var \Magento\Customer\Api\AddressRepositoryInterface */
    private $addressRepository;

    /** @var \Magento\Quote\Model\ResourceModel\Quote\Address */
    private $quoteAddressResourceModel;

    /** @var \Retailplace\MultiQuote\Model\QuoteHandlers */
    private $quoteHandlers;

    /** @var \Retailplace\MultiQuote\Model\QuoteResource */
    private $quoteResourceModel;

    /** @var \Psr\Log\LoggerInterface */
    private $logger;

    /**
     * Constructor
     *
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Magento\Customer\Api\AddressRepositoryInterface $addressRepository
     * @param \Magento\Quote\Model\ResourceModel\Quote\Address $quoteAddressResourceModel
     * @param \Retailplace\MultiQuote\Model\QuoteHandlers $quoteHandlers
     * @param \Magento\Quote\Model\ResourceModel\Quote $quoteResourceModel
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        Context $context,
        CustomerSession $customerSession,
        CheckoutSession $checkoutSession,
        AddressRepositoryInterface $addressRepository,
        Address $quoteAddressResourceModel,
        QuoteHandlers $quoteHandlers,
        Quote $quoteResourceModel,
        LoggerInterface $logger
    ) {
        parent::__construct($context);

        $this->customerSession = $customerSession;
        $this->checkoutSession = $checkoutSession;
        $this->addressRepository = $addressRepository;
        $this->quoteAddressResourceModel = $quoteAddressResourceModel;
        $this->quoteHandlers = $quoteHandlers;
        $this->quoteResourceModel = $quoteResourceModel;
        $this->logger = $logger;
    }

    /**
     * Execute Controller
     *
     * @return \Magento\Framework\Controller\Result\Redirect
     */
    public function execute()
    {
        $postcode = $this->getRequest()->getParam('zip-postcode');
        if ($postcode) {
            try {
                $this->updateQuoteAddress($postcode);
                $this->updateCustomerAddress($postcode);
                $this->updateRelatedQuotes($postcode);
            } catch (Exception $e) {
                $this->logger->error($e->getMessage());
            }
        }

        $resultRedirect = $this->resultRedirectFactory->create();
        $resultRedirect->setPath('checkout/cart');
        if ($this->getRequest()->isAjax()) {
            $resultRedirect = null;
        }

        return $resultRedirect;
    }

    /**
     * Update Postcode in Quote Address
     *
     * @param string $postcode
     * @throws \Magento\Framework\Exception\AlreadyExistsException
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function updateQuoteAddress(string $postcode)
    {
        $quote = $this->checkoutSession->getQuote();
        $shippingAddress = $quote->getShippingAddress();
        $shippingAddress->setPostcode($postcode);
        $this->quoteAddressResourceModel->save($shippingAddress);
    }

    /**
     * Update Postcode in Customer Addresses
     *
     * @param string $postcode
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function updateCustomerAddress(string $postcode)
    {
        $customer = $this->customerSession->getCustomer();
        $billingAddress = $this->addressRepository->getById($customer->getDefaultBillingAddress()->getId());
        $shippingAddress = $this->addressRepository->getById($customer->getDefaultShippingAddress()->getId());
        $billingAddress->setPostcode($postcode);
        $shippingAddress->setPostcode($postcode);
        $this->addressRepository->save($billingAddress);
        $this->addressRepository->save($shippingAddress);

    }


    /**
     * Update Related Quotes
     *
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\AlreadyExistsException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function updateRelatedQuotes(string $postcode)
    {
        $quote = $this->checkoutSession->getQuote();
        $parentId = $quote->getData(OneSellerQuoteAttributes::QUOTE_PARENT_QUOTE_ID);
        if ($parentId) {
            $parentQuote = $this->quoteHandlers->loadQuoteById($parentId);
            $shippingAddress = $parentQuote->getShippingAddress();
            $shippingAddress->setPostcode($postcode);
            $this->quoteAddressResourceModel->save($shippingAddress);
            $this->quoteResourceModel->removeChildQuotes($parentQuote->getId(), $quote->getId());
        } else {
            $this->quoteResourceModel->removeChildQuotes($quote->getId());
        }
    }
}
