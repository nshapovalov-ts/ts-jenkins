<?php
namespace Mirakl\FrontendDemo\Block\Cart\Item;

use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Quote\Model\Quote\Item as QuoteItem;
use Mirakl\Connector\Helper\Offer as OfferHelper;
use Mirakl\Connector\Model\Offer;
use Mirakl\Connector\Model\OfferFactory;
use Mirakl\Connector\Model\Quote\Updater as QuoteUpdater;
use Mirakl\Core\Model\Shop;
use Mirakl\MMP\Common\Domain\Shipping\ShippingType;
use Mirakl\MMP\Front\Domain\Collection\Shipping\ShippingFeeTypeCollection;

class Shipping extends Template
{
    /**
     * @var string
     */
    protected $_template = 'checkout/cart/item/shipping.phtml';

    /**
     * @var QuoteItem
     */
    protected $_item;

    /**
     * @var PriceCurrencyInterface
     */
    protected $priceCurrency;

    /**
     * @var OfferHelper
     */
    protected $offerHelper;

    /**
     * @var QuoteUpdater
     */
    protected $quoteUpdater;

    /**
     * @var OfferFactory
     */
    protected $offerFactory;

    /**
     * @param   Context                 $context
     * @param   PriceCurrencyInterface  $priceCurrency
     * @param   OfferHelper             $offerHelper
     * @param   QuoteUpdater            $quoteUpdater
     * @param   OfferFactory            $offerFactory
     * @param   array                   $data
     */
    public function __construct(
        Context $context,
        PriceCurrencyInterface $priceCurrency,
        OfferHelper $offerHelper,
        QuoteUpdater $quoteUpdater,
        OfferFactory $offerFactory,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->priceCurrency = $priceCurrency;
        $this->offerHelper = $offerHelper;
        $this->quoteUpdater = $quoteUpdater;
        $this->offerFactory = $offerFactory;
    }

    /**
     * @param   float   $value
     * @param   bool    $includeContainer
     * @return  string
     */
    public function formatPrice($value, $includeContainer = false)
    {
        return $this->priceCurrency->convertAndFormat($value, $includeContainer);
    }

    /**
     * Get quote item
     *
     * @return  QuoteItem
     */
    public function getItem()
    {
        return $this->_item;
    }

    /**
     * @return  Offer|null
     */
    public function getOffer()
    {
        $item = $this->getItem();
        /** @var \Magento\Quote\Model\Quote\Item\Option $offerCustomOption */
        if ($offerCustomOption = $item->getProduct()->getCustomOption('mirakl_offer')) {
            return $this->offerFactory->fromJson($offerCustomOption->getValue());
        }

        return null;
    }

    /**
     * @return  ShippingType
     */
    public function getSelectedShippingType()
    {
        $item = $this->getItem();

        if ($shippingTypeCode = $item->getMiraklShippingType()) {
            return $this->quoteUpdater->getItemShippingTypeByCode($item, $shippingTypeCode);
        }

        return $this->quoteUpdater->getItemSelectedShippingType($item);
    }

    /**
     * Retrieve available shipping types of given quote item
     *
     * @return  ShippingFeeTypeCollection
     */
    public function getShippingTypes()
    {
        static $renderedShippingTypes = [];

        $shippingTypes = $this->quoteUpdater->getItemShippingTypes($this->getItem());

        if (in_array($shippingTypes, $renderedShippingTypes, true)) {
            return new ShippingFeeTypeCollection();
        }

        $renderedShippingTypes[] = $shippingTypes;

        return $shippingTypes;
    }

    /**
     * @return  Shop|null
     */
    public function getShop()
    {
        $offer = $this->getOffer();

        return $offer ? $this->offerHelper->getOfferShop($offer) : null;
    }

    /**
     * Set quote item
     *
     * @param   QuoteItem   $item
     * @return  $this
     */
    public function setItem(QuoteItem $item)
    {
        $this->_item = $item;

        return $this;
    }

    /**
     * @return  string
     */
    protected function _toHtml()
    {
        if (!$this->_item) {
            return '';
        }

        return parent::_toHtml();
    }
}
