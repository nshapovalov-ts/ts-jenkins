<?php
/**
 * Retailplace_Stripe
 *
 * @copyright   Copyright (c) 2022 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Alexander Korsun <aleksandr@tradesquare.com.au>
 * @author      Ruslan Mukhatov <ruslan@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\Stripe\Model;

use Magento\Sales\Api\Data\OrderInterface;
use Psr\Log\LoggerInterface;
use StripeIntegration\Payments\Model\Config;
use Magento\Framework\Api\Search\FilterGroupBuilder;
use Magento\Framework\Api\FilterBuilder;
use Magento\Sales\Api\Data\OrderSearchResultInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Exception;
use Retailplace\Stripe\Rewrite\Model\Method\Invoice;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilderFactory;
use Magento\Customer\Model\ResourceModel\CustomerFactory;
use Magento\Framework\DataObjectFactory as ObjectFactory;
use Magento\Sales\Api\OrderPaymentRepositoryInterface;
use Stripe\PaymentMethod;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Output\ConsoleOutputFactory;
use Magento\Framework\DB\Adapter\Pdo\Mysql;
use Magento\Framework\Stdlib\DateTime\DateTimeFactory;

/**
 * Class Processing
 */
class Processing
{
    /**
     * @var string
     */
    const ATTRIBUTE_CODE_MAX_CREDIT_LIMIT = 'max_credit_limit';

    /**
     * @var Config
     */
    private $config;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var OrderRepository
     */
    private $orderRepository;

    /**
     * @var FilterGroupBuilder
     */
    private $filterGroupBuilder;

    /**
     * @var FilterBuilder
     */
    private $filterBuilder;

    /**
     * @var TimezoneInterface
     */
    private $timezone;

    /**
     * @var SearchCriteriaBuilderFactory
     */
    private $searchCriteriaBuilderFactory;

    /**
     * @var CustomerRepositoryInterface
     */
    private $customerRepository;

    /**
     * @var CustomerFactory
     */
    private $customerFactory;

    /**
     * @var ObjectFactory
     */
    private $objectFactory;

    /**
     * @var OrderPaymentRepositoryInterface
     */
    private $paymentRepository;

    /**
     * @var OutputInterface
     */
    private $output;

    /**
     * @var ConsoleOutputFactory
     */
    private $outputFactory;

    /**
     * @var DateTimeFactory
     */
    private $dateTimeFactory;

    /**
     * Processing constructor.
     *
     * @param LoggerInterface $logger
     * @param OrderRepository $orderRepository
     * @param FilterGroupBuilder $filterGroupBuilder
     * @param FilterBuilder $filterBuilder
     * @param Config $config
     * @param TimezoneInterface $timezone
     * @param CustomerRepositoryInterface $customerRepository
     * @param SearchCriteriaBuilderFactory $searchCriteriaBuilderFactory
     * @param CustomerFactory $customerFactory
     * @param ObjectFactory $objectFactory
     * @param OrderPaymentRepositoryInterface $paymentRepository
     * @param ConsoleOutputFactory $outputFactory
     * @param DateTimeFactory $dateTimeFactory
     */
    public function __construct(
        LoggerInterface $logger,
        OrderRepository $orderRepository,
        FilterGroupBuilder $filterGroupBuilder,
        FilterBuilder $filterBuilder,
        Config $config,
        TimezoneInterface $timezone,
        CustomerRepositoryInterface $customerRepository,
        SearchCriteriaBuilderFactory $searchCriteriaBuilderFactory,
        CustomerFactory $customerFactory,
        ObjectFactory $objectFactory,
        OrderPaymentRepositoryInterface $paymentRepository,
        ConsoleOutputFactory $outputFactory,
        DateTimeFactory $dateTimeFactory
    ) {
        $this->logger = $logger;
        $this->orderRepository = $orderRepository;
        $this->filterGroupBuilder = $filterGroupBuilder;
        $this->filterBuilder = $filterBuilder;
        $this->config = $config;
        $this->timezone = $timezone;
        $this->customerRepository = $customerRepository;
        $this->searchCriteriaBuilderFactory = $searchCriteriaBuilderFactory;
        $this->customerFactory = $customerFactory;
        $this->objectFactory = $objectFactory;
        $this->paymentRepository = $paymentRepository;
        $this->outputFactory = $outputFactory;
        $this->dateTimeFactory = $dateTimeFactory;
    }

