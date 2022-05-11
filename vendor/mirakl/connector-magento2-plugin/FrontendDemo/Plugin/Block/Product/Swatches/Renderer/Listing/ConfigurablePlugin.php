<?php
namespace Mirakl\FrontendDemo\Plugin\Block\Product\Swatches\Renderer\Listing;

use Magento\Catalog\Model\Product;
use Magento\ConfigurableProduct\Block\Product\View\Type\Configurable;
use Magento\Framework\Json;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Tax\Helper\Data as TaxHelper;
use Mirakl\Connector\Helper\Config as ConnectorConfig;
use Mirakl\Connector\Model\Offer;
use Mirakl\FrontendDemo\Helper\Offer as OfferHelper;
use Mirakl\FrontendDemo\Helper\Tax as MiraklTaxHelper;

class ConfigurablePlugin
{
    /**
     * @var OfferHelper
     */
    protected $offerHelper;

    /**
     * @var TaxHelper
     */
    protected $taxHelper;

    /**
     * @var TaxHelper
     */
    protected $miraklTaxHelper;

    /**
     * @var ConnectorConfig
     */
    protected $connectorConfig;

    /**
     * @var Json\DecoderInterface
     */
    protected $jsonDecoder;

    /**
     * @var Json\EncoderInterface
     */
    protected $jsonEncoder;

    /**
     * @var PriceCurrencyInterface
     */
    protected $priceCurrency;

    /**
     * @param   OfferHelper             $offerHelper
     * @param   TaxHelper               $taxHelper
     * @param   MiraklTaxHelper         $miraklTaxHelper
     * @param   ConnectorConfig         $connectorConfig
     * @param   Json\DecoderInterface   $jsonDecoder
     * @param   Json\EncoderInterface   $jsonEncoder
     * @param   PriceCurrencyInterface  $priceCurrency
     */
    public function __construct(
        OfferHelper $offerHelper,
        TaxHelper $taxHelper,
        MiraklTaxHelper $miraklTaxHelper,
        ConnectorConfig $connectorConfig,
        Json\DecoderInterface $jsonDecoder,
        Json\EncoderInterface $jsonEncoder,
        PriceCurrencyInterface $priceCurrency
    ) {
        $this->offerHelper     = $offerHelper;
        $this->taxHelper       = $taxHelper;
        $this->miraklTaxHelper = $miraklTaxHelper;
        $this->connectorConfig = $connectorConfig;
        $this->jsonDecoder     = $jsonDecoder;
        $this->jsonEncoder     = $jsonEncoder;
        $this->priceCurrency   = $priceCurrency;
    }

    /**
     * @param   Configurable    $subject
     * @param   \Closure        $proceed
     * @return  string
     */
    public function aroundGetJsonConfig(Configurable $subject, \Closure $proceed)
    {
        $jsonConfig = $proceed();

        $config = $this->jsonDecoder->decode($jsonConfig);

        $store = $subject->getCurrentStore();
        $config['template'] = str_replace('%s',
            '<span class="offer-price-format"><%- data.price %></span>',
            $store->getCurrentCurrency()->getOutputFormat()
        );

        $product = $subject->getProduct();

        if ($this->offerHelper->hasAvailableOffersForProduct($product)) {
            $product->setRequiredOptions(0); // needed to force add to cart URL in form
        }

        if ($bestProduct = $this->offerHelper->getBestOperatorOffer($product)) {
            // Use best product
            $priceInfo = $bestProduct->getPriceInfo();
            $config['prices']['oldPrice']['amount']   = (float) $priceInfo->getPrice('regular_price')->getAmount()->getValue();
            $config['prices']['basePrice']['amount']  = (float) $priceInfo->getPrice('final_price')->getAmount()->getBaseAmount();
            $config['prices']['finalPrice']['amount'] = (float) $priceInfo->getPrice('final_price')->getAmount()->getValue();

        } elseif ($bestOffer = $this->offerHelper->getBestOffer($product)) {
            // Use best offer
            $config['prices']['oldPrice']['amount']   = (float) $this->convertPrice($bestOffer->getOriginPrice());
            $config['prices']['basePrice']['amount']  = (float) $this->convertPrice($bestOffer->getOriginPrice());

            if ($this->taxHelper->displayPriceIncludingTax() || $this->taxHelper->displayBothPrices()) {
                $finalPrice = $this->convertPrice($this->getOfferPriceInclTax($bestOffer, $product->getTaxClassId()));
            } else {
                $finalPrice = $this->convertPrice($bestOffer->getPrice());
            }

            $config['prices']['finalPrice']['amount'] = (float) $finalPrice;
        }

        // Handle configurable products
        $this->setOffersData($config, $subject->getAllowProducts());

        return $this->jsonEncoder->encode($config);
    }

