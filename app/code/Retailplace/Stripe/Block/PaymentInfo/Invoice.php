<?php

declare(strict_types=1);

/**
 * Retailplace_Stripe
 *
 * @copyright   Copyright (c) 2021 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Evgeniy Derevyanko <evgeniy@tradesquare.com.au>
 */

namespace Retailplace\Stripe\Block\PaymentInfo;

use Exception;
use Magento\Directory\Model\Country;
use Magento\Framework\Registry;
use Magento\Framework\View\Element\Template\Context;
use Magento\Payment\Gateway\ConfigInterface;
use Magento\Payment\Model\Info;
use StripeIntegration\Payments\Block\PaymentInfo\Invoice as InvoiceCore;
use Magento\Sales\Api\OrderPaymentRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Sales\Api\OrderRepositoryInterface;
use StripeIntegration\Payments\Helper\Api;
use StripeIntegration\Payments\Helper\Generic;
use StripeIntegration\Payments\Model\Config;
use StripeIntegration\Payments\Model\Stripe\CustomerFactory;
use StripeIntegration\Payments\Model\Stripe\InvoiceFactory;
use Magento\Sales\Api\Data\OrderPaymentInterface;

/**
 * Class Invoice
 */
class Invoice extends InvoiceCore
{
    /** @var OrderPaymentRepositoryInterface */
    private $paymentRepository;

    /** @var OrderRepositoryInterface */
    private $orderRepository;

    /** @var SearchCriteriaBuilder */
    private $searchCriteriaBuilder;

    /**
     * Invoice constructor.
     *
     * @param Context $context
     * @param ConfigInterface $config
     * @param Generic $helper
     * @param Config $paymentsConfig
     * @param InvoiceFactory $invoiceFactory
     * @param CustomerFactory $customerFactory
     * @param Api $api
     * @param Country $country
     * @param Info $info
     * @param Registry $registry
     * @param OrderPaymentRepositoryInterface $paymentRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param OrderRepositoryInterface $orderRepository
     * @param array $data
     */
    public function __construct(
        Context $context,
        ConfigInterface $config,
        Generic $helper,
        Config $paymentsConfig,
        InvoiceFactory $invoiceFactory,
        CustomerFactory $customerFactory,
        Api $api,
        Country $country,
        Info $info,
        Registry $registry,
        OrderPaymentRepositoryInterface $paymentRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        OrderRepositoryInterface $orderRepository,
        array $data = []
    ) {
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->paymentRepository = $paymentRepository;
        $this->orderRepository = $orderRepository;
        parent::__construct($context, $config, $helper, $paymentsConfig, $invoiceFactory, $customerFactory, $api, $country, $info, $registry, $data);
    }

    /**
     * Returns payment date
     *
     * @param string|null $format
     * @return string
     */
    public function getPaymentDate($format = null): string
    {
        $date = null;
        try {
            $invoice = $this->getInvoice();
            if ($invoice) {
                $lastTransId = $invoice->getId();
                $searchCriteria = $this->searchCriteriaBuilder->addFilter(OrderPaymentInterface::LAST_TRANS_ID, $lastTransId)->create();
                $payments = $this->paymentRepository->getList($searchCriteria)->getItems();
                foreach ($payments as $payment) {
                    $order = $payment->getOrder();
                    $date = $order->getPaymentDate() ?? $order->getCreatedAt();
                }
            }
        } catch (Exception $e) {
            $this->_logger->warning($e->getMessage());
        }

        return $this->paymentsConfig->getPaymentDate($format, $date);
    }

    /**
     * Returns payment method title
     *
     * @return string
     */
    public function getMethodTitle(): string
    {
        return $this->getMethod()->getShortTitle();
    }
}
