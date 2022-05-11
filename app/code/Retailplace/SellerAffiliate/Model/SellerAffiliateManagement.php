<?php

/**
 * Retailplace_SellerAffiliate
 *
 * @copyright   Copyright (c) 2022 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Evgeniy Derevyanko <evgeniy@tradesquare.com.au>
 * @author      Dmitriy Fionov <dmitriy@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\SellerAffiliate\Model;

use Magento\Framework\Api\SearchCriteriaBuilderFactory;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Stdlib\DateTime\DateTimeFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Model\ResourceModel\Order\Item\Collection;
use Retailplace\SellerAffiliate\Model\SellerAffiliateFactory;
use Magento\Sales\Model\ResourceModel\Order\Item\CollectionFactory as OrderItemCollectionFactory;
use Retailplace\SellerAffiliate\Api\Data\SellerAffiliateInterface;
use Retailplace\SellerAffiliate\Api\SellerAffiliateRepositoryInterface;
use Magento\Framework\HTTP\PhpEnvironment\RemoteAddress;
use Zend_Db_ExprFactory;
use Psr\Log\LoggerInterface;

/**
 * Class SellerAffiliateManagement implements management logic for Seller Affiliate
 * @SuppressWarnings(PHPMD.LongVariable)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class SellerAffiliateManagement
{
    /** @var string */
    public const DATE_TIME_FORMAT = 'Y-m-d H:i:s';
    public const AFFILIATE_COOKIE_NAME = 'seller_id';
    public const AFFILIATE_COOKIE_SELLER_ID = 'seller_id';
    public const AFFILIATE_COOKIE_CURRENT_URL = 'current_url';
    public const AFFILIATE_COOKIE_CURRENT_DATE = 'current_date';
    public const AFFILIATE_COOKIE_LIFETIME_SEC = 60 * 60 * 24 * 10;
    public const AFFILIATE_CODE_PREFIX = 'u';

    /** @var SellerAffiliateRepositoryInterface */
    private $sellerAffiliateRepository;

    /** @var SearchCriteriaBuilderFactory */
    private $searchCriteriaBuilderFactory;

    /** @var OrderItemCollectionFactory */
    private $orderItemCollectionFactory;

    /** @var SellerAffiliateFactory */
    private $sellerAffiliateFactory;

    /** @var RemoteAddress */
    private $remoteAddress;

    /** @var DateTimeFactory */
    private $dateTimeFactory;

    /** @var ResourceConnection */
    private $resourceConnection;

    /** @var Zend_Db_ExprFactory */
    private $exprFactory;

    /** @var LoggerInterface */
    private $logger;

    /**
     * SellerAffiliateManagement constructor
     *
     * @param SellerAffiliateRepositoryInterface $sellerAffiliateRepository
     * @param SearchCriteriaBuilderFactory $searchCriteriaBuilderFactory
     * @param OrderItemCollectionFactory $orderItemCollectionFactory
     * @param SellerAffiliateFactory $sellerAffiliateFactory
     * @param TimezoneInterface $dateTime
     * @param RemoteAddress $remoteAddress
     * @param ResourceConnection $resourceConnection
     * @param Zend_Db_ExprFactory $exprFactory
     * @param LoggerInterface $logger
     */
    public function __construct(
        SellerAffiliateRepositoryInterface $sellerAffiliateRepository,
        SearchCriteriaBuilderFactory $searchCriteriaBuilderFactory,
        OrderItemCollectionFactory $orderItemCollectionFactory,
        SellerAffiliateFactory $sellerAffiliateFactory,
        DateTimeFactory $dateTimeFactory,
        RemoteAddress $remoteAddress,
        ResourceConnection $resourceConnection,
        Zend_Db_ExprFactory $exprFactory,
        LoggerInterface $logger
    ) {
        $this->sellerAffiliateRepository = $sellerAffiliateRepository;
        $this->searchCriteriaBuilderFactory = $searchCriteriaBuilderFactory;
        $this->orderItemCollectionFactory = $orderItemCollectionFactory;
        $this->sellerAffiliateFactory = $sellerAffiliateFactory;
        $this->dateTimeFactory = $dateTimeFactory;
        $this->remoteAddress = $remoteAddress;
        $this->resourceConnection = $resourceConnection;
        $this->exprFactory = $exprFactory;
        $this->logger = $logger;
    }

    /**
     * Check is customer affiliated for the seller
     *
     * @param int $customerId
     * @param int $miraklShopId
     * @return bool
     */
    public function isCustomerAffiliated(int $customerId, int $miraklShopId): bool
    {
        $result = false;
        try {
            $searchCriteriaBuilder = $this->searchCriteriaBuilderFactory->create();
            $searchCriteriaBuilder->addFilter(SellerAffiliateInterface::CUSTOMER_ID, $customerId);
            $searchCriteriaBuilder->addFilter(SellerAffiliateInterface::SELLER_ID, $miraklShopId);
            $searchCriteriaBuilder->addFilter(SellerAffiliateInterface::IS_USER_AFFILIATED, true);
            $searchCriteria = $searchCriteriaBuilder->create();
            $result = (bool) $this->sellerAffiliateRepository->getList($searchCriteria)->getTotalCount();
        } catch (LocalizedException $e) {
            $this->logger->warning($e->getMessage());
        }

        return $result;
    }

    /**
     * @param int $customerId
     * @return array
     */
    public function getAffiliateShopIdsByCustomer(int $customerId): array
    {
        $affiliateShopIds = [];
        try {
            $searchCriteriaBuilder = $this->searchCriteriaBuilderFactory->create();
            $searchCriteriaBuilder->addFilter(SellerAffiliateInterface::CUSTOMER_ID, $customerId);
            $searchCriteriaBuilder->addFilter(SellerAffiliateInterface::IS_USER_AFFILIATED, true);
            $searchCriteria = $searchCriteriaBuilder->create();
            $affiliateShops = $this->sellerAffiliateRepository->getList($searchCriteria)->getItems();
            foreach ($affiliateShops as $shop) {
                $affiliateShopIds[] = $shop->getSellerId();
            }
        } catch (LocalizedException $exception) {
            $this->logger->error($exception->getMessage());
        }

        return $affiliateShopIds;
    }

    /**
     * Update shop affiliates
     *
     * @return int
     */
    public function updateShopAffiliates(): int
    {
        $affiliatesCollection = $this->getShopAffiliatesCollection();

        $affiliateIds = $affiliatesCollection->getColumnValues(SellerAffiliateInterface::SELLERAFFILIATE_ID);
        if (count($affiliateIds)) {
            $connection = $this->resourceConnection->getConnection();
            $connection->update(
                $connection->getTableName('retailplace_shop_affiliate'),
                [SellerAffiliateInterface::IS_USER_AFFILIATED => true],
                ['selleraffiliate_id IN (?)' => $affiliateIds]
            );
        }

        return count($affiliateIds);
    }

    /**
     * Get shop affiliates collection
     *
     * @return Collection
     */
    private function getShopAffiliatesCollection(): Collection
    {
        $orderItemCollection = $this->orderItemCollectionFactory->create();
        $minDate = $this->exprFactory->create(
            ['expression' => 'MIN(main_table.created_at)']
        );
        $orderItemCollection->getSelect()->columns(['order_date' => $minDate]);
        $orderItemCollection->getSelect()->joinInner(
            'sales_order',
            'sales_order.entity_id = main_table.order_id',
            'customer_id'
        );
        $orderItemCollection->getSelect()->joinInner(
            'retailplace_shop_affiliate',
            "retailplace_shop_affiliate.customer_id = sales_order.customer_id AND
                  retailplace_shop_affiliate.seller_id = main_table.mirakl_shop_id",
            ["selleraffiliate_id", "click_datetime"]
        );
        $orderItemCollection->addFieldToFilter('main_table.mirakl_shop_id', ['gt' => 0]);
        $orderItemCollection->addFieldToFilter('sales_order.customer_id', ['gt' => 0]);
        $orderItemCollection->getSelect()->group(['sales_order.customer_id', 'main_table.mirakl_shop_id']);
        $having = $this->exprFactory->create(
            ['expression' => 'order_date > retailplace_shop_affiliate.click_datetime']
        );
        $orderItemCollection->getSelect()->having($having);

        return $orderItemCollection;
    }

    /**
     * Check is user affiliated for the customer
     *
     * @param int $customerId
     * @param int $sellerId
     * @param string $datetime
     * @return bool
     */
    public function isFirstOrderForAffiliatedCustomer(int $customerId, int $sellerId, string $datetime): bool
    {
        $orderItemCollection = $this->orderItemCollectionFactory->create();
        $orderItemCollection->getSelect()->joinInner(
            'sales_order',
            'sales_order.entity_id = main_table.order_id',
            'customer_id'
        );
        $orderItemCollection->addFieldToFilter('customer_id', ['eq' => $customerId]);
        $orderItemCollection->addFieldToFilter('mirakl_shop_id', ['eq' => $sellerId]);
        $orderItemCollection->addFieldToFilter('main_table.created_at', ['lt' => $datetime]);

        return !(bool) $orderItemCollection->getSize();
    }

    /**
     * @param int $sellerId
     * @param int $customerId
     * @param string $affiliateUrl
     * @param string $clientSideDateTime
     * @return void
     */
    public function createSellerAffiliateEntity(
        int $sellerId,
        int $customerId,
        string $affiliateUrl,
        string $clientSideDateTime
    ) {
        try {
            $searchCriteriaBuilder = $this->searchCriteriaBuilderFactory->create();
            $searchCriteriaBuilder
                ->addFilter(
                    SellerAffiliateInterface::CUSTOMER_ID,
                    $customerId
                )
                ->addFilter(
                    SellerAffiliateInterface::SELLER_ID,
                    $sellerId
                );
            $searchCriteria = $searchCriteriaBuilder->create();
            $isAlreadyAffiliated = (bool) $this->sellerAffiliateRepository->getList($searchCriteria)->getTotalCount();
            if (!$isAlreadyAffiliated) {
                $dateTime = $this->dateTimeFactory->create();
                $currentServerDateTime = $dateTime->gmtDate();
                $remoteAddress = $this->remoteAddress->getRemoteAddress();
                $isCustomerAffiliated = $this->isFirstOrderForAffiliatedCustomer(
                    $customerId,
                    $sellerId,
                    $currentServerDateTime
                );
                $sellerAffiliate = $this->sellerAffiliateFactory->create();
                $clientSideDateTime = $clientSideDateTime ?? $currentServerDateTime;
                $sellerAffiliate->setCustomerId($customerId);
                $sellerAffiliate->setSellerId($sellerId);
                $sellerAffiliate->setAffiliateUrl($affiliateUrl);
                $sellerAffiliate->setClientSideDateTime($clientSideDateTime);
                $sellerAffiliate->setClickDateTime($currentServerDateTime);
                $sellerAffiliate->setIpAddress($remoteAddress);
                $sellerAffiliate->setIsUserAffiliated($isCustomerAffiliated);
                $this->sellerAffiliateRepository->save($sellerAffiliate);
            }
        } catch (LocalizedException $exception) {
            $this->logger->error($exception->getMessage());
        }
    }
}