    /**
     * @param   float   $value
     * @return  float
     */
    public function convertPrice($value)
    {
        return $this->priceCurrency->convert($value);
    }

    /**
     * @param   Offer   $offer
     * @return  float
     */
    protected function getOfferMinShippingPriceInclTax(Offer $offer)
    {
        $price = $offer->getMinShippingPrice();
        if ($this->connectorConfig->getShippingPricesIncludeTax()) {
            return $price;
        }

        return $this->miraklTaxHelper->getShippingPriceInclTax($price);
    }

    /**
     * @param   Offer   $offer
     * @param   int     $taxClassId
     * @return  float
     */
    protected function getOfferPriceInclTax(Offer $offer, $taxClassId)
    {
        $price = $offer->getPrice();
        if ($this->connectorConfig->getOffersIncludeTax()) {
            return $price;
        }

        return $this->miraklTaxHelper->getPriceInclTax($price, $taxClassId);
    }

    /**
     * @param   array   $config
     * @param   array   $allowProducts
     */
    protected function setOffersData(array &$config, array $allowProducts)
    {
        /** @var Product $product */
        foreach ($allowProducts as $product) {
            /** @var Offer $offer */
            $offer = $this->offerHelper->getBestOffer($product);
            $this->setOptionPrice($config, $product, $offer);
        }
    }

    /**
     * Set option price in array format
     *
     * @param   array   $config
     * @param   Product $product
     * @param   Offer   $offer
     * @return  mixed
     */
    protected function setOptionPrice(array &$config, $product, $offer = null)
    {
        if ($offer) {
            $config['optionPrices'][$product->getId()] = [
                'oldPrice'                => ['amount' => $this->convertPrice($offer->getOriginPrice())],
                'basePrice'               => ['amount' => $this->convertPrice($offer->getPrice())],
                'minShippingPrice'        => ['amount' => $this->convertPrice($offer->getMinShippingPrice())],
                'minShippingPriceInclTax' => ['amount' => $this->convertPrice($this->getOfferMinShippingPriceInclTax($offer))],
                'tierPrices'              => [],
            ];

            if ($this->taxHelper->displayPriceExcludingTax()) {
                $finalPrice = $this->convertPrice($offer->getPrice());
            } else {
                $finalPrice = $this->convertPrice($this->getOfferPriceInclTax($offer, $product->getTaxClassId()));
            }
            $config['optionPrices'][$product->getId()]['finalPrice']['amount'] = $finalPrice;

            $shop = $offer->getShop();
            $offerData = [
                'priceAdditionalInfo'  => $offer->getPriceAdditionalInfo(),
                'conditionLabel'       => $offer->getConditionName(),
                'stock'                => $offer->getQuantity(),
                'type'                 => 'offer',
                'offerId'              => $offer->getId(),
                'productSku'           => $product->getSku(),
                'soldBy'               => $offer->getShopName(),
                'soldByUrl'            => $shop->getUrl(),
                'shopEvaluationsCount' => $shop->getEvaluationsCount(),
                'shopEvaluation'       => $shop->getFormattedGrade(),
            ];
        } else {
            $offerData = [
                'type'       => 'product',
                'productSku' => $product->getSku(),
                'soldBy'     => $this->offerHelper->getStoreName($product),
            ];
        }

        $config['optionPrices'][$product->getId()]['offerData'] = $offerData;
    }
}