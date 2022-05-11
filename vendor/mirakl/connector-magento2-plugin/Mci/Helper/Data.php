<?php
namespace Mirakl\Mci\Helper;

use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ProductFactory;
use Magento\Catalog\Model\ResourceModel\Eav\Attribute as EavAttribute;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\Catalog\Model\ResourceModel\ProductFactory as ProductResourceFactory;
use Magento\Eav\Model\Config as EavConfig;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DataObject;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\Exception\LocalizedException;

class Data extends AbstractHelper
{
    // Fake attributes to send in PM01 to ease sellers products import
    const ATTRIBUTE_SKU                 = 'shop_sku';
    const ATTRIBUTE_CATEGORY            = 'category';
    const ATTRIBUTE_VARIANT_GROUP_CODE  = 'variant_group_code';

    // Real Magento attributes created for Mirakl
    const ATTRIBUTE_ATTR_SET            = 'mirakl_attr_set_id';
    const ATTRIBUTE_SHOPS_SKUS          = 'mirakl_shops_skus';
    const ATTRIBUTE_VARIANT_GROUP_CODES = 'mirakl_variant_group_codes';
    const ATTRIBUTE_IMAGE_PREFIX        = 'mirakl_image_';

    // Separators used for multiple values attributes
    const MULTIVALUES_VALUE_SEPARATOR   = '|';
    const MULTIVALUES_PAIR_SEPARATOR    = ',';

    /**
     * List of forbidden chars for multiple values attributes
     *
     * @var array
     */
    protected $_forbiddenChars = [
        self::MULTIVALUES_VALUE_SEPARATOR,
        self::MULTIVALUES_PAIR_SEPARATOR,
    ];

    /**
     * @var ResourceConnection
     */
    protected $resource;

    /**
     * @var AdapterInterface
     */
    protected $connection;

    /**
     * @var EavConfig
     */
    protected $eavConfig;

    /**
     * @var ProductFactory
     */
    protected $productFactory;

    /**
     * @var ProductResourceFactory
     */
    protected $productResourceFactory;

    /**
     * @var ProductCollectionFactory
     */
    protected $productCollectionFactory;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var array|null
     */
    protected $imagesAttributes;

    /**
     * @param   Context                     $context
     * @param   ResourceConnection          $resource
     * @param   EavConfig                   $eavConfig
     * @param   ProductFactory              $productFactory
     * @param   ProductResourceFactory      $productResourceFactory
     * @param   ProductCollectionFactory    $productCollectionFactory
     * @param   Config                      $config
     */
    public function __construct(
        Context $context,
        ResourceConnection $resource,
        EavConfig $eavConfig,
        ProductFactory $productFactory,
        ProductResourceFactory $productResourceFactory,
        ProductCollectionFactory $productCollectionFactory,
        Config $config
    ) {
        parent::__construct($context);
        $this->resource                 = $resource;
        $this->connection               = $resource->getConnection();
        $this->eavConfig                = $eavConfig;
        $this->productFactory           = $productFactory;
        $this->productResourceFactory   = $productResourceFactory;
        $this->productCollectionFactory = $productCollectionFactory;
        $this->config                   = $config;
    }

    /**
     * Adds shop sku to specified product (format is shop_id1|shop_sku1,shop_id2|shop_sku2)
     *
     * @param   DataObject  $product
     * @param   string      $shopId
     * @param   string      $sku
     * @return  DataObject
     */
    public function addProductShopSku(DataObject $product, $shopId, $sku)
    {
        // Throw exception if shop's SKU contains forbidden chars
        $this->validateShopSku($sku);

        $attrCode = self::ATTRIBUTE_SHOPS_SKUS;

        return $this->addProductShopAttributeMultiValues($product, $shopId, $attrCode, $sku);
    }

    /**
     * Adds shop variant group code to specified product (format is shop_id1|variant_id1,shop_id2|variant_id2)
     *
     * @param   DataObject  $product
     * @param   string      $shopId
     * @param   string      $variantId
     * @return  DataObject
     */
    public function addProductShopVariantId(DataObject $product, $shopId, $variantId)
    {
        $attrCode = self::ATTRIBUTE_VARIANT_GROUP_CODES;
        $variantId = $this->cleanVariantId($variantId);

        return $this->addProductShopAttributeMultiValues($product, $shopId, $attrCode, $variantId);
    }

