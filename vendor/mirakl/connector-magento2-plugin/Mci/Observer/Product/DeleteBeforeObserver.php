<?php
namespace Mirakl\Mci\Observer\Product;

use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ResourceModel\ProductFactory as ProductResourceFactory;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Mirakl\Mci\Helper\Data as MciHelper;
use Mirakl\Mci\Helper\Hash as HashHelper;

class DeleteBeforeObserver implements ObserverInterface
{
    /**
     * @var HashHelper
     */
    protected $hashHelper;

    /**
     * @var ProductResourceFactory
     */
    protected $productResourceFactory;

    /**
     * @param   HashHelper              $hashHelper
     * @param   ProductResourceFactory  $productResourceFactory
     */
    public function __construct(HashHelper $hashHelper, ProductResourceFactory $productResourceFactory)
    {
        $this->hashHelper = $hashHelper;
        $this->productResourceFactory = $productResourceFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function execute(Observer $observer)
    {
        /** @var \Magento\Catalog\Model\Product $product */
        $product = $observer->getEvent()->getProduct();

        // Delete hashes associated to shop skus of deleted product
        if ($shopSkus = $this->getShopsSkus($product)) {
            $shopSkus = explode(MciHelper::MULTIVALUES_PAIR_SEPARATOR, $shopSkus);
            foreach ($shopSkus as $shopSku) {
                list ($shopId, $sku) = explode(MciHelper::MULTIVALUES_VALUE_SEPARATOR, $shopSku);
                $this->hashHelper->deleteShopHash($shopId, $sku);
            }
        }
    }

    /**
     * @param   Product $product
     * @return  mixed
     */
    private function getShopsSkus(Product $product)
    {
        return $this->productResourceFactory->create()->getAttributeRawValue(
            $product->getId(),
            MciHelper::ATTRIBUTE_SHOPS_SKUS,
            \Magento\Store\Model\Store::DEFAULT_STORE_ID
        );
    }
}
