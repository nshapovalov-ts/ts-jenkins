<?php

/**
 * Retailplace_MiraklSeller
 *
 * @copyright   Copyright (c) 2022 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Satish Gumudavelly <satish@kipanga.com.au>
 * @author      Dmitriy Fionov <dmitriy@tradesquare.com.au>
 * @author      Ruslan Mukhatov <ruslan@tradesquare.com.au>
 * @author      Natalia Sekulich <natalia@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\MiraklSeller\Block;

use Retailplace\Search\Model\SearchFilter;
use Retailplace\MiraklSeller\Model\IsNewSeller;
use Retailplace\MiraklShop\Api\Data\ShopInterface;
use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Block\Product\Context;
use Magento\Catalog\Model\Layer;
use Magento\Catalog\Model\Layer\Resolver;
use Magento\CatalogSearch\Helper\Data as CatalogSearchData;
use Magento\Framework\Data\Helper\PostHelper;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\Url\Helper\Data;
use Mirakl\Core\Model\ResourceModel\Shop\Collection;
use Mirakl\Core\Model\ResourceModel\Shop\Collection as ShopCollection;
use Mirakl\Core\Model\Shop;
use Retailplace\MiraklPromotion\Api\PromotionRepositoryInterface;
use Retailplace\MiraklSeller\Helper\Seller as SellerAlias;

/**
 * Navigation block class
 */
class Seller extends \Magento\Framework\View\Element\Template
{
    /**
     * Product attribute code
     */
    const MIRAKL_SHOP_IDS = 'mirakl_shop_ids';

    /**
     * Pagination param
     */
    const SELLER_VIEW_PAGINATION = 'p';

    /**
     * @var SellerAlias
     */
    protected $helper;

    /**
     * @var ShopCollection
     */
    protected $shopCollection;

    /**
     * @var Layer
     */
    protected $_catalogLayer;

    /**
     * @var PostHelper
     */
    protected $_postDataHelper;

    /**
     * @var CategoryRepositoryInterface
     */
    protected $categoryRepository;

    /**
     * @var Data
     */
    protected $urlHelper;

    /**
     * @var PriceCurrencyInterface
     */
    protected $priceCurrency;

    /**
     * Catalog search data
     *
     * @var CatalogSearchData
     */
    protected $catalogSearchData;

    /**
     * @var mixed
     */
    private $facetData;

    /**
     * @var PromotionRepositoryInterface
     */
    private $promotionRepository;

    /**
     * @var IsNewSeller
     */
    private $isNewSeller;

    /**
     * IsNew Shop data
     *
     * @var array
     */
    private $shopsNewData = [];

    /**
     * @var SearchFilter
     */
    private $searchFilter;

    /**
     * Seller constructor.
     *
     * @param Context $context
     * @param PostHelper $postDataHelper
     * @param SellerAlias $helper
     * @param Resolver $layerResolver
     * @param CategoryRepositoryInterface $categoryRepository
     * @param Data $urlHelper
     * @param PriceCurrencyInterface $priceCurrency
     * @param CatalogSearchData $catalogSearchData
     * @param \Retailplace\MiraklPromotion\Api\PromotionRepositoryInterface $promotionRepository
     * @param IsNewSeller $isNewSeller ,
     *
     * @param array $data
     */
    public function __construct(
        Context $context,
        PostHelper $postDataHelper,
        SellerAlias $helper,
        Resolver $layerResolver,
        CategoryRepositoryInterface $categoryRepository,
        Data $urlHelper,
        PriceCurrencyInterface $priceCurrency,
        CatalogSearchData $catalogSearchData,
        PromotionRepositoryInterface $promotionRepository,
        IsNewSeller $isNewSeller,
        SearchFilter $searchFilter,
        array $data = []
    ) {
        parent::__construct(
            $context,
            $data
        );
        $this->_catalogLayer = $layerResolver->get();
        $this->_postDataHelper = $postDataHelper;
        $this->categoryRepository = $categoryRepository;
        $this->urlHelper = $urlHelper;
        $this->helper = $helper;
        $this->priceCurrency = $priceCurrency;
        $this->catalogSearchData = $catalogSearchData;
        $this->promotionRepository = $promotionRepository;
        $this->isNewSeller = $isNewSeller;
        $this->searchFilter = $searchFilter;
    }

