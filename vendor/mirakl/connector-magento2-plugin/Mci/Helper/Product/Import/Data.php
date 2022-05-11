<?php
namespace Mirakl\Mci\Helper\Product\Import;

use Magento\Catalog\Model\Product as ProductModel;
use Magento\Catalog\Model\ResourceModel\Eav\Attribute as EavAttribute;
use Mirakl\Core\Helper\Data as CoreHelper;
use Mirakl\Mci\Helper\Config;
use Mirakl\Mci\Helper\Data as MciHelper;
use Mirakl\Mci\Helper\Product\Image;

class Data
{
    /**
     * @var Config
     */
    protected $config;

    /**
     * @var CoreHelper
     */
    protected $coreHelper;

    /**
     * @var MciHelper
     */
    protected $mciHelper;

    /**
     * @param   Config      $config
     * @param   CoreHelper  $coreHelper
     * @param   MciHelper   $mciHelper
     */
    public function __construct(
        Config $config,
        CoreHelper $coreHelper,
        MciHelper $mciHelper
    ) {
        $this->config     = $config;
        $this->coreHelper = $coreHelper;
        $this->mciHelper  = $mciHelper;
    }

    /**
     * Adds specified data to product excepting multi values
     *
     * @param   ProductModel    $product
     * @param   array           $data
     * @param   bool            $skipDedupAttrs
     * @return  ProductModel
     */
    public function addDataToProduct(ProductModel $product, array $data, $skipDedupAttrs = true)
    {
        // Update URL key if name has changed
        if (!empty($data['name']) && $product->getName() != $data['name']) {
            $product->setUrlKey($this->mciHelper->getProductUrlKey($product));
        }

        if (!$skipDedupAttrs) {
            // Update all attributes
            $product->addData($data);
        } else {
            // Update attributes excluding deduplication attributes
            $attributes = $this->config->getDeduplicationAttributes();
            $product->addData(array_diff_key($data, array_flip($attributes)));
        }

        return $product;
    }

    /**
     * Returns values of $data array that are flagged as variant attributes
     *
     * @param   array   $data
     * @return  array
     */
    public function getDataVariants(array $data)
    {
        return array_intersect_key($data, $this->mciHelper->getVariantAttributes());
    }

    /**
     * Returns true if given data have at least one variant (configurable attribute)
     *
     * @param   array   $data
     * @return  bool
     */
    public function isDataHaveVariants(array $data)
    {
        return isset($data[MciHelper::ATTRIBUTE_VARIANT_GROUP_CODE])
            && strlen($data[MciHelper::ATTRIBUTE_VARIANT_GROUP_CODE])
            && !empty($this->getDataVariants($data));
    }

    /**
     * Mark images as not processed if new
     *
     * @param   array               $data
     * @param   ProductModel|null   $product
     */
    public function updateProductImagesProcessingFlag(&$data, ProductModel $product = null)
    {
        $productExists = (bool) $product;
        $imageAttributes = $this->mciHelper->getImagesAttributes();
        foreach ($imageAttributes as $imageAttribute) {
            /** @var EavAttribute $imageAttribute */
            $attrCode = $imageAttribute->getAttributeCode();
            if (isset($data[$attrCode]) && $data[$attrCode]) {
                $testUrl = $this->coreHelper->addQueryParamToUrl($data[$attrCode], 'processed', 'true');
                if (!$productExists || $product->getData($attrCode) != $testUrl) {
                    $data[$attrCode] = $this->coreHelper->addQueryParamToUrl($data[$attrCode], 'processed', 'false');
                } else {
                    unset($data[$attrCode]);
                }
            } elseif ($productExists && $product->getData($attrCode)) {
                $data[$attrCode] = Image::DELETED_IMAGE_URL;
            }
        }
    }

    /**
     * Updates product data containing multi values
     *
     * @param   ProductModel    $product
     * @param   array           $data
     * @return  ProductModel
     */
    public function updateProductDataMultiValues(ProductModel $product, array $data)
    {
        $delimiter = $this->config->getDeduplicationDelimiter();
        $attributes = $this->config->getDeduplicationAttributes();
        foreach ($attributes as $attrCode) {
            if (isset($data[$attrCode]) && $data[$attrCode]) {
                $attrData = $product->getData($attrCode);
                $values = $attrData ? explode($delimiter, $attrData) : [];
                $values = array_merge($values, explode($delimiter, $data[$attrCode]));
                $values = array_map('trim', $values);
                $product->setData($attrCode, implode($delimiter, array_unique($values)));
            }
        }

        return $product;
    }
}
