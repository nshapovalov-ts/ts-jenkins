<?php

/**
 * Retailplace_MiraklQuote
 *
 * @copyright   Copyright (c) 2022 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Dmitriy Fionov <dmitriy@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\MiraklQuote\Model;

use Exception;
use SplFileObject;
use SplFileObjectFactory;
use Psr\Log\LoggerInterface;
use Mirakl\Api\Helper\Offer as MiraklApi;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Api\SearchCriteriaBuilderFactory;
use Retailplace\MiraklConnector\Api\Data\OfferInterface;
use Mirakl\MMP\OperatorShop\Domain\Offer\Importer\ImportMode;
use Retailplace\MiraklConnector\Api\OfferRepositoryInterface;
use Magento\Framework\Exception\ConfigurationMismatchException;
use Mirakl\MMP\Shop\Request\Offer\Importer\OfferImportRequestFactory;

/**
 * Class MiraklOfferUpdater
 */
class MiraklOfferUpdater
{
    /** @var string */
    public const IMPORT_FILE_NAME = 'Quotable-Offers-OF01-%s.csv';
    public const CSV_SEPARATOR = ';';

    /** @var string */
    public const XML_PATH_MIRAKL_QUOTE_OPERATOR_API_KEY = 'mirakl_api/general/operator_api_key';

    /** @var \Mirakl\Api\Helper\Offer */
    private $miraklApi;

    /** @var \Mirakl\MMP\Shop\Request\Offer\Importer\OfferImportRequestFactory */
    private $offerImportRequestFactory;

    /** @var \SplFileObjectFactory */
    private $splFileObjectFactory;

    /** @var \Retailplace\MiraklConnector\Api\OfferRepositoryInterface */
    private $offerRepository;

    /** @var \Magento\Framework\Api\SearchCriteriaBuilderFactory */
    private $searchCriteriaBuilderFactory;

    /** @var \Magento\Framework\App\Config\ScopeConfigInterface */
    private $config;

    /** @var \Magento\Framework\App\ResourceConnection */
    private $resourceConnection;

    /** @var \Psr\Log\LoggerInterface */
    private $logger;

    /**
     * Constructor
     *
     * @param \Mirakl\Api\Helper\Offer $miraklApi
     * @param \Mirakl\MMP\Shop\Request\Offer\Importer\OfferImportRequestFactory $offerImportRequestFactory
     * @param \SplFileObjectFactory $splFileObjectFactory
     * @param \Retailplace\MiraklConnector\Api\OfferRepositoryInterface $offerRepository
     * @param \Magento\Framework\Api\SearchCriteriaBuilderFactory $searchCriteriaBuilderFactory
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $config
     * @param \Magento\Framework\App\ResourceConnection $resourceConnection
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        MiraklApi $miraklApi,
        OfferImportRequestFactory $offerImportRequestFactory,
        SplFileObjectFactory $splFileObjectFactory,
        OfferRepositoryInterface $offerRepository,
        SearchCriteriaBuilderFactory $searchCriteriaBuilderFactory,
        ScopeConfigInterface $config,
        ResourceConnection $resourceConnection,
        LoggerInterface $logger
    ) {
        $this->miraklApi = $miraklApi;
        $this->offerImportRequestFactory = $offerImportRequestFactory;
        $this->splFileObjectFactory = $splFileObjectFactory;
        $this->offerRepository = $offerRepository;
        $this->searchCriteriaBuilderFactory = $searchCriteriaBuilderFactory;
        $this->config = $config;
        $this->resourceConnection = $resourceConnection;
        $this->logger = $logger;
    }

    /**
     * Update Offers in Mirakl
     *
     * @param array|null $shopIds
     * @return int
     * @throws \Exception
     */
    public function updateOffers(?array $shopIds = null): int
    {
        $count = 0;
        $offerIds = [];
        $shopIds = $shopIds ?: $this->getShopIds();
        if ($shopIds) {
            foreach ($shopIds as $shopId) {
                $shopId = (int)$shopId;
                $offers = $this->getOffers($shopId);
                $count += count($offers);
                if (count($offers)) {
                    $offerIds = $this->updateOfferIds($offers, $offerIds);
                    $offersCsv = $this->getOffersCsv($offers);
                    $this->sendImportFile($offersCsv, $shopId);
                }
            }
        }
        $this->updateOffersData($offerIds);

        return $count;
    }

    /**
     * Update Offers field Allow Quote Requests in DB
     *
     * @param array $offerIds
     */
    private function updateOffersData(array $offerIds)
    {
        if (count($offerIds)) {
            $connection = $this->resourceConnection->getConnection();
            $connection->update(
                $connection->getTableName('mirakl_offer'),
                ['allow_quote_requests' => 'true'],
                ['offer_id IN (?)' => $offerIds]
            );
        }
    }

