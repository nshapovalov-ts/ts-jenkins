<?php

/**
 * Retailplace_MiraklApi
 *
 * @copyright   Copyright (c) 2022 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Alexander Korsun <aleksandr@tradesquare.com.au>
 * @author      Ruslan Mukhatov <ruslan@tradesquare.com.au>
 * @author      Evgeniy Derevyanko <evgeniy@tradesquare.com.au>
 * @author      Dmitriy Fionov <dmitriy@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\MiraklApi\Helper;

use Retailplace\MiraklShop\Api\Data\ShopInterface;
use Mirakl\MMP\Front\Domain\Order\Create\CreatedOrders;
use Mirakl\MMP\Front\Domain\Order\Create\CreateOrder;
use Mirakl\MMP\Front\Request\Order\Workflow\CreateOrderRequest;
use Magento\Framework\App\Helper\Context;
use Mirakl\Api\Model\Client\ClientManager;
use Magento\Framework\App\CacheInterface;
use Mirakl\Api\Model\Log\LoggerManager;
use Mirakl\Api\Model\Log\RequestLogValidator;
use Mirakl\MMP\Front\Client\FrontApiClientFactory;
use Mirakl\MMP\FrontOperator\Domain\Order\AdditionalField\UpdateAdditionalFieldsFactory;
use Mirakl\MMP\FrontOperator\Request\Order\AdditionalField\UpdateAdditionalFieldsRequestFactory;
use Mirakl\Core\Model\ResourceModel\Shop\CollectionFactory as ShopCollectionFactory;
use Magento\Customer\Api\GroupRepositoryInterface;
use Magento\Customer\Api\Data\GroupInterface;
use Magento\Framework\Api\SearchCriteriaBuilderFactory;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Psr\Log\LoggerInterface;
use Exception;
use Mirakl\Api\Helper\Config;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\Exception\LocalizedException;
use Mirakl\Api\Helper\Payment;
use Mirakl\MMP\FrontOperator\Domain\Order as MiraklOrder;
use Mirakl\Core\Helper\Config as CoreConfig;
use Mirakl\Api\Helper\Order as OrderApiHelper;
use Magento\Framework\Serialize\SerializerInterface;
use Retailplace\MiraklOrder\Api\MiraklOrderRepositoryInterface;
use Retailplace\MiraklOrder\Api\Data\MiraklOrderInterface;
use Retailplace\MiraklOrder\Model\MiraklOrderFactory;
use Retailplace\SellerAffiliate\Model\SellerAffiliateManagement;
use Retailplace\MiraklApi\Model\Queue\Publisher;
use Magento\Sales\Api\OrderRepositoryInterface;
use Mirakl\Connector\Model\Order\Converter as OrderConverter;
use Magento\Sales\Model\ResourceModel\Order as OrderResource;

/**
 * Class Order
 */
class Order extends OrderApiHelper
{
    /**
     * @type string
     */
    const AU_POST_CUSTOMER_GROUP = 'AU_Post';
    const XML_PATH_QUEUE_ENABLED = 'retailplace_mirakl_order/queue_settings/queue_enabled';

    /** @var GroupRepositoryInterface */
    private $groupRepository;

    /** @var UpdateAdditionalFieldsFactory */
    private $updateAdditionalFieldsFactory;

    /** @var ShopCollectionFactory */
    private $shopCollectionFactory;

    /** @var SearchCriteriaBuilderFactory */
    private $searchCriteriaBuilderFactory;

    /** @var UpdateAdditionalFieldsRequestFactory */
    private $updateAdditionalFieldsRequestFactory;

    /** @var CustomerRepositoryInterface */
    private $customerRepository;

    /** @var array */
    private $shopIds;

    /** @var array */
    private $customerGroups;

    /** @var LoggerInterface */
    private $logger;

    /** @var SerializerInterface */
    private $serializer;

    /** @var Config */
    private $configApi;

    /** @var FrontApiClientFactory */
    private $frontApiClientFactory;

    /** @var Payment */
    private $helperPayment;

    /** @var CoreConfig */
    private $coreConfig;

    /** @var array */
    private $miraklOrders;

    /** @var MiraklOrderFactory */
    private $miraklOrderFactory;

    /** @var MiraklOrderRepositoryInterface */
    private $miraklOrderRepository;

    /** @var SellerAffiliateManagement */
    private $sellerAffiliateManagement;

    /** @var Publisher */
    private $queuePublisher;

