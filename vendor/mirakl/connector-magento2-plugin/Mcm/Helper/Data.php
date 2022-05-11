<?php
namespace Mirakl\Mcm\Helper;

use Magento\Catalog\Model\Product;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;
use Mirakl\Mci\Helper\Data as MciHelper;

class Data extends MciHelper
{
    const CSV_MIRAKL_PRODUCT_ID                 = 'mirakl-product-id';
    const CSV_MIRAKL_PRODUCT_SKU                = 'mirakl-product-sku';
    const CSV_MIRAKL_VARIANT_GROUP_CODE         = 'variant_group_code';
    const ATTRIBUTE_MIRAKL_PRODUCT_SKU          = 'sku';
    const ATTRIBUTE_MIRAKL_PRODUCT_ID           = 'mirakl_mcm_product_id';
    const ATTRIBUTE_MIRAKL_IS_OPERATOR_MASTER   = 'mirakl_mcm_is_operator_master';
    const ATTRIBUTE_MIRAKL_VARIANT_GROUP_CODE   = 'mirakl_mcm_variant_group_code';

    /**
     * Adds Mirakl product id to specified product
     *
     * @param   DataObject  $product
     * @param   string      $miraklProductId
     * @return  DataObject
     */
    public function addProductMiraklProductId(DataObject $product, $miraklProductId)
    {
        // Throw exception if shop's SKU contains forbidden chars
        $this->validateMiraklProductId($miraklProductId);

        $product->setData(self::ATTRIBUTE_MIRAKL_PRODUCT_ID, $miraklProductId);

        return $product;
    }

    /**
     * Tries to find a simple product by Mirakl product id
     *
     * @param   string  $miraklProductId
     * @return  Product|null
     */
    public function findSimpleProductByDeduplication($miraklProductId)
    {
        $product = null;

        if (!empty($miraklProductId)) {
            $product = $this->findProductByAttribute(
                self::ATTRIBUTE_MIRAKL_PRODUCT_ID, $miraklProductId, Product\Type::TYPE_SIMPLE
            );
        }

        return $product;
    }

    /**
     * Tries to find a simple product by Mirakl product sku
     *
     * @param   string  $miraklProductSku
     * @return  Product|null
     */
    public function findProductBySku($miraklProductSku)
    {
        $product = null;

        if (!empty($miraklProductSku)) {
            $product = $this->findProductByAttribute(
                self::ATTRIBUTE_MIRAKL_PRODUCT_SKU, $miraklProductSku, Product\Type::TYPE_SIMPLE
            );
        }

        return $product;
    }

    /**
     * Verify that provided Mirakl Product Id does not contain a forbidden char
     *
     * @param   string  $miraklProductId
     * @return  $this
     * @throws  LocalizedException
     */
    public function validateMiraklProductId($miraklProductId)
    {
        foreach ($this->_forbiddenChars as $char) {
            if (false !== strpos($miraklProductId, $char)) {
                throw new LocalizedException(__('Invalid Mirakl Product Id specified, char %1 is not allowed.', $char));
            }
        }

        return $this;
    }

    /**
     * @param   string  $variantId
     * @param   string  $type
     * @return  \Magento\Catalog\Model\ResourceModel\Product\Collection
     */
    public function findProductsByVariantId($variantId, $type = null)
    {
        $variantId = $this->cleanVariantId($variantId);

        $collection = $this->productCollectionFactory->create();
        $collection->setStore(0);
        $collection->addAttributeToSelect('*');
        $collection->addAttributeToFilter(self::ATTRIBUTE_MIRAKL_VARIANT_GROUP_CODE, $variantId);

        if ($type) {
            $collection->addFieldToFilter('type_id', $type);
        }

        return $collection;
    }

    /**
     * @param   string  $variantId
     * @param   string  $type
     * @return  Product|null
     */
    public function findMcmProductByVariantId($variantId, $type = null)
    {
        $collection = $this->findProductsByVariantId($variantId, $type);

        return $collection->count() ? $collection->getFirstItem() : null;
    }

    /**
     * {@inheritdoc}
     */
    public function addProductVariantId(DataObject $product, $variantId)
    {
        $variantId = $this->cleanVariantId($variantId);
        $product->setData(self::ATTRIBUTE_MIRAKL_VARIANT_GROUP_CODE, $variantId);

        return $product;
    }
}
