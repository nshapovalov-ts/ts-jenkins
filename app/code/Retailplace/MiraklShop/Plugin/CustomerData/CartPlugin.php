<?php
/**
 * Retailplace_MiraklShop
 *
 * @copyright   Copyright (c) 2022 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Nikolay Shapovalov <nikolay@tradesquare.com.au>
 * @author      Natalia Sekulich <natalia@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\MiraklShop\Plugin\CustomerData;

use Magento\Checkout\CustomerData\Cart;
use Magento\Quote\Api\Data\CartInterface;
use Mirakl\Core\Model\ResourceModel\Shop\CollectionFactory;
use Magento\Checkout\Model\Session;
use Retailplace\MiraklShop\Model\Shop;
use Psr\Log\LoggerInterface;

/**
 * Class CartPlugin adding additional data to local storage
 * @SuppressWarnings(PHPMD.LongVariable)
 * @SuppressWarnings(PHPMD.CookieAndSessionMisuse)
 */
class CartPlugin
{
    /**
     * @var string
     */
    public const SHOP_AMOUNTS_FIELD = 'shop_amounts';

    /** @var Session */
    private $checkoutSession;

    /** @var LoggerInterface */
    private $logger;

    /** @var CollectionFactory */
    private $shopCollectionFactory;

    /**
     * @param CollectionFactory $shopCollectionFactory
     * @param Session $checkoutSession
     * @param LoggerInterface $logger
     */
    public function __construct(
        CollectionFactory $shopCollectionFactory,
        Session $checkoutSession,
        LoggerInterface $logger
    ) {
        $this->shopCollectionFactory = $shopCollectionFactory;
        $this->checkoutSession = $checkoutSession;
        $this->logger = $logger;
    }

    /**
     * @param Cart $subject
     * @param $result
     * @return array
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterGetSectionData(Cart $subject, $result): array
    {
        $result[self::SHOP_AMOUNTS_FIELD] = $this->getShopAmounts();

        return $result;
    }

    /**
     * @return Shop[]
     */
    private function getShopAmounts(): array
    {
        $shopAmounts = [];
        $shopIds = $this->getShopIds();
        $shopCollection = $this->shopCollectionFactory->create();
        $shopCollection->addFieldToFilter('id', ['in' => $shopIds]);
        foreach ($shopCollection->getItems() as $shopItem) {
            $shopAmounts[$shopItem->getId()] = $shopItem->getShopAmounts()->toArray();
        }

        return $shopAmounts ?? [];
    }

    /**
     * @return array
     */
    private function getShopIds(): array
    {
        $shopIds = [];
        $quote = $this->getQuote();
        if ($quote) {
            $quoteItems = $quote->getItems() ?? [];
            foreach ($quoteItems as $item) {
                $shopIds[] = $item->getMiraklShopId();
            }
        }

        return $shopIds;
    }

    /**
     * @return CartInterface|null
     */
    private function getQuote(): ?CartInterface
    {
        try {
            $quote = $this->checkoutSession->getQuote();
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
            $quote = null;
        }

        return $quote;
    }
}
