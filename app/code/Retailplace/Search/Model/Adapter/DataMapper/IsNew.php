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
use Magento\Store\Model\ScopeInterface;
use Mirasvit\Search\Api\Data\IndexInterface;
use Magento\Framework\Search\Request\Dimension;
use Amasty\Shopby\Model\Layer\Filter\IsNew\Helper;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;

/**
 * Class IsNew
 * @package Retailplace\Search\Model\Adapter\DataMapper
 */
class IsNew implements DataMapperInterface
{
    const FIELD_NAME = 'am_is_new';
    const DOCUMENT_FIELD_NAME = 'news_from_date';
    const INDEX_DOCUMENT = 'document';

    /**
     * @var CollectionFactory
     */
    private $productCollectionFactory;

    /**
     * @var Helper
     */
    private $isNewHelper;

    /**
     * @var array
     */
    private $newProductIds = [];

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    public function __construct(
        CollectionFactory $productCollectionFactory,
        Helper $isNewHelper,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->productCollectionFactory = $productCollectionFactory;
        $this->isNewHelper = $isNewHelper;
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * @param array $documents
     * @param Dimension[] $dimensions
     * @param IndexInterface $index
     *
     * @return array
     * @SuppressWarnings(PHPMD)
     */
    public function map(array $documents, $dimensions, $index)
    {

        $dimension = current($dimensions);
        $storeId = $dimension->getValue();

        foreach ($documents as $id => $doc) {
            $key = self::FIELD_NAME;
            $value = $rawDocs[$id][self::DOCUMENT_FIELD_NAME] ?? $this->isProductNew($id, $storeId);
            $documents[$id][$key . "_raw"] = (int) $value;
        }

        return $documents;
    }

    /**
     * @return bool
     */
    public function isAllowed(): bool
    {
        return $this->scopeConfig->isSetFlag('amshopby/am_is_new_filter/enabled', ScopeInterface::SCOPE_STORE);
    }

    /**
     * @param $entityId
     * @param $storeId
     * @return bool
     */
    private function isProductNew($entityId, $storeId): bool
    {
        return isset($this->getNewProductIds($storeId)[$entityId]);
    }

    /**
     * @param $storeId
     * @return array
     */
    private function getNewProductIds($storeId): array
    {
        if (!isset($this->newProductIds[$storeId])) {
            $this->newProductIds[$storeId] = [];
            $collection = $this->productCollectionFactory->create()->addStoreFilter($storeId);
            $this->isNewHelper->addNewFilter($collection);

            foreach ($collection as $item) {
                $this->newProductIds[$storeId][$item->getId()] = $item->getId();
            }
        }
        return $this->newProductIds[$storeId];
    }
}
