<?php

/**
 * Retailplace_Search
 *
 * @copyright   Copyright (c) 2022 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Alexander Korsun <aleksandr@tradesquare.com.au>
 * @author      Ruslan Mukhatov <ruslan@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\Search\Model\Adapter\DataMapper;

use Mirasvit\Search\Api\Data\Index\DataMapperInterface;
use Magento\Customer\Model\Group;
use Magento\Customer\Model\ResourceModel\Group\CollectionFactory;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\Search\Request\Dimension;
use Mirasvit\Search\Api\Data\IndexInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Customer\Model\ResourceModel\Group\Collection;
use Magento\Framework\Exception\NoSuchEntityException;
use Mirakl\Connector\Helper\Offer as OfferHelper;
use Retailplace\MiraklConnector\Rewrite\Helper\Offer as RetailplaceOfferHelper;

/**
 * Class IsSalable
 */
class IsSalable implements DataMapperInterface
{
    /**
     * @type string
     */
    const FIELD_NAME = 'am_is_salable';

    /**
     * @var CollectionFactory
     */
    private $customerGroupCollectionFactory;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var Collection
     */
    private $customerGroupCollection;

    /**
     * @var OfferHelper
     */
    private $offerHelper;

    /**
     * @param CollectionFactory $customerGroupCollectionFactory
     * @param StoreManagerInterface $storeManager
     * @param ScopeConfigInterface $scopeConfig
     * @param OfferHelper $offerHelper
     */
    public function __construct(
        CollectionFactory $customerGroupCollectionFactory,
        StoreManagerInterface $storeManager,
        ScopeConfigInterface $scopeConfig,
        OfferHelper $offerHelper
    ) {
        $this->customerGroupCollectionFactory = $customerGroupCollectionFactory;
        $this->storeManager = $storeManager;
        $this->scopeConfig = $scopeConfig;
        $this->offerHelper = $offerHelper;
    }

    /**
     * @param array $documents
     * @param Dimension[] $dimensions
     * @param IndexInterface $index
     *
     * @return array
     * @SuppressWarnings(PHPMD)
     * @throws NoSuchEntityException
     */
    public function map(array $documents, $dimensions, $index): array
    {
        $dimension = current($dimensions);
        $storeId = $dimension->getValue();
        $store = $this->storeManager->getStore($storeId);
        $websiteId = $store->getWebsiteId();
        $currencyCode = $store->getCurrentCurrencyCode();
        if ($this->customerGroupCollection === null) {
            $this->customerGroupCollection = $this->customerGroupCollectionFactory->create();
        }

        $productIds = [];
        foreach ($documents as $id => $doc) {
            $productIds[] = $id;
        }

        $offers = $this->offerHelper->getAvailableOffersForProducts($productIds, $currencyCode, null, $storeId);

        foreach ($documents as $id => $doc) {
            /** @var Group $customerGroup */
            foreach ($this->customerGroupCollection as $customerGroup) {
                $code = $customerGroup->getCustomerGroupCode();
                $isSalable = false;
                if (!empty($offers[$id][$code]) || !empty($offers[$id][RetailplaceOfferHelper::ALL_GROUPS])) {
                    $isSalable = true;
                }

                $key = self::FIELD_NAME . '_' . $customerGroup->getId() . '_' . $websiteId;
                $documents[$id][$key . "_raw"] = (int) $isSalable;
            }
        }
        return $documents;
    }

    /**
     * @return bool
     */
    public function isAllowed(): bool
    {
        return $this->scopeConfig->isSetFlag('amshopby/am_is_salable_filter/enabled', ScopeInterface::SCOPE_STORE);
    }
}