    /**
     * Assembles product shop multi values
     *
     * @param   DataObject  $product
     * @param   string      $shopId
     * @param   string      $attrCode
     * @param   string      $value
     * @return  DataObject
     */
    private function addProductShopAttributeMultiValues(DataObject $product, $shopId, $attrCode, $value)
    {
        $values = [];
        $pairSeparator = self::MULTIVALUES_PAIR_SEPARATOR;
        $valueSeparator = self::MULTIVALUES_VALUE_SEPARATOR;
        if ($product->getData($attrCode)) {
            $values = explode($pairSeparator, $product->getData($attrCode));
        }
        $values[] = $shopId . $valueSeparator . $value;

        $product->setData($attrCode, implode($pairSeparator, array_unique($values)));

        return $product;
    }

    /**
     * @param   string  $variantId
     * @return  string
     */
    public function cleanVariantId($variantId)
    {
        // Remove forbidden chars from variant id
        foreach ($this->_forbiddenChars as $char) {
            $variantId = str_replace($char, '', $variantId);
        }

        return $variantId;
    }

    /**
     * Tries to find a product by specified attribute with exact value matching
     *
     * @param   string      $attrCode
     * @param   mixed       $value
     * @param   string|null $type
     * @return  Product|null
     */
    public function findProductByAttribute($attrCode, $value, $type = null)
    {
        $collection = $this->productCollectionFactory->create()
            ->addAttributeToSelect('*')
            ->addAttributeToFilter($attrCode, $value);

        if ($type) {
            $collection->addFieldToFilter('type_id', $type);
        }

        $collection->setPage(1, 1); // Limit to 1 result

        if ($collection->count()) {
            return $collection->getFirstItem()->setStoreId(0);
        }

        return null;
    }

    /**
     * Tries to find a product by specified attribute with multi values
     *
     * @param   string      $attrCode
     * @param   string      $value
     * @param   string      $separator
     * @param   string|null $type
     * @return  Product|null
     */
    public function findProductByMultiValues($attrCode, $value, $separator, $type = null)
    {
        $allValuesToQuery = explode($separator, $value);
        foreach ($allValuesToQuery as $valueToQuery) {
            $condition = [
                ['eq'   => $valueToQuery],
                ['like' => "%,$valueToQuery,%"], // Spaces on each side
                ['like' => "%,$valueToQuery"], // Space before and ends with $needle
                ['like' => "$valueToQuery,%"] // Starts with needle and space after
            ];
            if ($product = $this->findProductByAttribute($attrCode, $condition, $type)) {
                return $product;
            }
        }

        return null;
    }

    /**
     * Tries to find a product by shop sku
     *
     * @param   string  $shopId
     * @param   string  $sku
     * @return  Product|null
     */
    public function findProductByShopSku($shopId, $sku)
    {
        $product = null;
        $attrCode = self::ATTRIBUTE_SHOPS_SKUS;
        $productResource = $this->productResourceFactory->create();

        if ($attribute = $productResource->getAttribute($attrCode)) {
            $shopSku = $shopId . self::MULTIVALUES_VALUE_SEPARATOR . $sku;
            $valueTable = $attribute->getBackendTable();
            $entityCol = \Mirakl\Core\Helper\Data::isEnterprise() ? 'row_id' : 'entity_id';

            $select = $this->productCollectionFactory->create()
                ->getSelect()
                ->columns('e.entity_id')
                ->join(['v' => $valueTable], "v.$entityCol = e.$entityCol", '')
                ->where('v.attribute_id = ?', $attribute->getId())
                ->where(new \Zend_Db_Expr("CONCAT(',', CONCAT(value, ',')) LIKE ?"), "%,$shopSku,%")
                ->limit(1);

            $productData = $this->connection->fetchRow($select);
            if ($productData && count($productData)) {
                $product = $this->productFactory->create();
                $product->setStoreId(0);
                $productResource->load($product, $productData['entity_id']);
            }
        }

        return $product;
    }

    /**
     * Tries to find a product by shop variant id
     *
     * @param   string      $shopId
     * @param   string      $variantId
     * @param   string|null $type
     * @return  Product|null
     */
    public function findProductByVariantId($shopId, $variantId, $type = null)
    {
        $product = null;

        $attrCode = self::ATTRIBUTE_VARIANT_GROUP_CODES;
        $productResource = $this->productResourceFactory->create();

        if ($attribute = $productResource->getAttribute($attrCode)) {
            $shopVariantId = $shopId . self::MULTIVALUES_VALUE_SEPARATOR . $this->cleanVariantId($variantId);
            $valueTable = $attribute->getBackendTable();
            $entityCol = \Mirakl\Core\Helper\Data::isEnterprise() ? 'row_id' : 'entity_id';

            $select = $this->productCollectionFactory->create()
                ->getSelect()
                ->columns('e.entity_id')
                ->join(['v' => $valueTable], "v.$entityCol = e.$entityCol", '')
                ->where('v.attribute_id = ?', $attribute->getId())
                ->where(new \Zend_Db_Expr("CONCAT(',', CONCAT(v.value, ',')) LIKE ?"), "%,$shopVariantId,%")
                ->limit(1);

            if ($type) {
                $select->where('e.type_id = ?', $type);
            }

            $productData = $this->connection->fetchRow($select);
            if ($productData && count($productData)) {
                $product = $this->productFactory->create();
                $product->setStoreId(0);
                $productResource->load($product, $productData['entity_id']);
            }
        }

        return $product;
    }

