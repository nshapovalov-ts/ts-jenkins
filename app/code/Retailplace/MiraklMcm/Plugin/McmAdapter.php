<?php

/**
 * Retailplace_MiraklMcm
 *
 * @copyright   Copyright (c) 2022 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Satish Gumudavelly <satish@kipanga.com.au>
 * @author      Natalia Sekulich <natalia@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\MiraklMcm\Plugin;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Catalog\Api\ProductAttributeOptionManagementInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Category;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Attribute\Repository as AttributeRepository;
use Magento\Catalog\Model\ResourceModel\Eav\Attribute;
use Magento\ConfigurableProduct\Helper\Product\Options\Factory as OptionsFactory;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Eav\Api\Data\AttributeOptionInterfaceFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Store\Model\Store;
use Mirakl\Mci\Helper\Product\Import\Data as DataHelper;
use Mirakl\Mcm\Model\Product\Import\Adapter\Mcm;
use Mirakl\Mcm\Model\Product\Import\Indexer\Indexer;
use Magento\Catalog\Model\ResourceModel\Eav\Attribute as ProductAttribute;
use Retailplace\MiraklConnector\Setup\Patch\Data\AddBestsellerAttribute;
use Retailplace\MiraklMcm\Model\BestSellerAttributeUpdater;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Retailplace\MiraklMcm\Helper\Data;
use Retailplace\MiraklShop\Model\AttributeUpdater\HasNewProductsAttributeUpdater;

/**
 * Class McmAdapter
 */
class McmAdapter
{
    /**
     * @var DataHelper
     */
    protected $dataHelper;

    /**
     * @var AttributeRepository
     */
    protected $attributeRepository;

    /**
     * @var ProductRepositoryInterface
     */
    protected $productRepository;

    /**
     * @var Indexer
     */
    protected $indexer;

    /**
     * @var OptionsFactory
     */
    protected $optionsFactory;

    /**
     * @var ProductAttributeOptionManagementInterface
     */
    protected $productAttributeOptionManagement;

    /**
     * @var AttributeOptionInterfaceFactory
     */
    protected $optionDataFactory;

    /**
     * @var string
     */
    private $bestSellerOptionId;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @var array
     */
    private $allSetAttributes;

    /**
     * @var TimezoneInterface
     */
    private $timezone;

    /**
     * @var HasNewProductsAttributeUpdater
     */
    private $newProductsAttributeUpdater;

    /**
     * @param DataHelper $dataHelper
     * @param AttributeRepository $attributeRepository
     * @param ProductRepositoryInterface $productRepository
     * @param Indexer $indexer
     * @param OptionsFactory $optionsFactory
     * @param ProductAttributeOptionManagementInterface $productAttributeOptionManagement
     * @param AttributeOptionInterfaceFactory $optionDataFactory
     * @param TimezoneInterface $timezone
     * @param HasNewProductsAttributeUpdater $newProductsAttributeUpdater
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     */
    public function __construct(
        DataHelper $dataHelper,
        AttributeRepository $attributeRepository,
        ProductRepositoryInterface $productRepository,
        Indexer $indexer,
        OptionsFactory $optionsFactory,
        ProductAttributeOptionManagementInterface $productAttributeOptionManagement,
        AttributeOptionInterfaceFactory $optionDataFactory,
        TimezoneInterface $timezone,
        HasNewProductsAttributeUpdater $newProductsAttributeUpdater,
        SearchCriteriaBuilder $searchCriteriaBuilder
    ) {
        $this->dataHelper = $dataHelper;
        $this->attributeRepository = $attributeRepository;
        $this->productRepository = $productRepository;
        $this->indexer = $indexer;
        $this->optionsFactory = $optionsFactory;
        $this->productAttributeOptionManagement = $productAttributeOptionManagement;
        $this->optionDataFactory = $optionDataFactory;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->timezone = $timezone;
        $this->newProductsAttributeUpdater = $newProductsAttributeUpdater;
    }

    /**
     * Replace empty variant values by placeholders
     *
     * @param Mcm $subject
     * @param array $data
     * @return array
     * @throws LocalizedException
     * @throws \Magento\Framework\Exception\InputException
     * @throws NoSuchEntityException
     * @throws \Magento\Framework\Exception\StateException
     */
    public function beforeImport(
        Mcm $subject,
        array $data
    ) {
        $data = $this->_convertTextValuesToOptionIds($subject, $data);
        $data = $this->_replaceEmptyVariantValues($subject, $data);
        $data = $this->updateBestSellerAttribute($subject, $data);
        return [$data];
    }

