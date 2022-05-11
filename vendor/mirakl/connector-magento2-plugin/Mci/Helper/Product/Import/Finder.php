<?php
namespace Mirakl\Mci\Helper\Product\Import;

use Magento\Catalog\Model\Product as ProductModel;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Mirakl\Mci\Helper\Config;
use Mirakl\Mci\Helper\Data as MciHelper;

class Finder
{
    /**
     * @var Config
     */
    protected $config;

    /**
     * @var MciHelper
     */
    protected $mciHelper;

    /**
     * @param   Config      $config
     * @param   MciHelper   $mciHelper
     */
    public function __construct(
        Config $config,
        MciHelper $mciHelper
    ) {
        $this->config    = $config;
        $this->mciHelper = $mciHelper;
    }

    /**
     * @param   string  $shopId
     * @param   array   $data
     * @return  ProductModel|null
     */
    public function findParentProductByVariantId($shopId, array $data)
    {
        $parentProduct = null;

        $attrCode = MciHelper::ATTRIBUTE_VARIANT_GROUP_CODE;
        if ($attrCode && isset($data[$attrCode]) && strlen($data[$attrCode])) {
            $parentProduct = $this->mciHelper->findProductByVariantId(
                $shopId, $data[$attrCode], Configurable::TYPE_CODE
            );
        }

        return $parentProduct;
    }

    /**
     * @param   array       $data
     * @param   string|null $type
     * @return  ProductModel|null
     */
    public function findProductByDeduplication(array $data, $type = null)
    {
        /** @var ProductModel $product */
        $product = null;

        $separator = $this->config->getDeduplicationDelimiter();
        $deduplicationAttributes = $this->config->getDeduplicationAttributes();
        $deduplicateMultiValues = $this->config->isDeduplicationMultiValues();

        // Try to find product by attribute used for deduplication
        foreach ($deduplicationAttributes as $attrCode) {
            if (!isset($data[$attrCode]) || '' === $data[$attrCode]) {
                continue;
            }

            if ($product = $this->mciHelper->findProductByAttribute($attrCode, $data[$attrCode], $type)) {
                break;
            }

            if (!$deduplicateMultiValues) {
                continue;
            }

            if ($product = $this->mciHelper->findProductByMultiValues($attrCode, $data[$attrCode], $separator, $type)) {
                break;
            }
        }

        return $product;
    }
}
