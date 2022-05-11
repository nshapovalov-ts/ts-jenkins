<?php
namespace Mirakl\Connector\Model\Quote;

use Magento\Framework\App\CacheInterface;
use Magento\Quote\Api\Data\CartInterface;

class Cache
{
    /**
     * @var CacheInterface
     */
    protected $cache;

    /**
     * @var array
     */
    protected $cachedMethodResult = [];

    /**
     * @var array
     */
    protected $registry = [];

    /**
     * @param CacheInterface $cache
     */
    public function __construct(CacheInterface $cache)
    {
        $this->cache = $cache;
    }

    /**
     * Returns result calculated by a previous call on a quote
     *
     * @param   string  $methodName
     * @param   int     $quoteId
     * @param   string  $hash
     * @return  mixed|null
     */
    public function getCachedMethodResult($methodName, $quoteId, $hash)
    {
        if (!isset($this->cachedMethodResult[$methodName][$quoteId])) {
            return null;
        }

        if ($this->cachedMethodResult[$methodName][$quoteId]['hash'] != $hash) {
            return null;
        }

        return $this->cachedMethodResult[$methodName][$quoteId]['result'];
    }

    /**
     * @return  CacheInterface
     */
    public function getCache()
    {
        return $this->cache;
    }

    /**
     * @param   string  $key
     * @return  mixed|null
     */
    public function registry($key)
    {
        return $this->registry[$key] ?? null;
    }

    /**
     * @param   CartInterface   $quote
     * @param   string          $shippingZone
     * @return  string
     */
    public function getQuoteFeesCacheKey(CartInterface $quote, $shippingZone)
    {
        return sprintf('mirakl_shipping_fees_%s_%s_%s',
            $quote->getId(), $shippingZone, $this->getQuoteControlHash($quote));
    }

    /**
     * @param   CartInterface   $quote
     * @return  array
     */
    public function getQuoteCacheTags(CartInterface $quote)
    {
        return ['BLOCK_HTML', 'MIRAKL', 'MIRAKL_QUOTE_' . $quote->getId()];
    }

    /**
     * Retrieve quote control hash
     *
     * @param   CartInterface   $quote
     * @return  string
     */
    public function getQuoteControlHash(CartInterface $quote)
    {
        $items = $quote->getItemsCollection();
        $shippingTypes = [];
        $itemsQty = [];
        foreach ($items as $item) {
            $shippingTypes[$item->getId()] = $item->getMiraklShippingType();
            $itemsQty[$item->getId()] = (float) $item->getQty();
        }

        return sha1(json_encode([$itemsQty, $shippingTypes]));
    }

    /**
     * Store cache result calculated
     *
     * @param   string  $methodName
     * @param   int     $quoteId
     * @param   mixed   $result
     * @param   string  $hash
     */
    public function setCachedMethodResult($methodName, $quoteId, $result, $hash)
    {
        if (!isset($this->cachedMethodResult[$methodName])) {
            $this->cachedMethodResult[$methodName] = [];
        }

        $this->cachedMethodResult[$methodName][$quoteId] = ['result' => $result, 'hash' => $hash];
    }

    /**
     * @param   string  $key
     * @param   mixed   $data
     */
    public function register($key, $data)
    {
        $this->registry[$key] = $data;
    }
}
