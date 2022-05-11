<?php
namespace Mirakl\Connector\Plugin\Model\Carrier;

use Magento\OfflineShipping\Model\Carrier\Freeshipping;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Api\Data\CartItemInterface;
use Magento\Quote\Model\Quote\Address\RateRequest;
use Magento\Quote\Model\Quote\Item as QuoteItem;
use Magento\Shipping\Model\Rate\Result as RateResult;
use Mirakl\Connector\Helper\Quote as QuoteHelper;
use Mirakl\Connector\Model\Quote\Loader as QuoteLoader;

class FreeshippingPlugin
{
    /**
     * @var QuoteHelper
     */
    protected $quoteHelper;

    /**
     * @var QuoteLoader
     */
    protected $quoteLoader;

    /**
     * @param QuoteHelper $quoteHelper
     * @param QuoteLoader $quoteLoader
     */
    public function __construct(
        QuoteHelper $quoteHelper,
        QuoteLoader $quoteLoader
    ) {
        $this->quoteHelper = $quoteHelper;
        $this->quoteLoader = $quoteLoader;
    }

    /**
     * @param   Freeshipping    $subject
     * @param   \Closure        $proceed
     * @param   string          $field
     * @return  bool
     */
    public function aroundGetConfigFlag(Freeshipping $subject, \Closure $proceed, $field)
    {
        $quote = $this->quoteLoader->getQuote();

        if ($field == 'active' && $this->quoteHelper->isMiraklQuote($quote)) {
            return true;
        }

        return $proceed($field);
    }

    /**
     * @param   Freeshipping    $subject
     * @param   \Closure        $proceed
     * @param   RateRequest     $request
     * @return  RateResult|bool
     */
    public function aroundCollectRates(Freeshipping $subject, \Closure $proceed, RateRequest $request)
    {
        $quote = $this->extractQuoteFromItems($request->getAllItems());

        if ($quote && $this->quoteHelper->isMiraklQuote($quote)) {
            if (!$this->quoteHelper->isFullMiraklQuote($quote)) {
                // Define amount manually because we want to force free shipping usage on Mirakl items
                $request->setBaseSubtotalInclTax(pow(10, 6));
            } else {
                $subtotal = $request->getPackageValue(); // get quote subtotal
                /** @var QuoteItem $item */
                foreach ($quote->getAllItems() as $item) {
                    if ($item->isDeleted() || $item->getParentItemId()) {
                        continue;
                    }
                    if ($item->getMiraklOfferId()) {
                        $subtotal -= $item->getBaseRowTotalInclTax(); // subtract Mirakl items row total
                    }
                }
                $request->setFreeShipping(false);
                $request->setBaseSubtotalInclTax(max(0, $subtotal));
            }
        }

        return $proceed($request);
    }

    /**
     * @param   CartItemInterface[] $items
     * @return  CartInterface|null
     */
    private function extractQuoteFromItems(array $items)
    {
        foreach ($items as $item) {
            if ($quote = $item->getQuote()) {
                return $quote;
            }
        }

        return null;
    }
}
