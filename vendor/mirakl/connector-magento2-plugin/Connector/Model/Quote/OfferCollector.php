<?php
namespace Mirakl\Connector\Model\Quote;

use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Model\Quote\Item as QuoteItem;
use Magento\Quote\Model\Quote\Item\Option as QuoteItemOption;
use Magento\Quote\Model\ResourceModel\Quote\ItemFactory as QuoteItemResourceFactory;
use Mirakl\Connector\Model\Offer;
use Mirakl\Connector\Model\OfferFactory;
use Mirakl\MMP\Front\Domain\Shipping\OfferQuantityShippingTypeTuple;

class OfferCollector
{
    /**
     * @var QuoteItemResourceFactory
     */
    protected $quoteItemResourceFactory;

    /**
     * @var OfferFactory
     */
    protected $offerFactory;

    /**
     * @var Cache
     */
    protected $cache;

    /**
     * @param   QuoteItemResourceFactory    $quoteItemResourceFactory
     * @param   OfferFactory                $offerFactory
     * @param   Cache                       $cache
     */
    public function __construct(
        QuoteItemResourceFactory $quoteItemResourceFactory,
        OfferFactory $offerFactory,
        Cache $cache
    ) {
        $this->quoteItemResourceFactory = $quoteItemResourceFactory;
        $this->offerFactory = $offerFactory;
        $this->cache = $cache;
    }

    /**
     * Returns current offers in specified quote
     *
     * @param   CartInterface   $quote
     * @return  array
     */
    public function getItemsWithOffer(CartInterface $quote)
    {
        $hash = $this->cache->getQuoteControlHash($quote);
        if ($cache = $this->cache->getCachedMethodResult(__METHOD__, $quote->getId(), $hash)) {
            return $cache;
        }

        $quoteItemsWithOffer = [];
        /** @var QuoteItem $quoteItem */
        foreach ($this->getQuoteItems($quote) as $quoteItem) {
            if ($quoteItem->isDeleted() || $quoteItem->getParentItemId()) {
                continue;
            }

            /** @var QuoteItemOption $offerCustomOption */
            $offerCustomOption = $quoteItem->getProduct()->getCustomOption('mirakl_offer');
            if (!$offerCustomOption) {
                continue;
            }

            $offer = $this->offerFactory->fromJson($offerCustomOption->getValue());

            // Merge items if they have the same offer id
            if (isset($quoteItemsWithOffer[$offer->getId()])) {
                $existingOffer = $quoteItemsWithOffer[$offer->getId()];
                $quoteItem->setQty($quoteItem->getQty() + $existingOffer->getQty());
                $this->quoteItemResourceFactory->create()->save($quoteItem);
                $quote->removeItem($existingOffer->getId());
            }

            $quoteItem->setData('offer', $offer);
            $quoteItemsWithOffer[$offer->getId()] = $quoteItem;
        }

        $this->cache->setCachedMethodResult(__METHOD__, $quote->getId(), $quoteItemsWithOffer, $hash);

        return $quoteItemsWithOffer;
    }

    /**
     * Returns operator quote items
     *
     * @param   CartInterface   $quote
     * @return  array
     */
    public function getItemsWithoutOffer(CartInterface $quote = null)
    {
        $hash = $this->cache->getQuoteControlHash($quote);
        if ($cache = $this->cache->getCachedMethodResult(__METHOD__, $quote->getId(), $hash)) {
            return $cache;
        }

        $quoteItemsWithoutOffer = [];
        /** @var QuoteItem $quoteItem */
        foreach ($this->getQuoteItems($quote) as $quoteItem) {
            if (!$quoteItem->isDeleted() && !$quoteItem->getParentItemId()
                && !$quoteItem->getProduct()->getCustomOption('mirakl_offer'))
            {
                $quoteItemsWithoutOffer[] = $quoteItem;
            }
        }

        $this->cache->setCachedMethodResult(__METHOD__, $quote->getId(), $quoteItemsWithoutOffer, $hash);

        return $quoteItemsWithoutOffer;
    }

    /**
     * Returns offers in cart with quantity
     *
     * @param   CartInterface   $quote
     * @return  array
     */
    public function getOffersWithQty(CartInterface $quote)
    {
        $offersWithQty = [];

        /** @var QuoteItem $quoteItem */
        foreach ($this->getItemsWithOffer($quote) as $quoteItem) {
            /** @var Offer $offer */
            $offer = $quoteItem->getData('offer');
            $offerQty = (new OfferQuantityShippingTypeTuple())
                ->setOfferId($offer->getId())
                ->setQuantity($quoteItem->getQty())
                ->setShippingTypeCode($quoteItem->getMiraklShippingType());
            $offersWithQty[] = $offerQty;
        }

        return $offersWithQty;
    }

    /**
     * @param   CartInterface   $quote
     * @return  array
     */
    public function getQuoteItems(CartInterface $quote)
    {
        $registryKey = sprintf('mirakl_quote_items_%d', $quote->getId());
        if (null === ($items = $this->cache->registry($registryKey))) {
            $items = $quote->getAllItems();
            $this->cache->register($registryKey, $items);
        }

        return $items;
    }
}
