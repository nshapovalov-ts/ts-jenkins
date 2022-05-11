<?php
/**
 * Retailplace_MiraklConnector
 *
 * @copyright   Copyright © 2022 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Satish Gumudavelly <satish@kipanga.com.au>
 * @author      Dmitriy Kosykh <dmitriykosykh@tradesquare.com.au>
 */

namespace Retailplace\MiraklConnector\Plugin;

use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\CatalogSearch\Model\Indexer\Fulltext\Action\DataProvider;
use Retailplace\MiraklConnector\Model\MarginUpdater;
use Magento\Catalog\Api\Data\ProductInterface;

/**
 * Class SearchIndexerPlugin
 * @see \Magento\CatalogSearch\Model\Indexer\Fulltext\Action\DataProvider::prepareProductIndex()
 */
class SearchIndexerPlugin
{
    /**
     * @var array $attributes
     */
    private $attributes;

    /**
     * @var array $excludedAttributesForConfigurable
     */
    private $excludedAttributesForConfigurable = [MarginUpdater::IS_FOR_TRADE];

    /**
     * @var CollectionFactory
     */
    private $collectionFactory;

    /**
     * @param CollectionFactory $collectionFactory
     */
    public function __construct(
        CollectionFactory $collectionFactory
    ) {
        $this->collectionFactory = $collectionFactory;
    }

    /**
     * @param DataProvider $dataProvider
     * @param array $attributeData
     * @param array|null $productData
     * @param array|null $productAdditional
     * @param int|null $storeId
     *
     * @return array
     */
    public function afterPrepareProductIndex(
        DataProvider $dataProvider,
        array $attributeData,
        array $productData = null,
        array $productAdditional = null,
        int $storeId = null
    ): array {
        $typeId = $productAdditional['type_id'] ?? "";
        if ($typeId != 'configurable' || $productData === null || count($productData) === 0) {
            return $attributeData;
        }
        $configurableProductId = $productAdditional['entity_id'];

        foreach ($this->excludedAttributesForConfigurable as $attributeCode) {
            $attributeId = $this->getAttribute($dataProvider, $attributeCode)->getId();
            if (isset($attributeData[$attributeId])) {
                if (isset($attributeData[$attributeId][$configurableProductId])) {
                    $attributeData[$attributeId] = [
                        $configurableProductId => $attributeData[$attributeId][$configurableProductId]
                    ];
                } else {
                    unset($attributeData[$attributeId]);
                }
            }
        }

        $attributeId = $this->getAttribute($dataProvider, ProductInterface::SKU)->getId();
        if ($attributeId) {
            $attributeData[$attributeId] = $this->updateSku(array_keys($productData), $productAdditional['sku']);
        }

        return $attributeData;
    }

    /**
     * @param DataProvider $dataProvider
     * @param string $attributeCode
     * @return mixed
     */
    public function getAttribute(DataProvider $dataProvider, string $attributeCode)
    {
        if (isset($this->attributes[$attributeCode])) {
            return $this->attributes[$attributeCode];
        }
        return $this->attributes[$attributeCode] = $dataProvider->getSearchableAttribute($attributeCode);
    }

    /**
     * @param array $productsIds
     * @param string $sku
     *
     * @return string
     */
    private function updateSku(array $productsIds, string $sku): string
    {
        $skus = [$sku];
        $collection = $this->collectionFactory->create();
        $collection->addAttributeToFilter('entity_id', ['in' => $productsIds]);
        foreach ($collection as $product) {
            if ($sku !== $product->getSku()) {
                $skus[] = $product->getSku();
            }
        }

        return implode(' ', $skus);
    }
}
