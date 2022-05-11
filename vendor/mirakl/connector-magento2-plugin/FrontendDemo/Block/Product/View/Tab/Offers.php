<?php
namespace Mirakl\FrontendDemo\Block\Product\View\Tab;

use Magento\Catalog\Block\Product\Context;
use Magento\Catalog\Block\Product\View\AbstractView;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ResourceModel\Eav\Attribute;
use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\CatalogInventory\Api\StockStateInterface;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Framework\DataObject\IdentityInterface;
use Magento\Framework\Json\EncoderInterface as JsonEncoderInterface;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\Registry;
use Magento\Framework\Stdlib\ArrayUtils;
use Mirakl\Connector\Helper\StockQty as StockQtyHelper;
use Mirakl\Connector\Model\Offer;
use Mirakl\FrontendDemo\Block\Product\OfferQuantityTrait;
use Mirakl\FrontendDemo\Block\Product\Offer\Price;
use Mirakl\FrontendDemo\Helper\Config;
use Mirakl\FrontendDemo\Helper\Offer as OfferHelper;

class Offers extends AbstractView implements IdentityInterface
{
    use OfferQuantityTrait;

    /**
     * @var Registry
     */
    protected $coreRegistry;

    /**
     * @var Config
     */
    protected $configHelper;

    /**
     * @var OfferHelper
     */
    protected $offerHelper;

    /**
     * @var JsonEncoderInterface
     */
    protected $jsonEncoder;

    /**
     * @var PriceCurrencyInterface
     */
    protected $priceCurrency;

    /**
     * @var StockStateInterface
     */
    protected $stockState;

    /**
     * @var StockRegistryInterface
     */
    protected $stockRegistry;

    /**
     * @var StockQtyHelper
     */
    protected $stockQtyHelper;

    /**
     * @var Product[]
     */
    protected $usedProducts;

    /**
     * @param   Context                 $context
     * @param   ArrayUtils              $arrayUtils
     * @param   OfferHelper             $offerHelper
     * @param   Config                  $configHelper
     * @param   JsonEncoderInterface    $jsonEncoder
     * @param   PriceCurrencyInterface  $priceCurrency
     * @param   StockStateInterface     $stockState
     * @param   StockRegistryInterface  $stockRegistry
     * @param   StockQtyHelper          $stockQtyHelper
     * @param   array                   $data
     */
    public function __construct(
        Context $context,
        ArrayUtils $arrayUtils,
        OfferHelper $offerHelper,
        Config $configHelper,
        JsonEncoderInterface $jsonEncoder,
        PriceCurrencyInterface $priceCurrency,
        StockStateInterface $stockState,
        StockRegistryInterface $stockRegistry,
        StockQtyHelper $stockQtyHelper,
        $data = []
    ) {
        parent::__construct($context, $arrayUtils, $data);

        $this->coreRegistry   = $context->getRegistry();
        $this->offerHelper    = $offerHelper;
        $this->configHelper   = $configHelper;
        $this->jsonEncoder    = $jsonEncoder;
        $this->priceCurrency  = $priceCurrency;
        $this->stockState     = $stockState;
        $this->stockRegistry  = $stockRegistry;
        $this->stockQtyHelper = $stockQtyHelper;

        $this->setTabTitle();
    }

    /**
     * Get offer helper
     *
     * @return  OfferHelper
     */
    public function getOfferHelper()
    {
        return $this->offerHelper;
    }

    /**
     * Get current product
     *
     * @return  null|Product
     */
    public function getProduct()
    {
        return $this->coreRegistry->registry('product');
    }

    /**
     * Set tab title
     *
     * @return  void
     */
    public function setTabTitle()
    {
        $this->setTitle(__('All Offers'));
    }

    /**
     * Format price value
     *
     * @param   float   $amount
     * @param   bool    $includeContainer
     * @param   int     $precision
     * @return  float
     */
    public function formatCurrency(
        $amount,
        $includeContainer = true,
        $precision = PriceCurrencyInterface::DEFAULT_PRECISION
    ) {
        return $this->priceCurrency->convertAndFormat($amount, $includeContainer, $precision);
    }