    /**
     * Get Mirakl shop IDs from the products and update these shops
     *
     * @param Mcm $subject
     * @param ProductInterface $product
     * @param array $data
     *
     * @return ProductInterface
     */
    public function afterImport(
        Mcm $subject,
        ProductInterface $product,
        array $data
    ) {
        if ($this->isProductNew($product)) {
            $shopIds = $product->getMiraklShopIds() ?? '';
            if ($shopIds) {
                $this->newProductsAttributeUpdater->updateOnProductImport($shopIds);
            }
        }

        return $product;
    }

    /**
     * Check if a product is new
     *
     * @param ProductInterface $product
     *
     * @return bool
     */
    private function isProductNew(ProductInterface $product): bool
    {
        $newsFromDate = $product->getNewsFromDate();
        $newsToDate = $product->getNewsToDate();
        if (!$newsFromDate && !$newsToDate) {
            return false;
        }

        return $this->timezone->isScopeDateInInterval(
            $product->getStore(),
            $newsFromDate,
            $newsToDate
        );
    }

    /**
     * @param string $attrCode
     * @return  ProductAttribute
     * @throws  \Exception
     */
    private function getAttribute(string $attrCode)
    {
        return $this->attributeRepository->get($attrCode);
    }

    /**
     * @return string
     * @throws \Exception
     */
    public function getBestSellerOptionId()
    {
        if ($this->bestSellerOptionId == null) {
            $topProduct = $this->getAttribute(BestSellerAttributeUpdater::TOP_PRODUCT_ATTRIBUTE);
            $this->bestSellerOptionId = $topProduct->getSource()->getOptionId(BestSellerAttributeUpdater::BEST_SELLER_OPTION_LABEL);
        }
        return $this->bestSellerOptionId;
    }

    /**
     * @param Mcm $subject
     * @param $data
     * @return mixed
     * @throws \Exception
     */
    protected function updateBestSellerAttribute(Mcm $subject, &$data)
    {
        $topProductValue = $data[BestSellerAttributeUpdater::TOP_PRODUCT_ATTRIBUTE] ?? '';
        $data[AddBestsellerAttribute::BEST_SELLER] = ($topProductValue == $this->getBestSellerOptionId());
        return $data;
    }

    /**
     * @param Mcm $subject
     * @param array $data
     * @return array
     * @throws LocalizedException
     * @throws \Magento\Framework\Exception\InputException
     * @throws NoSuchEntityException
     * @throws \Magento\Framework\Exception\StateException
     */
    protected function _convertTextValuesToOptionIds(Mcm $subject, $data)
    {
        $attributeCode = 'item_variant';
        if (empty($data[$attributeCode])) {
            return $data;
        }

        $value = $data[$attributeCode];

        /** @var Attribute $attribute */
        $attribute = $this->attributeRepository->get($attributeCode);
        if (!$attribute->usesSource()) {
            return $data;
        }

        $optionId = $attribute->getSource()->getOptionId($value);
        if (!$optionId) {
            $option = $this->optionDataFactory->create()->setLabel($value);
            $this->productAttributeOptionManagement->add($attributeCode, $option);
            $optionId = $attribute->getSource()->getOptionId($value);
        }
        $data[$attributeCode] = $optionId;
        return $data;
    }

    /**
     * @param Mcm $subject
     * @param array $data
     * @return array
     * @throws LocalizedException
     */
    protected function _replaceEmptyVariantValues(Mcm $subject, $data)
    {
        if (!$this->dataHelper->isDataHaveVariants($data)) {
            return $data;
        }

        $parentProduct = $subject->findParentProductByVariantId($data);
        if (!$parentProduct || !$parentProduct->getId()) {
            return $data;
        }

        $shouldSaveConfigurableProduct = $this->_updateConfigurableAttributes($parentProduct, $data);
        if ($shouldSaveConfigurableProduct) {
            $this->productRepository->save($parentProduct);
            $parentProduct->unsetData('_cache_instance_used_product_attributes');
            $parentProduct->unsetData('_cache_instance_used_attributes');
            $parentProduct->unsetData('_cache_instance_configurable_attributes');
        }
        $productAttributes = $parentProduct->getTypeInstance()->getUsedProductAttributes($parentProduct);
        $variants = $this->dataHelper->getDataVariants($data);

        foreach ($productAttributes as $productAttribute) {
            /** @var Attribute $productAttribute */
            $code = $productAttribute->getAttributeCode();
            if (!isset($variants[$code])) {
                continue;
            }

            $value = $data[$code];
            if ($value) {
                continue;
            }

            if (!$productAttribute->usesSource()) {
                continue;
            }

            $productAttribute->setStoreId(Store::DEFAULT_STORE_ID);
            $optionId = $productAttribute->getSource()->getOptionId(Data::EMPTY_VALUE_PLACEHOLDER);
            if (!$optionId) {
                continue;
            }

            $data[$code] = $optionId;
        }
        return $data;
    }