    /**
     * @return  \Magento\Eav\Model\ResourceModel\Entity\Attribute\Collection
     */
    private function getAttributeCollection()
    {
        return $this->eavConfig->getEntityType('catalog_product')->getAttributeCollection();
    }

    /**
     * Returns Mirakl images special attributes, starting by mirakl_image_*
     *
     * @return  EavAttribute[]
     */
    public function getImagesAttributes()
    {
        if (null === $this->imagesAttributes) {
            $this->imagesAttributes = [];
            foreach ($this->getAttributeCollection()->getItems() as $attribute) {
                /** @var EavAttribute $attribute */
                if (self::isAttributeImage($attribute)) {
                    $this->imagesAttributes[$attribute->getAttributeCode()] = $attribute;
                }
            }
        }

        uksort($this->imagesAttributes, function($a, $b) {
            if ($a == $b) {
                return 0;
            }

            $start = strlen(self::ATTRIBUTE_IMAGE_PREFIX);
            $val1 = (int) substr($a, $start);
            $val2 = (int) substr($b, $start);

            return $val1 > $val2 ? 1 : -1;
        });

        return $this->imagesAttributes;
    }

    /**
     * Returns product's URL key and set it to product's SKU if Magento fails giving a nice one
     *
     * @param   Product $product
     * @return  string
     */
    public function getProductUrlKey(Product $product)
    {
        $urlKey = $product->formatUrlKey($product->getName());
        if (empty($urlKey) || is_numeric($urlKey)) {
            $urlKey = $product->getSku();
        }

        return $urlKey;
    }

    /**
     * Returns true if specified attribute is for image import, false otherwise
     *
     * @param   DataObject  $attribute
     * @return  bool
     */
    public static function isAttributeImage(DataObject $attribute)
    {
        /** @var EavAttribute $attribute */
        return 0 === strpos($attribute->getAttributeCode(), self::ATTRIBUTE_IMAGE_PREFIX);
    }

    /**
     * Returns product attributes flagged as variant (mirakl_is_variant attribute)
     *
     * @return  EavAttribute[]
     */
    public function getVariantAttributes()
    {
        $attributes = [];
        foreach ($this->getAttributeCollection()->getItems() as $attribute) {
            /** @var EavAttribute $attribute */
            if ($attribute->getData('mirakl_is_variant')) {
                $attributes[$attribute->getAttributeCode()] = $attribute;
            }
        }

        return $attributes;
    }

    /**
     * Removes product shop multi values
     *
     * @param   DataObject  $product
     * @param   string      $shopId
     * @param   string      $attrCode
     * @param   string      $value
     * @return  DataObject
     */
    private function removeProductShopAttributeMultiValues(DataObject $product, $shopId, $attrCode, $value)
    {
        $values = [];
        $pairSeparator = self::MULTIVALUES_PAIR_SEPARATOR;
        $valueSeparator = self::MULTIVALUES_VALUE_SEPARATOR;
        if ($product->getData($attrCode)) {
            $values = explode($pairSeparator, $product->getData($attrCode));
        }
        if (false !== ($pos = array_search($shopId . $valueSeparator . $value, $values))) {
            unset($values[$pos]);
            $product->setData($attrCode, implode($pairSeparator, $values));
        }

        return $product;
    }

    /**
     * Removes shop sku from specified product (format is shop_id1|shop_sku1,shop_id2|shop_sku2)
     *
     * @param   DataObject  $product
     * @param   string      $shopId
     * @param   string      $sku
     * @return  DataObject
     */
    public function removeProductShopSku(DataObject $product, $shopId, $sku)
    {
        $attrCode = self::ATTRIBUTE_SHOPS_SKUS;

        return $this->removeProductShopAttributeMultiValues($product, $shopId, $attrCode, $sku);
    }

    /**
     * Verify that provided shop's SKU does not contain a forbidden char
     *
     * @param   string  $shopSku
     * @return  $this
     * @throws  LocalizedException
     */
    public function validateShopSku($shopSku)
    {
        foreach ($this->_forbiddenChars as $char) {
            if (false !== strpos($shopSku, $char)) {
                throw new LocalizedException(__('Invalid SKU specified, char %1 is not allowed.', $char));
            }
        }

        return $this;
    }
}