    /**
     * Pay Invoices
     *
     * @param array $params
     */
    public function payInvoices(array $params = [])
    {
        $orders = $this->getAllUnpaidOrder($params);

        foreach ($orders->getItems() as $order) {
            $this->logger->info("Start Pay Invoice on Order#
            {$order->getIncrementId()} - Creation Date: {$order->getCreatedAt()}");

            try {
                $payment = $order->getPayment();
                $invoiceId = $payment->getAdditionalInformation('invoice_id');
                if (empty($invoiceId)) {
                    throw new LocalizedException(__(
                        "This invoice for order cannot do paid. Invoice id is empty."
                    ));
                }

                $this->config->getStripeClient()->invoices->pay($invoiceId, [
                    'forgive'     => true,
                    'off_session' => false
                ]);
            } catch (Exception | LocalizedException $e) {
                $this->logger->error($e->getMessage());
            }
        }
    }

    /**
     * Get All Unpaid Order
     *
     * @param array $params
     * @return OrderSearchResultInterface
     */
    public function getAllUnpaidOrder(array $params = []): OrderSearchResultInterface
    {
        $filters = [];

        $filters[] = $this->filterBuilder->setField('invoice.stripe_invoice_paid')
            ->setConditionType('neq')
            ->setValue(Invoice::STRIPE_INVOICE_PAID)
            ->create();

        $filters[] = $this->filterBuilder->setField('invoice.stripe_invoice_id')
            ->setConditionType('neq')
            ->setValue('NULL')
            ->create();

        if (empty($params['force'])) {
            $filters[] = $this->filterBuilder->setField('payment_date')
                ->setConditionType('lteq')
                ->setValue($this->timezone->date()->format(\Retailplace\Stripe\Rewrite\Model\Config::DATE_FORMAT))
                ->create();
        }

        if (!empty($params['filters']['order_id'])) {
            $filters[] = $this->filterBuilder->setField('entity_id')
                ->setConditionType('eq')
                ->setValue($params['filters']['order_id'])
                ->create();
        }

        $filterGroups = [];

        foreach ($filters as $filter) {
            $filterGroups[] = $this->filterGroupBuilder
                ->addFilter($filter)
                ->create();
        }

        // create search criteria
        $searchCriteria = $this->searchCriteriaBuilderFactory->create()
            ->setFilterGroups($filterGroups)
            ->create();

        return $this->orderRepository->getList($searchCriteria);
    }

    /**
     * Calculate Customer Available Credit Limit
     *
     * @param CustomerInterface $customer
     * @return array
     */
    public function calculateCustomerAvailableCreditLimit(CustomerInterface $customer): array
    {
        $result = [];

        try {
            $maxGrandTotal = $this->config->getCustomerCreditLimit($customer);

            $filters = [];

            $filters[] = $this->filterBuilder->setField(OrderInterface::CUSTOMER_ID)
                ->setConditionType('eq')
                ->setValue($customer->getId())
                ->create();

            $filters[] = $this->filterBuilder->setField('payment.method')
                ->setConditionType('eq')
                ->setValue(Invoice::METHOD_CODE)
                ->create();

            $filters[] = $this->filterBuilder->setField('invoice.stripe_invoice_paid')
                ->setConditionType('neq')
                ->setValue(Invoice::STRIPE_INVOICE_PAID)
                ->create();

            $filters[] = $this->filterBuilder->setField('invoice.stripe_invoice_id')
                ->setConditionType('neq')
                ->setValue('NULL')
                ->create();

            $filterGroups = [];

            foreach ($filters as $filter) {
                $filterGroups[] = $this->filterGroupBuilder
                    ->addFilter($filter)
                    ->create();
            }

            // create search criteria
            $searchCriteria = $this->searchCriteriaBuilderFactory->create()
                ->setFilterGroups($filterGroups)
                ->create();

            $orders = $this->orderRepository->getList($searchCriteria);

            $totalDuty = 0;
            foreach ($orders->getItems() as $order) {
                $totalDuty += $order->getBaseTotalPaid() - $order->getBaseTotalRefunded();
            }

            $available = $maxGrandTotal;

            if (!empty($totalDuty)) {
                if ($available >= $totalDuty) {
                    $available -= $totalDuty;
                } else {
                    $available = 0;
                }
            }

            $result = [
                'total'     => $maxGrandTotal,
                'available' => $available,
                'duty'      => $totalDuty
            ];
        } catch (Exception | LocalizedException $e) {
            $this->logger->error($e->getMessage());
        }

        return $result;
    }

    /**
     * Update Customers Credit Limits
     *
     * @param string|int|null $customerGroupId
     * @throws Exception
     */
    public function updateCustomersCreditLimits($customerGroupId = null)
    {
        $creditLimits = $this->config->getMaxCreditLimit();

        foreach ($creditLimits as $creditLimit) {
            if (!empty($creditLimit['id'])) {
                $groupId = $creditLimit['id'];
                if (!empty($customerGroupId) && $groupId != $customerGroupId) {
                    continue;
                }

                $newLimit = !empty($creditLimit['value']) ? $creditLimit['value']
                    : $this->config->getInvoicingMaxGrandTotal();

                $this->updateCustomerLimit($groupId, $newLimit);
            }
        }
    }

    /**
     * Update Customer Limit
     *
     * @param $groupId
     * @param $limit
     * @throws Exception
     */
    public function updateCustomerLimit($groupId, $limit)
    {
        $customers = $this->getCustomersListForUpdatingCreditLimit($groupId, $limit);
        $customerResource = $this->customerFactory->create();
        $customerData = $this->objectFactory->create();
        foreach ($customers as $customer) {
            $customerData->setData(['id' => $customer->getId(), self::ATTRIBUTE_CODE_MAX_CREDIT_LIMIT => $limit]);
            $customerResource->saveAttribute($customerData, self::ATTRIBUTE_CODE_MAX_CREDIT_LIMIT);
            echo "update customer id : " . $customer->getId() . "\r\n";
        }
    }

    /**
     * Get Customers List For Updating Credit Limit
     *
     * @param $groupId
     * @param $limit
     * @return array|null
     */
    public function getCustomersListForUpdatingCreditLimit($groupId, $limit): ?array
    {
        $customers = [];

        $filterGroups = [];

        $filterGroups[] = $this->filterGroupBuilder
            ->addFilter($this->filterBuilder->setField(CustomerInterface::GROUP_ID)
                ->setConditionType('eq')
                ->setValue($groupId)
                ->create())
            ->create();

        $filterGroups[] = $this->filterGroupBuilder
            ->addFilter($this->filterBuilder->setField(self::ATTRIBUTE_CODE_MAX_CREDIT_LIMIT)
                ->setConditionType('null')
                ->create())
            ->addFilter($this->filterBuilder->setField(self::ATTRIBUTE_CODE_MAX_CREDIT_LIMIT)
                ->setConditionType('neq')
                ->setValue($limit)
                ->create())
            ->create();

        $searchCriteria = $this->searchCriteriaBuilderFactory->create()
            ->setFilterGroups($filterGroups)
            ->create();

        try {
            $customersList = $this->customerRepository->getList($searchCriteria);
            $customers = $customersList->getItems();
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
        }

        return $customers;
    }

    /**
     * Update Credit Card Info
     *
     * @param bool
     */
    public function updateCreditCardInfo($isPrintInfo = false)
    {
        $filters = [];

        $filters[] = $this->filterBuilder->setField('method')
            ->setConditionType('eq')
            ->setValue(Invoice::METHOD_CODE)
            ->create();

        $filters[] = $this->filterBuilder->setField('cc_last_4')
            ->setConditionType('null')
            ->create();

        $filterGroups = [];

        foreach ($filters as $filter) {
            $filterGroups[] = $this->filterGroupBuilder
                ->addFilter($filter)
                ->create();
        }

        $searchCriteria = $this->searchCriteriaBuilderFactory->create()
            ->setFilterGroups($filterGroups)
            ->create();

        $payments = $this->paymentRepository->getList($searchCriteria);

        foreach ($payments->getItems() as $payment) {
            try {
                $token = $payment->getAdditionalInformation('token');
                $paymentMethod = PaymentMethod::retrieve($token);
                if ($paymentMethod) {
                    $card = $paymentMethod->card;
                    $payment->setCcExpMonth($card->exp_month);
                    $payment->setCcExpYear($card->exp_year);
                    $payment->setCcLast4($card->last4);
                    $payment->setCcType($card->brand);
                    $this->paymentRepository->save($payment);
                    if ($isPrintInfo) {
                        $message = __('Payment id: %1 - OK', $payment->getEntityId());
                        $this->getOutput()->writeln("<info>$message</info>");
                    }
                }
            } catch (Exception | LocalizedException $e) {
                $this->logger->error($e->getMessage());
                if ($isPrintInfo) {
                    $message = __('Payment id: %1 - ERROR (%2)', $payment->getEntityId(), $e->getMessage());
                    $this->getOutput()->writeln("<info>$message</info>");
                }
            }
        }
    }

    /**
     * @param string $postcode
     * @param string $phone
     * @param string $expMonth
     * @param string $expYear
     * @param string $last4
     * @param array $excludeCustomers
     * @return bool
     *
     * If a customer is using the same (postcode OR phone number)
     * AND the same credit card in net30 or net60 (last 4 digits and expiry date),
     * that was used in some previous order for the last 6 months, we should block the payment.
     */
    public function checkCreditCardForDuplicates(
        string $postcode,
        string $phone,
        string $expMonth,
        string $expYear,
        string $last4,
        array $excludeCustomers = []
    ): bool {

        try {
            $days = $this->config->getDaysCountForCheckCreditCard();

            $filters = [];
            $filterGroups = [];

            $filters[] = $this->filterBuilder->setField('payment.method')
                ->setConditionType('eq')
                ->setValue(Invoice::METHOD_CODE)
                ->create();

            $filters[] = $this->filterBuilder->setField('payment.cc_last_4')
                ->setConditionType('eq')
                ->setValue($last4)
                ->create();

            $filters[] = $this->filterBuilder->setField('payment.cc_exp_month')
                ->setConditionType('eq')
                ->setValue($expMonth)
                ->create();

            $filters[] = $this->filterBuilder->setField('payment.cc_exp_year')
                ->setConditionType('eq')
                ->setValue($expYear)
                ->create();

            if ($excludeCustomers) {
                $filters[] = $this->filterBuilder->setField(OrderInterface::CUSTOMER_ID)
                    ->setConditionType('nin')
                    ->setValue($excludeCustomers)
                    ->create();
            }

            $dateTime = $this->dateTimeFactory->create();
            $timeStamp = $dateTime->timestamp(sprintf("-%d day", $days));
            $date = $dateTime->gmtDate(Mysql::DATETIME_FORMAT, $timeStamp);

            $filters[] = $this->filterBuilder->setField('invoice.created_at')
                ->setConditionType('gteq')
                ->setValue($date)
                ->create();

            $filters[] = $this->filterBuilder->setField('address.address_type')
                ->setConditionType('eq')
                ->setValue('billing')
                ->create();

            foreach ($filters as $filter) {
                $filterGroups[] = $this->filterGroupBuilder
                    ->addFilter($filter)
                    ->create();
            }

            $filterGroups[] = $this->filterGroupBuilder
                ->addFilter($this->filterBuilder->setField('address.postcode')
                    ->setConditionType('eq')
                    ->setValue($postcode)
                    ->create())
                ->addFilter($this->filterBuilder->setField('address.telephone')
                    ->setConditionType('eq')
                    ->setValue($phone)
                    ->create())
                ->create();

            $searchCriteria = $this->searchCriteriaBuilderFactory->create()
                ->setFilterGroups($filterGroups)
                ->setPageSize(1)
                ->create();

            $orders = $this->orderRepository->getList($searchCriteria);

            if ($orders->count()) {
                return true;
            }
        } catch (Exception | LocalizedException $e) {
            $this->logger->error($e->getMessage());
            return true;
        }

        return false;
    }

    /**
     * Get Console Output Object.
     *
     * @return OutputInterface
     */
    private function getOutput(): OutputInterface
    {
        if (!$this->output) {
            /** @var OutputInterface $output */
            $this->output = $this->outputFactory->create();
        }

        return $this->output;
    }
}