    /**
     * Return HTML code
     *
     * @return  string
     */
    public function toHtml()
    {
        if ($this->offerHelper->hasAvailableOffersForProduct($this->getProduct())) {
            return parent::toHtml();
        }

        return '';
    }

    /**
     * Retrieve configurable attributes of current product
     *
     * @return  Attribute[]
     */
    public function getConfigurableAttributes()
    {
        if (!$this->isConfigurableProduct()) {
            return [];
        }

        /** @var \Magento\ConfigurableProduct\Model\Product\Type\Configurable $productType */
        $productType = $this->getProduct()->getTypeInstance();

        return $productType->getUsedProductAttributes($this->getProduct());
    }

    /**
     * Return unique ID(s) for each object in system
     *
     * @return  array
     */
    public function getIdentities()
    {
        return ['offers_block'];
    }

    /**
     * @param   Offer   $offer
     * @return  string
     */
    public function getOfferPriceHtml(Offer $offer)
    {
        /** @var Price $block */
        $block = $this->getLayout()->createBlock(Price::class);

        return $block->setProduct($this->getProduct())
            ->setOffer($offer)
            ->toHtml();
    }

    /**
     * @param   int         $productId
     * @param   Attribute   $attribute
     * @return  string
     */
    public function getProductAttributeValue($productId, Attribute $attribute)
    {
        if (!isset($this->getUsedProducts()[$productId])) {
            return '';
        }

        $product = $this->getUsedProducts()[$productId];

        return $product->getAttributeText($attribute->getAttributeCode());
    }

    /**
     * Get all operator offers with associated stock
     *
     * @return  array
     */
    public function getOperatorOffers()
    {
        $operatorOffers = [];

        if ($this->isConfigurableProduct()) {
            /** @var \Magento\ConfigurableProduct\Model\Product\Type\Configurable $productType */
            $productType = $this->getProduct()->getTypeInstance();
            $products = $productType->getUsedProducts($this->getProduct());
        } else {
            $products = [$this->getProduct()];
        }

        /** @var Product $product */
        foreach ($products as $product) {
            $productStockQty = $this->stockQtyHelper->getProductStockQty($product);
            if ($product->isSaleable() && $productStockQty > 0) {
                $operatorOffers[$product->getId()]['offer'] = $product;
                $operatorOffers[$product->getId()]['stock'] = $productStockQty;
            }
        }

        return $operatorOffers;
    }

    /**
     * Get store name
     *
     * @return  string
     */
    public function getStoreName()
    {
        return $this->offerHelper->getStoreName($this->getProduct());
    }

    /**
     * Get all offers
     *
     * @param   int|array   $excludeOfferIds
     * @return  array
     */
    public function getAllOffers($excludeOfferIds = null)
    {
        return $this->offerHelper->getAllOffers($this->getProduct(), $excludeOfferIds);
    }

    /**
     * Retrieve product qty increments
     *
     * @param   Product $product
     * @return  float|false
     */
    public function getProductQtyIncrements(Product $product)
    {
        if (!$product->isSaleable()) {
            return false;
        }

        $stockItem = $this->stockRegistry->getStockItem(
            $product->getId(),
            $product->getStore()->getWebsiteId()
        );

        return $stockItem->getQtyIncrements();
    }

    /**
     * @return  Product[]
     */
    protected function getUsedProducts()
    {
        if (null === $this->usedProducts) {
            $this->usedProducts = [];

            if ($this->isConfigurableProduct()) {
                /** @var \Magento\ConfigurableProduct\Model\Product\Type\Configurable $productType */
                $productType = $this->getProduct()->getTypeInstance();
                $productType->setStoreFilter(null, $this->getProduct());

                $attrCodes = array_map(function ($attribute) {
                    /** @var Attribute $attribute */
                    return $attribute->getAttributeCode();
                }, $this->getConfigurableAttributes());

                $this->usedProducts = $productType->getUsedProductCollection($this->getProduct())
                    ->addAttributeToSelect($attrCodes)
                    ->getItems();
            }
        }

        return $this->usedProducts;
    }

    /**
     * Is a product configurable
     *
     * @return  bool
     */
    public function isConfigurableProduct()
    {
        return $this->getProduct()->getTypeId() == Configurable::TYPE_CODE;
    }
}
