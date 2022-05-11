<?php
/**
 * Retailplace_MiraklFrontendDemo
 *
 * @copyright   Copyright (c) 2022 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Alexander Korsun <aleksandr@tradesquare.com.au>
 * @author      Ruslan Mukhatov <ruslan@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\MiraklFrontendDemo\Plugin\Model;

use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Closure;
use Mirakl\Core\Model\ResourceModel\Shop\CollectionFactory as ShopCollectionFactory;
use Mirakl\FrontendDemo\Helper\Offer as OfferHelper;
use Mirakl\Connector\Helper\Offer as ConnectorOfferHelper;
use Magento\Store\Model\StoreManagerInterface;
use WeltPixel\GoogleTagManager\lib\Google\Exception;
use Magento\Framework\Exception\NoSuchEntityException;
use Psr\Log\LoggerInterface;
use Magento\Framework\Registry;

/**
 * Class ProductCollectionPlugin
 */
class ProductCollectionPlugin
{
    /**
     * @var string
     */
    const FLAG_HAS_APPEND_OFFERS = 'has_append_offers';

    /**
     * @var string
     */
    const FLAG_IS_LOADED = 'is_loaded';

    /**
     * @var string
     */
    const FLAG_HAS_SKIP_SALEABLE_CHECK = 'has_skip_saleable_check';

    /**
     * @var OfferHelper
     */
    protected $offerHelper;

    /**
     * @var ShopCollectionFactory
     */
    protected $shopCollectionFactory;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var ConnectorOfferHelper
     */
    private $connectorOfferHelper;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var Registry
     */
    private $coreRegistry;

    /**
     * @var null|int
     */
    private $sellerId;

    /**
     * @param OfferHelper $offerHelper
     * @param ShopCollectionFactory $shopCollectionFactory
     * @param StoreManagerInterface $storeManager
     * @param ConnectorOfferHelper $connectorOfferHelper
     * @param Registry $registry
     * @param LoggerInterface $logger
     */
    public function __construct(
        OfferHelper $offerHelper,
        ShopCollectionFactory $shopCollectionFactory,
        StoreManagerInterface $storeManager,
        ConnectorOfferHelper $connectorOfferHelper,
        Registry $registry,
        LoggerInterface $logger
    ) {
        $this->offerHelper = $offerHelper;
        $this->shopCollectionFactory = $shopCollectionFactory;
        $this->storeManager = $storeManager;
        $this->connectorOfferHelper = $connectorOfferHelper;
        $this->coreRegistry = $registry;
        $this->logger = $logger;
    }

    /**
     * Add Price Data to result
     *
     * @param Collection $subject
     * @param Closure $proceed
     * @param int|null $customerGroupId
     * @param int|null $websiteId
     * @return Collection $this
     */
    public function aroundAddPriceData(
        Collection $subject,
        Closure $proceed,
        ?int $customerGroupId = null,
        ?int $websiteId = null
    ): Collection {
        $skipFlag = 'has_skip_add_price_data';
        if ($subject->hasFlag($skipFlag)) {
            return $subject;
        }

        return $proceed($customerGroupId, $websiteId);
    }

    /**
     * @param Collection $productCollection
     * @return Collection
     */
    public function afterLoad(Collection $productCollection): Collection
    {

        if ($productCollection->hasFlag(self::FLAG_IS_LOADED)) {
            return $productCollection;
        }

        $productCollection->setFlag(self::FLAG_IS_LOADED, true);

        if ($productCollection->hasFlag(self::FLAG_HAS_SKIP_SALEABLE_CHECK)) {
            foreach ($productCollection as $product) {
                $product->setData('skip_saleable_check', true);
                $product->setData('salable', true);
            }
        }

        if ($productCollection->hasFlag(self::FLAG_HAS_APPEND_OFFERS)) {
            try {
                //load offers by products
                $productIds = [];

                foreach ($productCollection as $product) {
                    $productIds[] = $product->getId();
                }

                $store = $this->storeManager->getStore();
                $storeId = $store->getId();
                $currencyCode = $store->getCurrentCurrencyCode();
                $this->connectorOfferHelper->getAvailableOffersForProducts(
                    $productIds,
                    $currencyCode,
                    null,
                    $storeId,
                    true
                );

                $shopIds = [];
                foreach ($productCollection as $product) {
                    $offer = $this->offerHelper->getBestOffer($product);
                    $shopId = null;
                    if ($offer) {
                        $product->setData('main_offer', $offer);
                        $shopIds[$offer->getShopId()] = $offer->getShopId();
                        $shopId = $offer->getShopId();
                    } else {
                        $sellerId = $this->getSellerId();
                        if ($sellerId) {
                            $product->setData('offer_seller_id', $sellerId);
                        }
                    }

                    $worstOffer = $this->offerHelper->getWorstOffer($product, $shopId);
                    if ($worstOffer) {
                        $product->setData('worst_offer', $worstOffer);
                    }
                }
                if (!$shopIds) {
                    return $productCollection;
                }

                $shopCollection = $this->shopCollectionFactory->create();
                $shopCollection->addFieldToFilter('id', ['in' => $shopIds]);

                foreach ($productCollection as $product) {
                    $offer = $product->getData('main_offer');
                    if ($offer) {
                        $shop = $shopCollection->getItemById($offer->getShopId());
                        if ($shop) {
                            $product->setData('shop', $shop);
                        }
                    }
                }
            } catch (Exception | NoSuchEntityException $e) {
                $this->logger->critical($e);
            }
        }

        return $productCollection;
    }

    /**
     * Get Seller ID
     *
     * if the current page is a seller's page and the product does not have the available offers
     * @return int|null
     */
    private function getSellerId(): ?int
    {
        if ($this->sellerId !== null) {
            return $this->sellerId;
        }
        $this->sellerId = 0;
        $shop = $this->coreRegistry->registry('mirakl_shop');
        if ($shop) {
            $this->sellerId = (int) $shop->getId();
        }
        return $this->sellerId;
    }
}
