<?php
namespace Mirakl\Connector\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Api\Data\CartItemInterface;
use Mirakl\Connector\Model\Quote\OfferCollector;

class Quote extends AbstractHelper
{
    /**
     * @var OfferCollector
     */
    protected $offerCollector;

    /**
     * @param   Context         $context
     * @param   OfferCollector  $offerCollector
     */
    public function __construct(
        Context $context,
        OfferCollector $offerCollector
    ) {
        parent::__construct($context);

        $this->offerCollector = $offerCollector;
    }

    /**
     * Returns true if given quote contains ONLY Mirakl products
     *
     * @param   CartInterface   $quote
     * @return  bool
     */
    public function isFullMiraklQuote(CartInterface $quote)
    {
        static $cache = [];
        if (isset($cache[$quote->getId()])) {
            return $cache[$quote->getId()];
        }

        $result = true;
        foreach ($this->offerCollector->getQuoteItems($quote) as $item) {
            /** @var CartItemInterface $item */
            if (!$item->isDeleted()
                && !$item->getParentItemId()
                && !$item->getMiraklShopId()
                && !$item->getProduct()->getCustomOption('mirakl_offer')
            ) {
                $result = false;
                break;
            }
        }

        $cache[$quote->getId()] = $result;

        return $result;
    }

    /**
     * Returns true if given quote contains SOME Mirakl products
     *
     * @param   CartInterface   $quote
     * @return  bool
     */
    public function isMiraklQuote(CartInterface $quote)
    {
        static $cache = [];
        if (isset($cache[$quote->getId()])) {
            return $cache[$quote->getId()];
        }

        $result = false;
        foreach ($this->offerCollector->getQuoteItems($quote) as $item) {
            /** @var CartItemInterface $item */
            if (!$item->isDeleted()
                && !$item->getParentItemId()
                && ($item->getMiraklShopId() || $item->getProduct()->getCustomOption('mirakl_offer'))
            ) {
                $result = true;
                break;
            }
        }

        $cache[$quote->getId()] = $result;

        return $result;
    }
}