    /** @var OrderRepositoryInterface */
    private $orderRepository;

    /** @var OrderConverter */
    private $orderConverter;

    /** @var OrderResource */
    private $orderResource;

    /**
     * @param Context $context
     * @param ClientManager $clientManager
     * @param CacheInterface $cache
     * @param LoggerManager $loggerManager
     * @param RequestLogValidator $requestLogValidator
     * @param CustomerRepositoryInterface $customerRepository
     * @param ShopCollectionFactory $shopCollectionFactory
     * @param GroupRepositoryInterface $groupRepository
     * @param SearchCriteriaBuilderFactory $searchCriteriaBuilderFactory
     * @param FrontApiClientFactory $frontApiClientFactory
     * @param UpdateAdditionalFieldsFactory $updateAdditionalFieldsFactory
     * @param UpdateAdditionalFieldsRequestFactory $updateAdditionalFieldsRequestFactory
     * @param Payment $helperPayment
     * @param Config $configApi
     * @param CoreConfig $coreConfig
     * @param LoggerInterface $logger
     * @param SerializerInterface $serializer
     */
    public function __construct(
        Context $context,
        ClientManager $clientManager,
        CacheInterface $cache,
        LoggerManager $loggerManager,
        RequestLogValidator $requestLogValidator,
        CustomerRepositoryInterface $customerRepository,
        ShopCollectionFactory $shopCollectionFactory,
        GroupRepositoryInterface $groupRepository,
        SearchCriteriaBuilderFactory $searchCriteriaBuilderFactory,
        FrontApiClientFactory $frontApiClientFactory,
        UpdateAdditionalFieldsFactory $updateAdditionalFieldsFactory,
        UpdateAdditionalFieldsRequestFactory $updateAdditionalFieldsRequestFactory,
        Payment $helperPayment,
        Config $configApi,
        CoreConfig $coreConfig,
        LoggerInterface $logger,
        SerializerInterface $serializer,
        MiraklOrderFactory $miraklOrderFactory,
        MiraklOrderRepositoryInterface $miraklOrderRepository,
        SellerAffiliateManagement $sellerAffiliateManagement,
        Publisher $queuePublisher,
        OrderRepositoryInterface $orderRepository,
        OrderConverter $orderConverter,
        OrderResource $orderResource
    ) {
        $this->customerRepository = $customerRepository;
        $this->shopCollectionFactory = $shopCollectionFactory;
        $this->groupRepository = $groupRepository;
        $this->searchCriteriaBuilderFactory = $searchCriteriaBuilderFactory;
        $this->frontApiClientFactory = $frontApiClientFactory;
        $this->updateAdditionalFieldsFactory = $updateAdditionalFieldsFactory;
        $this->updateAdditionalFieldsRequestFactory = $updateAdditionalFieldsRequestFactory;
        $this->helperPayment = $helperPayment;
        $this->coreConfig = $coreConfig;
        $this->configApi = $configApi;
        $this->logger = $logger;
        $this->serializer = $serializer;
        $this->miraklOrderFactory = $miraklOrderFactory;
        $this->miraklOrderRepository = $miraklOrderRepository;
        $this->sellerAffiliateManagement = $sellerAffiliateManagement;
        $this->queuePublisher = $queuePublisher;
        $this->orderRepository = $orderRepository;
        $this->orderConverter = $orderConverter;
        $this->orderResource = $orderResource;
        parent::__construct($context, $clientManager, $cache, $loggerManager, $requestLogValidator);
    }

    /**
     * (OR01) Creates a new order on the Mirakl platform
     *
     * @param int $orderId
     * @param mixed $markAsSent
     * @param bool $useQueue
     * @return CreatedOrders|null
     * @throws LocalizedException
     */
    public function createMiraklOrder(int $orderId, $markAsSent, bool $useQueue = false): ?CreatedOrders
    {
        $result = null;
        if ($this->scopeConfig->isSetFlag(self::XML_PATH_QUEUE_ENABLED) && $useQueue) {
            $this->queuePublisher->addToQueue($orderId, $markAsSent);
        } else {
            $result = $this->process($orderId, $markAsSent, $useQueue);
        }

        return $result;
    }

