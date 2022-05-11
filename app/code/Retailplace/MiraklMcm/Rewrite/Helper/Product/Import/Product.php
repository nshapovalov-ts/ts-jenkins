<?php
/**
 * Retailplace_MiraklMcm
 *
 * @copyright   Copyright (c) 2022 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Alexander Korsun <aleksandr@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\MiraklMcm\Rewrite\Helper\Product\Import;

use Magento\Catalog\Model\Category as CategoryModel;
use Magento\Catalog\Model\Product as ProductModel;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable as ConfigurableType;
use Magento\Store\Api\Data\StoreInterface;
use Mirakl\Mcm\Helper\Data as McmHelper;
use Magento\Catalog\Model\ResourceModel\Eav\Attribute;
use Magento\Framework\Exception\NotFoundException;

class Product extends \Mirakl\Mcm\Helper\Product\Import\Product
{
    /**
     * @var StoreInterface[]
     */
    private $stores;

    /**
     * @var Attribute[]
     */
    private $variantAttributesBySetId = [];

    /**
     * Associates a simple child product to a parent configurable product
     *
     * @param ProductModel $parent
     * @param ProductModel $child
     * @param int|null $unlinkProductId
     */
    public function associateProducts(ProductModel $parent, ProductModel $child, $unlinkProductId = null)
    {
        $this->associateProductIds($parent, [$child->getId()], $unlinkProductId);
    }

    /**
     * Associates specified simple product ids to a parent configurable product
     *
     * @param ProductModel $parent
     * @param array $childrenIds
     * @param int|null $unlinkProductId
     */
    public function associateProductIds(ProductModel $parent, array $childrenIds, $unlinkProductId = null)
    {
        if ($parent->getTypeId() == ConfigurableType::TYPE_CODE) {
            /** @var ConfigurableType $productType */
            $productType = $parent->getTypeInstance();
            $associatedProductIds = $productType->getUsedProductCollection($parent)->getColumnValues('entity_id');
            $associatedProductIds = array_unique(array_merge($associatedProductIds, $childrenIds));
            if ($unlinkProductId) {
                $k = array_search($unlinkProductId, $associatedProductIds);
                if (false !== $k) {
                    unset($associatedProductIds[$k]);
                }
            }
            $parent->setAssociatedProductIds($associatedProductIds);
        }
    }

    /**
     * Creates and initializes a configurable product
     *
     * @param CategoryModel $category
     * @param array $data
     * @return  ProductModel
     * @throws NotFoundException
     */
    public function createConfigurableProduct(CategoryModel $category, array $data)
    {
        // Remove potential unique deduplication attributes from parent
        $data = array_diff_key($data, array_flip($this->config->getDeduplicationAttributes()));
        $this->removeDeduplicationAttributesFromParent($data);

        // Create the product
        $parentProduct = $this->createProduct(
            ConfigurableType::TYPE_CODE,
            $category,
            $data,
            ['use_config_manage_stock' => 1, 'is_in_stock' => 1]
        );

        // Force visibility on configurable
        $parentProduct->setVisibility(ProductModel\Visibility::VISIBILITY_BOTH);

        // Initialize configurable options for variant products
        $this->updateProductConfigurableOptions($parentProduct, $data);

        return $parentProduct;
    }

    /**
     * Creates and initializes a product according to arguments
     *
     * @param string $type
     * @param CategoryModel $category
     * @param array $data
     * @param array $stockData
     * @return  ProductModel
     * @throws NotFoundException
     */
    private function createProduct($type, CategoryModel $category, array $data, array $stockData = []): ProductModel
    {
        /** @var ProductModel $product */
        $product = $this->productFactory->create();
        $product->addData($data);
        $this->initProduct($product, $category, $type);
        $product->setStockData($stockData);

        return $product;
    }

    /**
     * Creates and initializes a simple product
     *
     * @param CategoryModel $category
     * @param array $data
     * @return  ProductModel
     * @throws NotFoundException
     */
    public function createSimpleProduct(CategoryModel $category, array $data): ProductModel
    {
        /** @var ProductModel $product */
        $product = $this->createProduct(
            ProductModel\Type::TYPE_SIMPLE,
            $category,
            $data,
            ['use_config_manage_stock' => 1, 'is_in_stock' => 0, 'qty' => 0, 'type_id' => 'simple']
        );

        $this->inventoryHelper->createSourceItems($product);

        return $product;
    }

    /**
     * Returns stores used for product import
     *
     * @return  StoreInterface[]
     */
    public function getStores(): array
    {
        if (null === $this->stores) {
            $this->stores = $this->config->getStoresUsedForProductImport();
        }

        return $this->stores;
    }

    /**
     * Returns website ids to enable for product import
     *
     * @return  array
     */
    private function getWebsiteIds(): array
    {
        $websiteIds = [];
        if ($this->storeManager->isSingleStoreMode()) {
            $websiteIds[] = $this->storeManager->getDefaultStoreView()->getWebsiteId();
        } else {
            foreach ($this->getStores() as $store) {
                if ($websiteId = $store->getWebsiteId()) {
                    $websiteIds[] = $websiteId;
                }
            }
        }

        return array_unique($websiteIds);
    }

    /**
     * Updates product configurable options in extension attributes
     *
     * @param ProductModel $parentProduct
     * @param array $data
     * @return  Product
     */
    private function updateProductConfigurableOptions(ProductModel $parentProduct, array $data): Product
    {
        if ($parentProduct->getTypeId() != ConfigurableType::TYPE_CODE) {
            return $this;
        }

        $attributes = $this->getVariantAttributes($parentProduct);
        if (empty($attributes)) {
            throw new \InvalidArgumentException(
                'No configurable attribute found for parent product creation: ' . $parentProduct->getName()
            );
        }

        $attributesData = [];
        foreach ($attributes as $attribute) {
            /** @var Attribute $attribute */
            $values = [];
            $attrCode = $attribute->getAttributeCode();

            // Handle case when value is empty or not present for this attribute
            if (!isset($data[$attrCode]) || '' === $data[$attrCode]) {
                if (!$attribute->getIsRequired()) {
                    continue; // value is empty for this product but not required, continue to next attribute
                } else {
                    throw new \InvalidArgumentException(
                        sprintf(
                            'Attribute "%s" is required but is empty for product "%s"',
                            $attrCode,
                            $parentProduct->getName()
                        )
                    );
                }
            }

            foreach (explode(',', $parentProduct->getData($attrCode)) as $value) {
                $values[$value] = [
                    'include'     => 1,
                    'value_index' => $value,
                ];
            }

            $attrId = $attribute->getId();
            $attributesData[$attrId] = [
                'attribute_id' => $attrId,
                'code'         => $attrCode,
                'label'        => $attribute->getStoreLabel(),
                'values'       => $values,
            ];
        }

        $options = $this->optionsFactory->create($attributesData);
        $extensionAttributes = $parentProduct->getExtensionAttributes();
        $extensionAttributes->setConfigurableProductOptions($options);
        $parentProduct->setExtensionAttributes($extensionAttributes);

        return $this;
    }

    /**
     * Initializes product default data
     *
     * @param ProductModel $product
     * @param CategoryModel $category
     * @param string $type
     * @throws NotFoundException
     */
    private function initProduct(ProductModel $product, CategoryModel $category, string $type = ProductModel\Type::TYPE_SIMPLE)
    {
        $product->setSku(substr(sha1(uniqid()), 0, 8))
            ->setTypeId($type)
            ->setStatus(Status::STATUS_DISABLED)
            ->setVisibility($this->config->getDefaultVisibility())
            ->setAttributeSetId($this->categoryHelper->getCategoryAttributeSet($category)->getId())
            ->setPrice(0)
            ->setStoreId(0)
            ->setWebsiteIds($this->getWebsiteIds());

        $miraklProductSku = $product->getData(McmHelper::CSV_MIRAKL_PRODUCT_SKU);
        if ($miraklProductSku && $type == ProductModel\Type::TYPE_SIMPLE) {
            $product->setSku($miraklProductSku);
        }

        $this->categoryHelper->addCategoryToProduct($product, $category);

        $product->setUrlKey($this->mciHelper->getProductUrlKey($product));

        // Enable product if configured
        if ($this->config->isAutoEnableProduct()) {
            $product->setStatus(Status::STATUS_ENABLED);
        }

        $this->flagProduct($product);
    }

    /**
     * Retrieve configurable attributes flagged as 'Is Variant' in Mirakl of specified product's attribute set
     *
     * @param ProductModel $product
     * @return  Attribute[]
     */
    private function getVariantAttributes(ProductModel $product)
    {
        if ($product->getTypeId() != ConfigurableType::TYPE_CODE) {
            return [];
        }

        $setId = $product->getAttributeSetId();
        if (!isset($this->variantAttributesBySetId[$setId])) {
            /** @var ConfigurableType $productType */
            $productType = $product->getTypeInstance();

            $setAttributes = [];
            /** @var Attribute $attribute */
            foreach ($productType->getSetAttributes($product) as $attribute) {
                if ($productType->canUseAttribute($attribute)) {
                    $setAttributes[$attribute->getAttributeCode()] = $attribute;
                }
            }

            $variantAttributes = $this->mciHelper->getVariantAttributes();
            $this->variantAttributesBySetId[$setId] = array_intersect_key($variantAttributes, $setAttributes);
        }

        return $this->variantAttributesBySetId[$setId];
    }
}
