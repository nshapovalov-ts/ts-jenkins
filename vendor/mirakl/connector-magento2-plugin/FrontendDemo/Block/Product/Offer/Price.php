<?php
namespace Mirakl\FrontendDemo\Block\Product\Offer;

use Magento\Catalog\Model\Product;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\View\Element\Template;
use Magento\Tax\Model\CalculationFactory as TaxCalculationFactory;
use Mirakl\Connector\Helper\Config as ConnectorConfig;
use Mirakl\Connector\Model\Offer;
use Mirakl\FrontendDemo\Helper\Tax as TaxHelper;

/**
 * @method  Offer   getOffer
 * @method  $this   setOffer(Offer $offer)
 * @method  Product getProduct
 * @method  $this   setProduct(Product $product)
 */
class Price extends Template
{
    /**
     * @var string
     */
    protected $_template = 'product/offer/price.phtml';

    /**
     * @var ConnectorConfig
     */
    protected $connectorConfig;

    /**
     * @var TaxHelper
     */
    protected $taxHelper;

    /**
     * @var TaxCalculationFactory
     */
    protected $taxCalculationFactory;

    /**
     * @var float
     */
    protected $taxRate;

    /**
     * @var PriceCurrencyInterface
     */
    protected $priceCurrency;

    /**
     * @var bool
     */
    protected $displayShippingPrice = true;

    /**
     * @param   Template\Context        $context
     * @param   ConnectorConfig         $connectorConfig
     * @param   TaxHelper               $taxHelper
     * @param   TaxCalculationFactory   $taxCalculationFactory
     * @param   PriceCurrencyInterface  $priceCurrency
     * @param   array                   $data
     */
    public function __construct(
        Template\Context $context,
        ConnectorConfig $connectorConfig,
        TaxHelper $taxHelper,
        TaxCalculationFactory $taxCalculationFactory,
        PriceCurrencyInterface $priceCurrency,
        array $data = []
    ) {
        parent::__construct($context, $data);

        $this->connectorConfig       = $connectorConfig;
        $this->taxHelper             = $taxHelper;
        $this->taxCalculationFactory = $taxCalculationFactory;
        $this->priceCurrency         = $priceCurrency;
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
     * @return  bool
     */
    public function getDisplayShippingPrice()
    {
        return $this->displayShippingPrice;
    }

    /**
     * @return  float
     */
    public function getOfferPriceExclTax()
    {
        $price = $this->getOffer()->getPrice();
        if (!$this->connectorConfig->getOffersIncludeTax()) {
            return $price;
        }

        return $this->taxHelper->getPriceExclTax($price, $this->getProduct()->getTaxClassId());
    }

    /**
     * @return  float
     */
    public function getOfferPriceInclTax()
    {
        $price = $this->getOffer()->getPrice();
        if ($this->connectorConfig->getOffersIncludeTax()) {
            return $price;
        }

        return $this->taxHelper->getPriceInclTax($price, $this->getProduct()->getTaxClassId());
    }

    /**
     * @return  float
     */
    public function getOfferOriginPriceExclTax()
    {
        $price = $this->getOffer()->getOriginPrice();
        if (!$this->connectorConfig->getOffersIncludeTax()) {
            return $price;
        }

        return $this->taxHelper->getPriceExclTax($price, $this->getProduct()->getTaxClassId());
    }

    /**
     * @return  float
     */
    public function getOfferOriginPriceInclTax()
    {
        $price = $this->getOffer()->getOriginPrice();
        if ($this->connectorConfig->getOffersIncludeTax()) {
            return $price;
        }

        return $this->taxHelper->getPriceInclTax($price, $this->getProduct()->getTaxClassId());
    }

    /**
     * @return  float
     */
    public function getOfferMinShippingPriceExclTax()
    {
        $price = $this->getOffer()->getMinShippingPrice();
        if (!$this->connectorConfig->getShippingPricesIncludeTax()) {
            return $price;
        }

        return $this->taxHelper->getShippingPriceExclTax($price);
    }

    /**
     * @return  float
     */
    public function getOfferMinShippingPriceInclTax()
    {
        $price = $this->getOffer()->getMinShippingPrice();
        if ($this->connectorConfig->getShippingPricesIncludeTax()) {
            return $price;
        }

        return $this->taxHelper->getShippingPriceInclTax($price);
    }

    /**
     * @param   float   $price
     * @return  float
     */
    public function getPriceExclTax($price)
    {
        if (!$this->connectorConfig->getOffersIncludeTax()) {
            return $price;
        }

        return $this->taxHelper->getPriceExclTax($price, $this->getProduct()->getTaxClassId());
    }

    /**
     * @param   float   $price
     * @return  float
     */
    public function getPriceInclTax($price)
    {
        if ($this->connectorConfig->getOffersIncludeTax()) {
            return $price;
        }

        return $this->taxHelper->getPriceInclTax($price, $this->getProduct()->getTaxClassId());
    }

    /**
     * @return  string
     */
    public function getPriceRangesHtml()
    {
        /** @var PriceRanges $block */
        $block = $this->getLayout()->createBlock(PriceRanges::class);

        return $block->setProduct($this->getProduct())
            ->setOffer($this->getOffer())
            ->toHtml();
    }

    /**
     * @param   bool    $flag
     * @return  $this
     */
    public function setDisplayShippingPrice($flag)
    {
        $this->displayShippingPrice = (bool) $flag;

        return $this;
    }
}