    /**
     * Collect Offer IDs
     *
     * @param \Retailplace\MiraklConnector\Api\Data\OfferInterface[] $offers
     * @param array $offerIds
     * @return array
     */
    private function updateOfferIds(array $offers, array $offerIds): array
    {
        foreach ($offers as $offer) {
            $offerIds[] = $offer->getId();
        }

        return $offerIds;
    }

    /**
     * Get Operator API Key from Config
     *
     * @throws \Magento\Framework\Exception\ConfigurationMismatchException
     */
    private function getOperatorApiKey(): string
    {
        $key = $this->config->getValue(self::XML_PATH_MIRAKL_QUOTE_OPERATOR_API_KEY);
        if (!$key) {
            throw new ConfigurationMismatchException(__('Operator API key is absent in configuration'));
        }

        return $key;
    }


    /**
     * Send CSV file to Mirakl
     *
     * @throws \Exception
     */
    private function sendImportFile(SplFileObject $offersCsv, int $shopId)
    {
        /** @var \Mirakl\MMP\Shop\Request\Offer\Importer\OfferImportRequest $offerImportRequest */
        $offerImportRequest = $this->offerImportRequestFactory->create(['file' => $offersCsv]);
        $offerImportRequest->setWithProducts(false);
        $offerImportRequest->setFileName(sprintf(self::IMPORT_FILE_NAME, time()));
        $offerImportRequest->setImportMode(ImportMode::PARTIAL_UPDATE);
        $offerImportRequest->setShop($shopId);
        $offerImportRequest->bodyParams[] = 'shop';

        $client = $this->miraklApi->getClient();
        $client->setApiKey($this->getOperatorApiKey());

        try {
            $client($offerImportRequest);
        } catch (Exception $e) {
            $this->logger->error($e->getMessage());
            throw $e;
        }
    }

    /**
     * Generate CSV file from Offers array
     *
     * @param \Retailplace\MiraklConnector\Api\Data\OfferInterface[] $offers
     * @return \SplFileObject
     */
    private function getOffersCsv(array $offers): SplFileObject
    {
        /** @var \SplFileObject $csvFile */
        $csvFile = $this->splFileObjectFactory->create([
            'file_name' => 'php://memory',
            'open_mode' => 'w+'
        ]);

        $csvFile->fputcsv(
            [
                'allow-quote-requests',
                'sku'
            ],
            self::CSV_SEPARATOR
        );

        foreach ($offers as $offer) {
            $csvFile->fputcsv(
                [
                    'true',
                    $offer->getShopSku()
                ],
                self::CSV_SEPARATOR
            );
        }

        return $csvFile;
    }

    /**
     * Get Shop IDs for Offers Update
     *
     * @return array|null
     */
    private function getShopIds(): ?array
    {
        $searchCriteriaBuilder = $this->searchCriteriaBuilderFactory->create();
        $searchCriteria = $searchCriteriaBuilder
            ->addFilter(OfferInterface::ALLOW_QUOTE_REQUESTS, 'false')
            ->addFilter(OfferInterface::SHOP_SKU, '', 'neq')
            ->addFilter(OfferInterface::SHOP_SKU, true, 'notnull')
            ->setPageSize(1)
            ->create();

        $offers = $this->offerRepository->getList($searchCriteria);
        $shopIds = null;
        foreach ($offers->getItems() as $offer) {
            $shopIds = [
                $offer->getShopId()
            ];
        }

        return $shopIds;
    }

    /**
     * Get Offers List
     *
     * @param int $shopId
     * @return \Retailplace\MiraklConnector\Api\Data\OfferInterface[]
     */
    private function getOffers(int $shopId): array
    {
        $searchCriteriaBuilder = $this->searchCriteriaBuilderFactory->create();
        $searchCriteria = $searchCriteriaBuilder
            ->addFilter(OfferInterface::SHOP_ID, $shopId)
            ->addFilter(OfferInterface::ALLOW_QUOTE_REQUESTS, 'false')
            ->addFilter(OfferInterface::SHOP_SKU, '', 'neq')
            ->addFilter(OfferInterface::SHOP_SKU, true, 'notnull')
            ->create();

        $offers = $this->offerRepository->getList($searchCriteria);
        $offersList = [];
        foreach ($offers->getItems() as $offer) {
            if ($offer->getShopSku()) {
                $offersList[] = $offer;
            } else {
                $this->logger->warning(__(
                    'Offer with Product SKU %1 does not have required field Shop Product Sku, skip updating.',
                    $offer->getProductSku()
                ));
            }
        }

        return $offersList;
    }
}
