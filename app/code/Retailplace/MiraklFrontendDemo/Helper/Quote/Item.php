<?php
namespace Retailplace\MiraklFrontendDemo\Helper\Quote;

use Magento\Quote\Model\Quote as Quote;
use Magento\Quote\Model\Quote\Item as QuoteItem;
use Mirakl\MMP\Front\Domain\Collection\Shipping\ShippingFeeTypeCollection;
use Mirakl\FrontendDemo\Helper\Quote\Item as HelperQuoteItem;
use Mirakl\Connector\Model\Quote\Updater as QuoteUpdater;

class Item extends HelperQuoteItem
{
    /**
     * @var array
     */
    private $shippingTypes = [];

    /**
     * @var QuoteUpdater
     */
    protected $quoteUpdater;

    /**
     * @param QuoteItem $item
     * @param Quote\Address|null $shippingAddress
     * @return  ShippingFeeTypeCollection
     */
    public function getItemShippingTypes(QuoteItem $item, $shippingAddress = null)
    {
        static $renderedShippingTypes = [];

        if (null === $shippingAddress) {
            $shippingAddress = $item->getQuote()->getShippingAddress();
        }

        $shippingTypes = $this->quoteUpdater->getItemShippingTypes($item, $shippingAddress);

        if (!empty($shippingTypes)) {
            $this->shippingTypes[$item->getMiraklOfferId()] = $shippingTypes;
        }

        if (in_array($shippingTypes, $renderedShippingTypes, true)) {
            return new ShippingFeeTypeCollection();
        }

        $renderedShippingTypes[] = $shippingTypes;

        return $shippingTypes;
    }

    /**
     * Check Exist Shipping Type By Item
     * @param QuoteItem $item
     * @return bool
     */
    public function checkExistShippingTypeByItem(QuoteItem $item): bool
    {

        $shippingType = $this->shippingTypes[$item->getMiraklOfferId()];
        if (empty($shippingType)) {
            return false;
        }
        $items = $shippingType->getItems();
        if (empty($items)) {
            return false;
        }

        return true;
    }
}
