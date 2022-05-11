<?php

/**
 * Retailplace_Search
 *
 * @copyright   Copyright (c) 2021 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Alexander Korsun <aleksandr@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\Search\Model\Adapter\DataMapper;

use Mirasvit\Search\Api\Data\Index\DataMapperInterface;
use Magento\Customer\Model\ResourceModel\Group\CollectionFactory;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\Search\Request\Dimension;
use Mirasvit\Search\Api\Data\IndexInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Customer\Model\ResourceModel\Group\Collection;
use Magento\Framework\Exception\NoSuchEntityException;
use Zend_Db_Expr;

/**
 * Class OnSale
 * @package Amasty\Shopby\Plugin\Elasticsearch\Model\Adapter\DataMapper
 */
class OnSale implements DataMapperInterface
{
    const FIELD_NAME = 'am_on_sale';

    /**
     * @var \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory
     */
    private $productCollectionFactory;

    /**
     * @var CollectionFactory
     */
    private $customerGroupCollectionFactory;

    /**
     * @var array
     */
    private $onSaleProductIds = [];

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

    public function __construct(
        \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory,
        CollectionFactory $customerGroupCollectionFactory,
        StoreManagerInterface $storeManager,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->productCollectionFactory = $productCollectionFactory;
        $this->customerGroupCollectionFactory = $customerGroupCollectionFactory;
        $this->storeManager = $storeManager;
        $this->scopeConfig = $scopeConfig;
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
    public function map(array $documents, $dimensions, $index)
    {
        $dimension = current($dimensions);
        $storeId = $dimension->getValue();
        $websiteId = $this->storeManager->getStore($storeId)->getWebsiteId();
        if ($this->customerGroupCollection === null) {
            $this->customerGroupCollection = $this->customerGroupCollectionFactory->create();
        }

        foreach ($documents as $id => $doc) {
            foreach ($this->customerGroupCollection as $item) {
                $key = self::FIELD_NAME . '_' . $item->getId() . '_' . $websiteId;
                $value = $this->isProductOnSale($id, $storeId, $item->getId());
                $documents[$id][$key . "_raw"] = (int) $value;
            }
        }
        return $documents;
    }

    /**
     * @return bool
     */
    public function isAllowed(): bool
    {
        return $this->scopeConfig->isSetFlag('amshopby/am_on_sale_filter/enabled', ScopeInterface::SCOPE_STORE);
    }

    /**
     * Is Product On Sale
     *
     * @param int $entityId
     * @param int $storeId
     * @param int $groupId
     * @return bool
     */
    private function isProductOnSale($entityId, $storeId, $groupId)
    {
        $onSaleProducts = $this->getOnSaleProductIds($storeId);
        if (isset($onSaleProducts[$entityId])) {
            $customerGroupIds = $onSaleProducts[$entityId];
            return empty($customerGroupIds) || in_array($groupId, array_values($customerGroupIds));
        }
        return false;
    }

    /**
     * Get On Sale ProductIds
     *
     * @param $storeId
     * @return array
     */
    private function getOnSaleProductIds($storeId): array
    {
        if (!isset($this->onSaleProductIds[$storeId])) {
            $this->onSaleProductIds[$storeId] = [];

            $customerGroupCollection = $this->customerGroupCollectionFactory->create();
            foreach ($customerGroupCollection as $item) {
                $collection = $this->productCollectionFactory->create()->addStoreFilter($storeId);

                $collection->addPriceData($item->getId());
                $collection->addAttributeToFilter('clearance', ['neq' => 1]);
                $select = $collection->getSelect();
                $select->where('price_index.final_price < price_index.price');
                $select->group('e.entity_id');
                $select->columns(
                    [
                        'customer_group_ids' =>
                            new Zend_Db_Expr('GROUP_CONCAT(price_index.customer_group_id SEPARATOR ",")')
                    ]
                );

                foreach ($collection as $product) {
                    $customerGroupIds = $product->getCustomerGroupIds() === null ?
                        '' : array_unique(explode(',', $product->getCustomerGroupIds()));
                    // @codingStandardsIgnoreStart
                    $this->onSaleProductIds[$storeId][$product->getId()] =
                        isset($this->onSaleProductIds[$storeId][$product->getId()])
                            ? array_merge($this->onSaleProductIds[$storeId][$product->getId()], $customerGroupIds)
                            : $customerGroupIds;
                    // @codingStandardsIgnoreEnd
                }
            }
        }
        return $this->onSaleProductIds[$storeId];
    }
}
