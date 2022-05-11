<?php
/**
 * Retailplace_MiraklSeller
 *
 * @copyright   Copyright (c) 2022 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Alexander Korsun <aleksandr@tradesquare.com.au>
 * @author      Ruslan Mukhatov <ruslan@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\MiraklSeller\Controller;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\App\ActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\RouterInterface;
use Magento\Framework\App\Action\Forward;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\CatalogUrlRewrite\Model\ResourceModel\Category\Product;
use Retailplace\MiraklSellerAdditionalField\Helper\Data;
use Mirakl\Core\Model\ResourceModel\Shop\CollectionFactory as ShopCollectionFactory;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\App\ActionFactory;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\Framework\Exception\NoSuchEntityException;
use Retailplace\MiraklSellerAdditionalField\Model\SellerFilter;
use Magento\Framework\UrlInterface;

/**
 * Class Router
 */
class Router implements RouterInterface
{
    /**
     * @var ActionFactory
     */
    protected $actionFactory;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var ProductCollectionFactory
     */
    protected $productCollectionFactory;

    /**
     * @var Product
     */
    protected $productUrlRewriteResource;

    /**
     * @var Data
     */
    protected $helper;

    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @var ShopCollectionFactory
     */
    protected $miraklShopCollectionFactory;

    /**
     * @var Visibility
     */
    protected $productVisibility;

    /**
     * @var Status
     */
    protected $productStatus;

    /**
     * @var SellerFilter
     */
    private $sellerFilter;

    /**
     * @param ActionFactory $actionFactory
     * @param StoreManagerInterface $storeManager
     * @param ProductCollectionFactory $productCollectionFactory
     * @param ShopCollectionFactory $miraklShopCollectionFactory
     * @param Data $helper
     * @param Product $productUrlRewriteResource
     * @param Status $productStatus
     * @param Visibility $productVisibility
     * @param ProductRepositoryInterface $productRepository
     * @param SellerFilter $sellerFilter
     */
    public function __construct(
        ActionFactory $actionFactory,
        StoreManagerInterface $storeManager,
        ProductCollectionFactory $productCollectionFactory,
        ShopCollectionFactory $miraklShopCollectionFactory,
        Data $helper,
        Product $productUrlRewriteResource,
        Status $productStatus,
        Visibility $productVisibility,
        ProductRepositoryInterface $productRepository,
        SellerFilter $sellerFilter
    ) {
        $this->storeManager = $storeManager;
        $this->productCollectionFactory = $productCollectionFactory;
        $this->actionFactory = $actionFactory;
        $this->productUrlRewriteResource = $productUrlRewriteResource;
        $this->helper = $helper;
        $this->productRepository = $productRepository;
        $this->miraklShopCollectionFactory = $miraklShopCollectionFactory;
        $this->productStatus = $productStatus;
        $this->productVisibility = $productVisibility;
        $this->sellerFilter = $sellerFilter;
    }

    /**
     * Match
     *
     * @param RequestInterface $request
     * @return bool|ActionInterface
     */
    public function match(RequestInterface $request)
    {
        try {
            $identifier = trim($request->getPathInfo(), '/');
            $chunk = explode('/', $identifier, 3);

            if (empty($chunk[0]) || empty($chunk[1]) || empty($chunk[2])) {
                return false;
            }

            $param = $chunk[0];
            $sellerId = $chunk[1];
            $url = $chunk[2];

            if ($param != 'seller') {
                return false;
            }

            $productId = $this->getProductByUrlKeyRewrite($url);

            if (empty($productId)) {
                $urlKey = substr($url, 0, strrpos($url, '.'));
                if (!empty($urlKey)) {
                    $productId = $this->getProductByUrlKey($urlKey);
                }
            }

            if (!empty($productId) && $this->checkSellerValid($sellerId, $productId)) {
                $request->setAlias(
                    UrlInterface::REWRITE_REQUEST_PATH_ALIAS,
                    ltrim($request->getPathInfo(), '/')
                );
                $request->setPathInfo('/catalog/product/view/id/' . $productId);

                if ($shop = $this->getMiraklSeller($sellerId)) {
                    if (!empty($shop['eav_option_id'])) {
                        $this->sellerFilter->setFilteredShopOptionIds([$shop['eav_option_id']]);
                    }
                }

                return $this->actionFactory->create(Forward::class);
            }
        } catch (NoSuchEntityException $e) {
            return false;
        }

        return false;
    }

    /**
     * Check Seller Valid
     *
     * @param string|int $shopId
     * @param string|int $productId
     * @return bool
     */
    protected function checkSellerValid($shopId, $productId): bool
    {
        if (empty($shopId)) {
            return false;
        }

        try {
            $product = $this->productRepository->getById($productId);
            if (!in_array($product->getStatus(), $this->productStatus->getVisibleStatusIds())) {
                return false;
            }
            if (!in_array($product->getVisibility(), $this->productVisibility->getVisibleInSiteIds())) {
                return false;
            }
        } catch (NoSuchEntityException $e) {
            return false;
        }

        if ($shop = $this->getMiraklSeller($shopId)) {
            $eavShopId = !empty($shop['eav_option_id']) ? $shop['eav_option_id'] : "";

            $shopIds = $product->getMiraklShopIds();
            if (!empty($shopIds)) {
                $shopIds = !is_array($shopIds) ? explode(',', $shopIds) : $shopIds;
            }

            if (is_array($shopIds) && in_array($eavShopId, $shopIds)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Get Product By Url Key
     *
     * @param $urlKey
     * @return string|null
     * @throws NoSuchEntityException
     */
    protected function getProductByUrlKey($urlKey): ?string
    {
        $productCollection = $this->productCollectionFactory->create()
            ->addStoreFilter($this->storeManager->getStore()->getId())
            ->addAttributeToFilter('status', ['in' => $this->productStatus->getVisibleStatusIds()])
            ->setVisibility($this->productVisibility->getVisibleInSiteIds())
            ->addAttributeToFilter(
                [
                    ['attribute' => 'url_key', 'eq' => $urlKey],
                ]
            );
        $productIds = $productCollection->getAllIds(1, 0);
        return !empty($productIds[0]) ? $productIds[0] : null;
    }

    /**
     * Get Product By Url Key Rewrite
     *
     * @param $url
     * @return string|null
     */
    protected function getProductByUrlKeyRewrite($url): ?string
    {
        $connection = $this->productUrlRewriteResource->getConnection();
        $table = $this->productUrlRewriteResource->getTable('url_rewrite');
        $select = $connection->select();
        $select->from($table, ['entity_id'])
            ->where('entity_type = :entity_type')
            ->where('request_path LIKE :request_path');

        $result = $connection->fetchCol(
            $select,
            ['entity_type' => 'product', 'request_path' => basename($url)]
        );

        return !empty($result[0]) ? $result[0] : null;
    }

    /**
     * Get Mirakl Seller
     *
     * @param string|int $sellerId
     * @return array
     */
    public function getMiraklSeller($sellerId): array
    {
        $collection = $this->miraklShopCollectionFactory->create()
            ->addFilter('id', $sellerId);
        $item = $collection->getFirstItem();
        if (!empty($item)) {
            return $item->getData();
        }
        return [];
    }
}