    /**
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function _prepareLayout()
    {
        parent::_prepareLayout();
        $title = $this->getSearchQueryText();
        if ($title) {
            $this->pageConfig->getTitle()->set($title);
            // add Home breadcrumb
            $breadcrumbs = $this->getLayout()->getBlock('breadcrumbs');
            if ($breadcrumbs) {
                $breadcrumbs->addCrumb(
                    'home',
                    [
                        'label' => __('Home'),
                        'title' => __('Go to Home Page'),
                        'link'  => $this->_storeManager->getStore()->getBaseUrl()
                    ]
                )->addCrumb(
                    'search',
                    ['label' => $title, 'title' => $title]
                );
            }
        }

        if ($this->isEnabled()) {
            $pagination = ($this->helper->getPageAllowedValues()) ? $this->helper->getPageAllowedValues() : "16,24,32,40,48,60";
            $paginationArray = explode(",", $pagination);
            $this->getLayer()->getProductCollection()->load();
            $collection = $this->getCollection();
            if ($collection) {
                $pager = $this->getLayout()->createBlock(
                    'Magento\Theme\Block\Html\Pager',
                    'seller.pager'
                )
                    ->setPageVarName(self::SELLER_VIEW_PAGINATION)
                    ->setAvailableLimit($paginationArray)
                    ->setShowPerPage(true)
                    ->setCollection(
                        $collection
                    );
                $this->setChild('pager', $pager);
            }
        }
        return $this;
    }

    /**
     * Get search query text
     *
     * @return \Magento\Framework\Phrase
     */
    public function getSearchQueryText()
    {
        return $this->catalogSearchData->getEscapedQueryText() ? __("Search results for: " . '"' . $this->catalogSearchData->getEscapedQueryText() . '"') : "";
    }

    public function isEnabled()
    {
        return $this->helper->isEnabled();
    }

    /**
     * @param $sellerFacetData
     * @return void
     */
    public function usort(&$sellerFacetData)
    {
        $column = 'count';
        usort($sellerFacetData, function ($a, $b) use ($column) {
            if ($a[$column] == $b[$column]) {
                return 0;
            }
            return ($a[$column] > $b[$column]) ? -1 : 1;
        });
    }

    /**
     * @return Collection|mixed
     */
    public function getCollection()
    {
        if ($this->shopCollection == null) {
            $sellerFacetData = $this->getSellerIdsFromLayer();
            $sellerFacetData = $this->filterNewSellers($sellerFacetData);
            $shopEavIds = array_keys($sellerFacetData);
            $this->usort($sellerFacetData);
            $shopEavIdsSortIdsByCount = array_column($sellerFacetData, 'value');
            $page = ($this->_request->getParam(self::SELLER_VIEW_PAGINATION)) ? $this->_request->getParam(self::SELLER_VIEW_PAGINATION) : 1;
            $pageSize = ($this->helper->getPageDefaultValue()) ? $this->helper->getPageDefaultValue() : 16;
            $shopCollection = $this->helper->getShopCollection($shopEavIds);
            $shopCollection->getSelect()->group('main_table.id');
            if ($shopEavIdsSortIdsByCount) {
                $shopCollection->getSelect()
                    ->order(new \Zend_Db_Expr('FIELD(eav_option_id,' . implode(',', $shopEavIdsSortIdsByCount) . ')'));
            }
            $shopCollection->setPageSize($pageSize);
            $shopCollection->setCurPage($page);
            $this->helper->addProductImages($shopCollection, $this->getLayer()->getProductCollection());
            $this->shopCollection = $shopCollection;
            $this->addPromotions();
        }
        return $this->shopCollection;
    }