    /**
     * Add new variant attributes to configurable products
     *
     * @param Mcm $subject
     * @param Product $parentProduct
     * @param array $data
     * @param Category $category
     * @param int|null $unlinkProductId
     * @return null
     * @throws LocalizedException
     */
    public function beforeProcessParentProduct(
        Mcm $subject,
        $parentProduct,
        $data,
        $category,
        $unlinkProductId = null
    ) {
        if (!$parentProduct) {
            return null;
        }

        if ($groupTitle = $parentProduct->getGroupTitle()) {
            $parentProduct->setName($groupTitle);
        }

        $this->_updateConfigurableAttributes($parentProduct, $data);
        return null;
    }

    /**
     * @param Product $parentProduct
     * @param array $data
     * @return bool
     * @throws LocalizedException
     */
    protected function _updateConfigurableAttributes(Product $parentProduct, $data): bool
    {
        $shouldSaveConfigurableProduct = false;

        /** @var Configurable $configurableType */
        $configurableType = $parentProduct->getTypeInstance();
        $productAttributes = $configurableType->getUsedProductAttributes($parentProduct);
        $productAttributeCodes = array_map(
            function ($attr) {
                return $attr->getAttributeCode();
            },
            $productAttributes
        );

        $variants = $this->dataHelper->getDataVariants($data);
        $allSetAttributes = $this->getAllSetAttributesByAttributeSetId((int)$parentProduct->getAttributeSetId());
        $currentSimpleSku = !empty($data['mirakl-product-sku']) ? $data['mirakl-product-sku'] : null;

        foreach ($variants as $key => $value) {
            if (!$value) {
                continue;
            }

            if (in_array($key, $productAttributeCodes)) {
                continue;
            }

            if (!in_array($key, $allSetAttributes)) {
                continue;
            }

            /** @var Attribute $newProductAttribute */
            $newProductAttribute = $this->attributeRepository->get($key);
            if (!$newProductAttribute->usesSource()) {
                continue;
            }

            $newProductAttribute->setStoreId(Store::DEFAULT_STORE_ID);
            $emptyValueOptionId = $newProductAttribute->getSource()->getOptionId(Data::EMPTY_VALUE_PLACEHOLDER);
            if (!$emptyValueOptionId) {
                continue;
            }

            $extensionAttributes = $parentProduct->getExtensionAttributes();
            $options = $extensionAttributes->getConfigurableProductOptions();
            if (!$options) {
                $options = [];
            }

            $newAttributesData = [];
            $values = [];
            foreach (explode(',', $value) as $singleValue) {
                $values[$singleValue] = [
                    'include'     => 1,
                    'value_index' => $singleValue,
                ];
            }

            $attrId = $newProductAttribute->getId();
            $newAttributesData[$attrId] = [
                'attribute_id' => $attrId,
                'code'         => $newProductAttribute->getAttributeCode(),
                'label'        => $newProductAttribute->getStoreLabel(),
                'values'       => $values,
            ];
            $newOptions = $this->optionsFactory->create($newAttributesData);
            $options = array_merge($options, $newOptions);

            $extensionAttributes->setConfigurableProductOptions($options);
            $parentProduct->setExtensionAttributes($extensionAttributes);
            $shouldSaveConfigurableProduct = true;

            $usedProductCollection = $configurableType->getUsedProductCollection($parentProduct)
                ->addAttributeToSelect($key);
            /** @var Product $usedProduct */
            foreach ($usedProductCollection as $usedProduct) {
                $optionId = $emptyValueOptionId;
                $currentValue = $usedProduct->getData($key);

                if (!empty($currentSimpleSku) && $currentSimpleSku == $usedProduct->getSku()) {
                    $newOptionId = array_key_first($values);
                    if (empty($newOptionId) || (!empty($currentValue) && $newOptionId == $currentValue)) {
                        continue;
                    }
                    $optionId = $newOptionId;
                } elseif (!empty($currentValue)) {
                    continue;
                }

                $usedProduct->setData($key, $optionId);
                $this->productRepository->save($usedProduct);
                $this->indexer->setIdsToIndex($usedProduct);
            }
        }

        $statusRemoveUnusedSuperAttributes = $this->findAndRemoveUnusedSuperAttributes($parentProduct);

        return $shouldSaveConfigurableProduct ?: $statusRemoveUnusedSuperAttributes;
    }

