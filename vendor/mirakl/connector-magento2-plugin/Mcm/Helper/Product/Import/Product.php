<?php
namespace Mirakl\Mcm\Helper\Product\Import;

use Magento\Catalog\Model\Product as ProductModel;
use Magento\Catalog\Model\ProductFactory;
use Magento\ConfigurableProduct\Helper\Product\Options\Factory as OptionsFactory;
use Magento\Store\Model\StoreManagerInterface;
use Mirakl\Mci\Helper\Data as MciHelper;
use Mirakl\Mci\Helper\Product\Import\Category as CategoryHelper;
use Mirakl\Mci\Helper\Product\Import\ProductTrait;
use Mirakl\Mcm\Helper\Config;
use Mirakl\Mcm\Helper\Data as McmHelper;
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
     * @var McmHelper
     */
    protected $mcmHelper;

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
     * @param   McmHelper               $mcmHelper
     * @param   CategoryHelper          $categoryHelper
     * @param   InventoryHelper         $inventoryHelper
     */
    public function __construct(
        Config $config,
        StoreManagerInterface $storeManager,
        ProductFactory $productFactory,
        OptionsFactory $optionsFactory,
        MciHelper $mciHelper,
        McmHelper $mcmHelper,
        CategoryHelper $categoryHelper,
        InventoryHelper $inventoryHelper
    ) {
        $this->config          = $config;
        $this->storeManager    = $storeManager;
        $this->productFactory  = $productFactory;
        $this->optionsFactory  = $optionsFactory;
        $this->mciHelper       = $mciHelper;
        $this->mcmHelper       = $mcmHelper;
        $this->categoryHelper  = $categoryHelper;
        $this->inventoryHelper = $inventoryHelper;
    }

    /**
     * @param   ProductModel    $product
     * @param   array           $data
     * @return  ProductModel
     * @throws  \Exception
     */
    public function addProductVariantId(ProductModel $product, array $data)
    {
        // Update the variant group code list of product
        $attrCode = McmHelper::ATTRIBUTE_VARIANT_GROUP_CODE;
        if ($attrCode && isset($data[$attrCode])) {
            $product = $this->mcmHelper->addProductVariantId($product, $data[$attrCode]);
        }

        return $product;
    }

    /**
     * @param   ProductModel    $product
     */
    protected function flagProduct(ProductModel $product)
    {
        $product->setMiraklSync(1);
        $product->setData(McmHelper::ATTRIBUTE_MIRAKL_IS_OPERATOR_MASTER, false);
    }

    /**
     * Removes potential unique deduplication attributes from parent
     *
     * @param   array   $data
     */
    public function removeDeduplicationAttributesFromParent(&$data)
    {
        unset($data[McmHelper::ATTRIBUTE_VARIANT_GROUP_CODE]);
    }
}