    /**
     * @param array $sellerFacetData
     *
     * @return array
     */
    private function filterNewSellers(array $sellerFacetData): array
    {
        $newSellerIds = $this->searchFilter->getNewSellerViewIds();
        if ($newSellerIds) {
            foreach ($sellerFacetData as $key => $item) {
                if (!in_array($item['value'], $newSellerIds)) {
                    unset($sellerFacetData[$key]);
                }
            }
        }

        return $sellerFacetData;
    }

    /**
     * Add Promotions to Shop Collection
     */
    private function addPromotions()
    {
        $promotionsArray = [];
        $shopIds = [];
        /** @var \Mirakl\Core\Model\Shop $shop */
        foreach ($this->shopCollection->getItems() as $shop) {
            $shopIds[] = $shop->getId();
        }

        $promotionsList = $this->promotionRepository->getActiveByShops($shopIds);
        foreach ($promotionsList->getItems() as $promotion) {
            $promotionsArray[$promotion->getShopId()][] = $promotion;
        }

        foreach ($this->shopCollection->getItems() as $shop) {
            $shop->setData('mirakl_promotion', []);
            if (!empty($promotionsArray[$shop->getId()])) {
                $shop->setData('mirakl_promotion', $promotionsArray[$shop->getId()]);
            }
        }
    }

    /**
     * @return mixed
     */
    public function getSellerIdsFromLayer()
    {
        if ($this->facetData == null) {
            $productCollection = $this->getLayer()->getProductCollection();
            $this->facetData = $productCollection->getFacetedData(self::MIRAKL_SHOP_IDS);
        }
        return $this->facetData;
    }

    /**
     * Retrieve layer object
     *
     * @return \Magento\Catalog\Model\Layer
     */
    public function getLayer()
    {
        $layer = $this->_getData('layer');
        if ($layer === null) {
            $layer = $this->_catalogLayer;
            $this->setData('layer', $layer);
        }
        return $layer;
    }

    /**
     * format price with currency
     * @return string
     */
    public function getFormatedPrice($amount)
    {
        return $this->priceCurrency->convertAndFormat($amount, true, 0);
    }

    /**
     * retrieve pagination for seller
     * @return string
     */
    public function getPagerHtml()
    {
        return $this->getChildHtml('pager');
    }

    /**
     * Get Product Images for Shop
     *
     * @param Shop $shop
     * @return string[]
     */
    public function getShopImages(Shop $shop): array
    {
        $allImages = [];
        if ($shop->getImage()) {
            $allImages[] = $this->getMediaUrl() . $shop->getImage();
        }

        $productImages = $shop->getData('product_images');
        if (is_array($productImages)) {
            $allImages = array_merge($allImages, $productImages);
        }

        return $allImages;
    }

    /**
     * Check if we should show Products Slider
     *
     * @param \Mirakl\Core\Model\Shop $shop
     * @return bool
     */
    public function isSlider(Shop $shop): bool
    {
        $imagesCount = count($this->getShopImages($shop));
        if ($shop->getImage()) {
            $imagesCount++;
        }

        return $imagesCount > 1;
    }

    /**
     * @return string
     */
    public function getMediaUrl()
    {
        return $this->_storeManager
            ->getStore()
            ->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA);
    }

    /**
     * Get new label for a shop
     *
     * @return string
     */
    public function getIsNewShopLabel(): string
    {
        return __($this->isNewSeller->getIsNewShopLabel())->render();
    }

    /**
     * Collect data for is_new shop attribute
     *
     * @param Shop|ShopInterface $shop
     *
     * @return bool
     */
    public function isNewShop($shop): bool
    {
        $isNew = $this->shopsNewData[$shop->getId()] ?? null;
        if ($isNew === null) {
            $isNew = $shop->getIsNew();
            $this->shopsNewData[$shop->getId()] = $isNew;
        }

        return $isNew;
    }

    /**
     * Get new label for a shop
     *
     * @return string
     */
    public function getNewProductsAddedLabel(): string
    {
        $label = (string) $this->_scopeConfig->getValue('retailplace_attribute_updater/has_new_products/has_new_products_label');

        return __($label)->render();
    }
}