    /**
     * (OR01) Creates a new order on the Mirakl platform
     *
     * @param int $orderId
     * @param mixed $markAsSent
     * @param bool $useQueue
     * @return CreatedOrders|null
     * @throws LocalizedException
     */
    public function process(int $orderId, $markAsSent, $useQueue = true): ?CreatedOrders
    {
        $magentoOrder = $this->orderRepository->get($orderId);
        $order = $this->orderConverter->convert($magentoOrder);
        if (!$this->validateLocale($order->getCustomer()->getLocale())) {
            $order->getCustomer()->unsetData('locale'); // Reset the locale if not handled by Mirakl
        }

        $request = new CreateOrderRequest($order);

        $this->_eventManager->dispatch('mirakl_api_create_order_before', ['request' => $request]);
        $response = null;
        try {
            $response = $this->send($request);
        } catch (Exception $e) {
            if (strpos($e->getMessage(), 'already exists.') !== false) {
                $magentoOrder->setMiraklSent(1);
                $this->orderResource->saveAttribute($magentoOrder, 'mirakl_sent');
                if (!$useQueue) {
                    throw $e;
                }
            } else {
                throw $e;
            }
        }

        if ($response) {
            $apiResponse = $this->serializer->unserialize($response);

            foreach ($apiResponse['orders'] as $miraklOrder) {
                $customerId = 0;
                if ($miraklOrder['customer']) {
                    $customerId = $miraklOrder['customer']['customer_id'];
                }

                /** @var MiraklOrderInterface $miraklOrderModel */
                $miraklOrderModel = $this->miraklOrderFactory->create();
                $miraklOrderModel->setMiraklOrderId($miraklOrder['id']);
                $miraklOrderModel->setIsAffiliated(
                    $this->sellerAffiliateManagement->isCustomerAffiliated((int) $customerId, (int) $miraklOrder['shop_id'])
                );
                $this->miraklOrderRepository->save($miraklOrderModel);

                $orderLines = [];
                foreach ($miraklOrder['order_lines'] as $orderLine) {
                    $orderLines[] = ["accepted" => true, "id" => $orderLine['id']];
                }
                $this->orderAcceptance($miraklOrder['id'], $orderLines);
            }

            foreach ($apiResponse['orders'] as $miraklOrder) {
                $miraklOrder = $this->getMiraklOrderById($order->getCommercialId(), $miraklOrder['id']);
                if (empty($miraklOrder)) {
                    continue;
                }

                $this->helperPayment->debitPayment($miraklOrder, $miraklOrder->getCustomer()->getId());
                $this->updateAdditionalFields($miraklOrder);
            }

            if ($markAsSent && $response->getOrders()->count()) {
                $magentoOrder->setMiraklSent(1);
                $this->orderResource->saveAttribute($magentoOrder, 'mirakl_sent');
            }
        }

        return $response;
    }

