<?php
namespace Mirakl\FrontendDemo\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\App\CacheInterface;

class CleanQuoteCacheObserver implements ObserverInterface
{
    /**
     * @var CacheInterface
     */
    private $cache;

    /**
     * @param CacheInterface $cache
     */
    public function __construct(CacheInterface $cache)
    {
        $this->cache = $cache;
    }

    /**
     * Clear quote cache used by the Mirakl connector if cart has changed
     *
     * {@inheritdoc}
     */
    public function execute(Observer $observer)
    {
        /** @var \Magento\Checkout\Model\Cart $cart */
        if ($cart = $observer->getEvent()->getCart()) {
            $quote = $cart->getQuote();
            $this->cache->clean(['MIRAKL_QUOTE_' . $quote->getId()]);
        }
    }
}