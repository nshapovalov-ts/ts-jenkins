<?php
namespace Mirakl\FrontendDemo\Helper\Quote;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Quote\Model\Quote as Quote;
use Magento\Quote\Model\Quote\Item as QuoteItem;
use Mirakl\Connector\Model\Quote\Updater as QuoteUpdater;
use Mirakl\MMP\Front\Domain\Collection\Shipping\ShippingFeeTypeCollection;

class Item extends AbstractHelper
{
    /**
     * @var QuoteUpdater
     */
    protected $quoteUpdater;

    /**
     * @param   Context                 $context
     * @param   QuoteUpdater            $quoteUpdater
     */
    public function __construct(
        Context $context,
        QuoteUpdater $quoteUpdater
    ) {
        parent::__construct($context);
        $this->quoteUpdater = $quoteUpdater;
    }

    /**
     * @param   QuoteItem           $item
     * @param   Quote\Address|null  $shippingAddress
     * @return  ShippingFeeTypeCollection
     */
    public function getItemShippingTypes(QuoteItem $item, $shippingAddress = null)
    {
        static $renderedShippingTypes = [];

        if (null === $shippingAddress) {
            $shippingAddress = $item->getQuote()->getShippingAddress();
        }

        $shippingTypes = $this->quoteUpdater->getItemShippingTypes($item, $shippingAddress);

        if (in_array($shippingTypes, $renderedShippingTypes, true)) {
            return new ShippingFeeTypeCollection();
        }

        $renderedShippingTypes[] = $shippingTypes;

        return $shippingTypes;
    }
}