    /**
     * Find And Remove Unused Super Attributes
     *
     * @param Product $parentProduct
     * @return bool
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function findAndRemoveUnusedSuperAttributes(Product $parentProduct): bool
    {
        $configurableType = $parentProduct->getTypeInstance();
        $productAttributes = $configurableType->getUsedProductAttributes($parentProduct);
        $usedProductCollection = $configurableType->getUsedProductCollection($parentProduct);

        foreach ($productAttributes as $attr) {
            $usedProductCollection->addAttributeToSelect($attr->getAttributeCode());
        }

        $allSetAttributes = $this->getAllSetAttributesByAttributeSetId((int)$parentProduct->getAttributeSetId());

        $unusedAttributeKeys = [];
        $cachingAttributeEmptyValue = [];

        /** @var Product $usedProduct */
        foreach ($usedProductCollection as $usedProduct) {
            foreach ($productAttributes as $attr) {
                $key = $attr->getAttributeCode();
                if (!isset($unusedAttributeKeys[$key])) {
                    $unusedAttributeKeys[$key] = false;
                }

                if (!empty($unusedAttributeKeys[$key])) {
                    continue;
                }

                if (!in_array($key, $allSetAttributes)) {
                    continue;
                }

                if (array_key_exists($key, $cachingAttributeEmptyValue)) {
                    $optionId = $cachingAttributeEmptyValue[$key];
                } else {
                    /** @var ProductAttribute $productAttribute */
                    $productAttribute = $this->attributeRepository->get($key);
                    if (!$productAttribute->usesSource()) {
                        continue;
                    }

                    $productAttribute->setStoreId(Store::DEFAULT_STORE_ID);
                    $optionId = $productAttribute->getSource()->getOptionId(Data::EMPTY_VALUE_PLACEHOLDER);
                    if (!$optionId) {
                        continue;
                    }
                    $cachingAttributeEmptyValue[$key] = $optionId;
                }

                $attributeValue = $usedProduct->getData($key);
                if (!empty($attributeValue) && $attributeValue != $optionId) {
                    $unusedAttributeKeys[$key] = true;
                }
            }
        }

        $unusedAttributeKeys = array_filter($unusedAttributeKeys, function ($v, $k) {
            return empty($v);
        }, ARRAY_FILTER_USE_BOTH);

        if (empty($unusedAttributeKeys)) {
            return false;
        }

        $extensionAttributes = $parentProduct->getExtensionAttributes();
        $options = $extensionAttributes->getConfigurableProductOptions();
        if (!$options) {
            return false;
        }

        $cacheInstanceUsedAttributes = $parentProduct->getData('_cache_instance_used_attributes');

        foreach ($productAttributes as $attrKey => $attr) {
            if (!isset($unusedAttributeKeys[$attr->getAttributeCode()])) {
                continue;
            }

            $attrId = $attr->getAttributeId();

            foreach ($options as $optionId => $option) {
                if ($option->getAttributeId() == $attrId) {
                    unset($options[$optionId]);
                    break;
                }
            }

            unset($productAttributes[$attrKey]);
            unset($cacheInstanceUsedAttributes[$attrKey]);
            unset($unusedAttributeKeys[$attr->getAttributeCode()]);

            if (empty($unusedAttributeKeys)) {
                break;
            }
        }

        $extensionAttributes->setConfigurableProductOptions($options);
        $parentProduct->setExtensionAttributes($extensionAttributes);
        $parentProduct->setData('_cache_instance_used_product_attributes', $productAttributes);
        $parentProduct->setData('_cache_instance_used_attributes', $cacheInstanceUsedAttributes);

        return true;
    }

    /**
     * @param Mcm $subject
     * @param Product|null $result
     * @return Product|null
     * @throws NoSuchEntityException
     */
    public function afterFindParentProductByVariantId(
        Mcm $subject,
        ?Product $result
    ): ?Product {
        if ($result && $result->getId()) {
            $result = $this->productRepository->getById($result->getId(), false, null, true);
        }
        return $result;
    }

    /**
     * Get All Set Attributes By Attribute Set Id
     *
     * @param int $getAttributeSetId
     * @return array
     */
    private function getAllSetAttributesByAttributeSetId(int $getAttributeSetId): array
    {
        if (!empty($this->allSetAttributes) && array_key_exists($getAttributeSetId, $this->allSetAttributes)) {
            return $this->allSetAttributes[$getAttributeSetId];
        }

        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter('attribute_set_id', $getAttributeSetId)
            ->create();
        $attributes = $this->attributeRepository->getList($searchCriteria)->getItems();
        foreach ($attributes as $attribute) {
            $this->allSetAttributes[$getAttributeSetId][] = $attribute->getAttributeCode();
        }

        return $this->allSetAttributes[$getAttributeSetId];
    }
}