    /**
     * Call Put Api
     *
     * @param string $apiBaseUrl
     * @param string $api
     * @param string $payload
     * @return bool|string|void
     */
    public function callPutApi($apiBaseUrl, $api, $payload = "")
    {
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL            => $apiBaseUrl . $api,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING       => "",
            CURLOPT_MAXREDIRS      => 10,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST  => "PUT",
            CURLOPT_POSTFIELDS     => $this->serializer->serialize($payload),
            CURLOPT_HTTPHEADER     => [
                "authorization:" . $this->configApi->getApiKey(),
                "cache-control: no-cache",
                "content-type: application/json"
            ],
        ]);
        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        if ($err) {
            $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/orderStatusUpdate.log');
            $logger = new \Zend\Log\Logger();
            $logger->addWriter($writer);

            $logger->info("cURL Error #:" . print_r($err, true));
            echo "cURL Error #:" . $err;
            die;
        } else {
            return $response;
        }
    }

    /**
     * Order Acceptance
     *
     * @param string $orderId
     * @param array $orderItems
     * @return bool|string|void
     */
    public function orderAcceptance(string $orderId, array $orderItems)
    {
        return $this->callPutApi($this->configApi->getApiUrl(), "/orders/$orderId/accept", [
            'order_lines' => $orderItems
        ]);
    }

    /**
     * Update Additional Fields
     *
     * @param MiraklOrder $miraklOrder
     * @return void
     */
    public function updateAdditionalFields(MiraklOrder $miraklOrder)
    {
        $additionalFields = [];
        $notes = [];
        if ($this->isCentralisedBillingPayment((int) $miraklOrder->getCustomer()->getId(), (int) $miraklOrder->getShopId())) {
            $notes[] = __('LPO Centralised billing payment');
        }

        if (
            $this->sellerAffiliateManagement->isCustomerAffiliated(
                (int) $miraklOrder->getCustomer()->getId(),
                (int) $miraklOrder->getShopId()
            )
        ) {
            $notes[] = __('TradeSquare Connect order');
        }

        if (count($notes)) {
            $additionalFields[] = [
                'type'  => 'STRING',
                'code'  => 'notes',
                'value' => implode(', ', $notes)
            ];
        }

        if (!$additionalFields) {
            return;
        }

        try {
            // Instantiating the Mirakl API Client
            $client = $this->frontApiClientFactory->create([
                'baseUrl' => $this->configApi->getApiUrl(),
                'apiKey'  => $this->configApi->getApiKey()
            ]);
            $update = $this->updateAdditionalFieldsFactory->create();
            $update->setOrderAdditionalFields($additionalFields);

            $request = $this->updateAdditionalFieldsRequestFactory->create(['orderId' => $miraklOrder->getId()]);
            $request->setOrderAdditionalFields($update);
            $client->updateOrderAdditionalFields($request);
        } catch (Exception $e) {
            $this->logger->error($e->getMessage());
        }
    }

    /**
     * Is Centralised Billing Payment
     *
     * @param int $customerId
     * @param int $shopId
     * @return bool
     */
    private function isCentralisedBillingPayment(int $customerId, int $shopId): bool
    {
        $customer = $this->getCustomer($customerId);
        if (!$customer) {
            return false;
        }

        if ($this->getGroupIdByCode(self::AU_POST_CUSTOMER_GROUP) != $customer->getGroupId()) {
            return false;
        }

        if (!in_array($shopId, $this->getAUPostSellersIds())) {
            return false;
        }

        return true;
    }

    /**
     * Get Customer
     *
     * @param int $customerId
     * @return CustomerInterface|null
     */
    private function getCustomer(int $customerId): ?CustomerInterface
    {
        $customer = null;
        try {
            $customer = $this->customerRepository->getById($customerId);
        } catch (Exception $e) {
            $this->logger->error($e->getMessage());
        }
        return $customer;
    }

    /**
     * Get Customer Group ID by Code
     *
     * @param string $groupCode
     * @return int|null
     */
    private function getGroupIdByCode(string $groupCode): ?int
    {
        if (isset($this->customerGroups[$groupCode])) {
            return $this->customerGroups[$groupCode];
        }

        $this->customerGroups[$groupCode] = null;

        try {
            $searchCriteriaBuilder = $this->searchCriteriaBuilderFactory->create();
            $searchCriteria = $searchCriteriaBuilder
                ->addFilter(GroupInterface::CODE, $groupCode, 'eq')
                ->create();

            $groups = $this->groupRepository->getList($searchCriteria);
            foreach ($groups->getItems() as $group) {
                $this->customerGroups[$groupCode] = (int) $group->getId();
            }
        } catch (Exception $e) {
            $this->logger->error($e->getMessage());
        }

        return $this->customerGroups[$groupCode];
    }

    /**
     * Get all Shop IDs with au_post_seller attribute
     *
     * @return array
     */
    private function getAUPostSellersIds(): array
    {
        if ($this->shopIds !== null) {
            return $this->shopIds;
        }

        $shopIds = [];

        try {
            $shopCollection = $this->shopCollectionFactory->create();
            $shopCollection
                ->addFieldToFilter(
                    ShopInterface::AU_POST_SELLER,
                    ['eq' => true]
                )
                ->addFieldToSelect('id');

            $shopIds = $shopCollection->getAllIds();
        } catch (Exception $e) {
            $this->logger->error($e->getMessage());
        }

        $this->shopIds = $shopIds;
        return $this->shopIds;
    }

    /**
     * Returns Mirakl order associated with specified order commercial id and remote id
     *
     * @param string $commercialId
     * @param string $remoteId
     * @return MiraklOrder|null
     */
    private function getMiraklOrderById(string $commercialId, string $remoteId): ?MiraklOrder
    {
        if (!isset($this->miraklOrders[$commercialId])) {
            $miraklOrders = $this->getOrdersByCommercialId(
                $commercialId,
                false,
                $this->coreConfig->getLocale()
            );

            foreach ($miraklOrders as $miraklOrder) {
                $this->miraklOrders[$commercialId][$miraklOrder->getId()] = $miraklOrder;
            }
        }

        if (!empty($this->miraklOrders[$commercialId][$remoteId])) {
            return $this->miraklOrders[$commercialId][$remoteId];
        }

        return null;
    }
}
