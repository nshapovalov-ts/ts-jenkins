<?php
namespace Mirakl\Mci\Helper\Product\Import;

use Magento\Catalog\Model\Product as ProductModel;
use Magento\Catalog\Model\ProductFactory;
use Magento\ConfigurableProduct\Helper\Product\Options\Factory as OptionsFactory;
use Magento\Store\Model\StoreManagerInterface;
use Mirakl\Mci\Helper\Config;
use Mirakl\Mci\Helper\Data as MciHelper;
use Mirakl\Mci\Helper\Product\Import\Category as CategoryHelper;
use Mirakl\Mci\Helper\Product\Import\Inventory as InventoryHelper;

class Product
{
    use ProductTrait;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var ProductFactory
     */
    protected $productFactory;

    /**
     * @var OptionsFactory
     */
    protected $optionsFactory;

    /**
     * @var MciHelper
     */
    protected $mciHelper;

    /**
     * @var CategoryHelper
     */
    protected $categoryHelper;

    /**
     * @var InventoryHelper
     */
    protected $inventoryHelper;

    /**
     * @param   Config                  $config
     * @param   StoreManagerInterface   $storeManager
     * @param   ProductFactory          $productFactory
     * @param   OptionsFactory          $optionsFactory
     * @param   MciHelper               $mciHelper
     * @param   CategoryHelper          $categoryHelper
     * @param   InventoryHelper         $inventoryHelper
     */
    public function __construct(
        Config $config,
        StoreManagerInterface $storeManager,
        ProductFactory $productFactory,
        OptionsFactory $optionsFactory,
        MciHelper $mciHelper,
        CategoryHelper $categoryHelper,
        InventoryHelper $inventoryHelper
    ) {
        $this->config          = $config;
        $this->storeManager    = $storeManager;
        $this->productFactory  = $productFactory;
        $this->optionsFactory  = $optionsFactory;
        $this->mciHelper       = $mciHelper;
        $this->categoryHelper  = $categoryHelper;
        $this->inventoryHelper = $inventoryHelper;
    }

    /**
     * @param   ProductModel    $product
     * @param   string          $shopId
     * @param   array           $data
     * @return  ProductModel
     */
    public function addProductShopVariantId(ProductModel $product, $shopId, array $data)
    {
        // Update the variant group code list of product
        $attrCode = MciHelper::ATTRIBUTE_VARIANT_GROUP_CODE;
        if ($attrCode && isset($data[$attrCode])) {
            $this->mciHelper->addProductShopVariantId($product, $shopId, $data[$attrCode]);
        }

        return $product;
    }

    /**
     * @param   ProductModel    $product
     */
    protected function flagProduct(ProductModel $product)
    {
        // Flag product as synchronizable for a potential P21 usage later
        if ($this->config->isAutoSyncProduct()) {
            $product->setMiraklSync(1);
        }
    }

    /**
     * Removes potential unique deduplication attributes from parent
     *
     * @param   array   $data
     */
    public function removeDeduplicationAttributesFromParent(&$data)
    {
        unset($data[MciHelper::ATTRIBUTE_VARIANT_GROUP_CODES]);
    }
}
