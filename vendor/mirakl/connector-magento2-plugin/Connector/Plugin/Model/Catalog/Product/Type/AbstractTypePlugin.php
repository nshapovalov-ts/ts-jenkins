<?php
namespace Mirakl\Connector\Plugin\Model\Catalog\Product\Type;

use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Type\AbstractType;
use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable as ConfigurableType;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\SecurityViolationException;
use Magento\Framework\Message\ManagerInterface;
use Mirakl\Connector\Model\Offer;
use Mirakl\Connector\Model\OfferFactory;
use Mirakl\Connector\Model\ResourceModel\OfferFactory as OfferResourceFactory;

class AbstractTypePlugin
{
    /**
     * @var OfferFactory
     */
    protected $offerFactory;

    /**
     * @var OfferResourceFactory
     */
    protected $offerResourceFactory;

    /**
     * @var StockRegistryInterface
     */
    protected $stockRegistry;

    /**
     * @var ManagerInterface
     */
    private $messageManager;

    /**
     * @param   OfferFactory            $offerFactory
     * @param   OfferResourceFactory    $offerResourceFactory
     * @param   StockRegistryInterface  $stockRegistry
     * @param   ManagerInterface        $messageManager
     */
    public function __construct(
        OfferFactory $offerFactory,
        OfferResourceFactory $offerResourceFactory,
        StockRegistryInterface $stockRegistry,
        ManagerInterface $messageManager
    ) {
        $this->offerFactory = $offerFactory;
        $this->offerResourceFactory = $offerResourceFactory;
        $this->stockRegistry = $stockRegistry;
        $this->messageManager = $messageManager;
    }

    /**
     * Add Mirakl offer id if present in request
     *
     * @param   DataObject  $buyRequest
     * @param   Product     $product
     * @throws  SecurityViolationException
     */
    protected function addMiraklOffer(DataObject $buyRequest, Product $product)
    {
        if ($offerId = $buyRequest->getData('offer_id')) {
            $superAttribute = $buyRequest->getData('super_attribute');
            $offer = $this->offerFactory->create();
            $this->offerResourceFactory->create()->load($offer, $offerId);

            if ($product->getTypeId() == ConfigurableType::TYPE_CODE || empty($superAttribute)) {
                /**
                 * Add the Mirakl offer as a custom option on configurable product
                 * or on the simple product if it's not a variant
                 */
                if (!$this->checkAssociation($offer, $product, $buyRequest)) {
                    throw new SecurityViolationException(__('An error happens when adding the product to the cart. Please reload the page and try again.'));
                }

                $product->addCustomOption('mirakl_offer', $offer->toJson());
            }

            if ($product->getTypeId() == 'simple') {
                /**
                 * Update stock item qty in order to validate the requested qty in
                 * @see \Magento\CatalogInventory\Model\StockStateProvider::checkQty()
                 */
                $stockItem = $this->stockRegistry->getStockItem($product->getId());
                $stockItem->setQty($offer->getQty());
            }
        }
    }

    /**
     * @param   Offer       $offer
     * @param   Product     $product
     * @param   DataObject  $buyRequest
     * @return  bool
     */
    protected function checkAssociation($offer, $product, $buyRequest)
    {
        if ($product->getTypeId() == ConfigurableType::TYPE_CODE) {
            /** @var ConfigurableType $productType */
            $productType = $product->getTypeInstance();
            $child = $productType->getProductByAttributes($buyRequest['super_attribute'], $product);

            return $child && $child->getSku() == $offer->getProductSku();
        }

        return $offer->getProductSku() == $product->getSku();
    }

    /**
     * Process product configuration
     *
     * @param   AbstractType    $abstractType
     * @param   DataObject      $buyRequest
     * @param   Product         $product
     * @throws  \UnexpectedValueException
     */
    public function beforeProcessConfiguration(AbstractType $abstractType, DataObject $buyRequest, Product $product)
    {
        $this->addMiraklOffer($buyRequest, $product);
    }

    /**
     * Initialize product(s) for add to cart process.
     * Advanced version of func to prepare product for cart.
     *
     * @param   AbstractType    $abstractType
     * @param   DataObject      $buyRequest
     * @param   Product         $product
     * @throws  \UnexpectedValueException
     */
    public function beforePrepareForCartAdvanced(AbstractType $abstractType, DataObject $buyRequest, Product $product)
    {
        $this->addMiraklOffer($buyRequest, $product);
    }
}
