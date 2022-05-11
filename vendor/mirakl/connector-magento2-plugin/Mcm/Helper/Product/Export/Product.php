<?php
namespace Mirakl\Mcm\Helper\Product\Export;

use Magento\Catalog\Model\ResourceModel\Eav\Attribute as EavAttribute;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Mirakl\Core\Model\ResourceModel\Product\Collection as ProductCollection;
use Mirakl\Core\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Mirakl\Mci\Model\Product\Attribute\ProductAttributesFinder;
use Mirakl\Mcm\Helper\Data as McmHelper;

class Product extends AbstractHelper
{
    /**
     * @var ProductCollectionFactory
     */
    protected $productCollectionFactory;

    /**
     * @var ProductAttributesFinder
     */
    protected $productAttributesFinder;

    /**
     * @param   Context                     $context
     * @param   ProductCollectionFactory    $productCollectionFactory
     * @param   ProductAttributesFinder     $productAttributesFinder
     */
    public function __construct(
        Context $context,
        ProductCollectionFactory $productCollectionFactory,
        ProductAttributesFinder $productAttributesFinder
    ) {
        parent::__construct($context);
        $this->productCollectionFactory = $productCollectionFactory;
        $this->productAttributesFinder = $productAttributesFinder;
    }

    /**
     * @return  string[]
     */
    public function getAttributeCodesToExport()
    {
        return array_map(function (EavAttribute $attribute) {
            return $attribute->getAttributeCode();
        }, $this->getAttributesToExport());
    }

    /**
     * @return  EavAttribute[]
     */
    public function getAttributesToExport()
    {
        return $this->productAttributesFinder->getExportableAttributes();
    }

    /**
     * @param   array   $productIds
     * @return  array
     */
    public function getProductsData(array $productIds)
    {
        $collections = $this->getProductsDataCollections($productIds);

        $data = [];
        /** @var \Mirakl\Core\Model\ResourceModel\Product\Collection $collection */
        foreach ($collections as $collection) {
            $collection->load(); // Load collection to be able to use methods below
            $collection->addCategoryPaths();
            $collection->addMediaGalleryAttribute();
            $collection->overrideByParentData([
                'parent_sku'                => 'sku',
                'parent_variant_group_code' => McmHelper::ATTRIBUTE_MIRAKL_VARIANT_GROUP_CODE
            ]);

            foreach ($collection as $product) {
                $productId = $product['entity_id'];
                if (!isset($data[$productId])) {
                    $data[$productId] = [];
                }
                $data[$productId] += $product;
            }
        }

        return $data;
    }

    /**
     * @param   array   $productIds
     * @return  ProductCollection
     */
    public function getProductCollection(array $productIds)
    {
        /** @var ProductCollection $collection */
        $collection = $this->productCollectionFactory->create();

        $collection->getSelect()
            ->reset(\Magento\Framework\DB\Select::COLUMNS)
            ->columns(['attribute_set_id', 'entity_id', 'sku']);

        $collection->addIdFilter($productIds);
        $collection->addAttributeToSelect(McmHelper::ATTRIBUTE_MIRAKL_IS_OPERATOR_MASTER, 'left');
        $collection->addAttributeToSelect(McmHelper::ATTRIBUTE_MIRAKL_PRODUCT_ID, 'left');
        $collection->addAttributeToSelect('mirakl_category_id', 'left');

        return $collection;
    }

    /**
     * @param   array   $productIds
     * @param   int     $attrChunkSize
     * @return  ProductCollection[]
     */
    public function getProductsDataCollections(array $productIds, $attrChunkSize = 15)
    {
        // Need to split into multipe collections because MySQL has a limited number of join possible for a query
        $collections = [];

        // Working with a limited chunk size because an attribute generates multiple joins
        $attributeChunks = array_chunk($this->getAttributesToExport(), $attrChunkSize);

        /** @var EavAttribute[] $attributes */
        foreach ($attributeChunks as $attributes) {
            $collection = $this->getProductCollection($productIds);
            foreach ($attributes as $attribute) {
                $collection->addAttributeToSelect($attribute->getAttributeCode());
            }
            $collections[] = $collection;
        }

        return $collections;
    }

    /**
     * @param   int $productId
     * @return  array
     */
    public function getSingleProductData($productId)
    {
        $products = $this->getProductsData([$productId]);

        return isset($products[$productId]) ? $products[$productId] : [];
    }

    /**
     * @param   string  $attrCode
     * @return  bool
     */
    public function isAttributeMultiSelect($attrCode)
    {
        $attribute = $this->productAttributesFinder->findByCode($attrCode);

        return $attribute && $attribute->getFrontendInput() == 'multiselect';
    }
}
