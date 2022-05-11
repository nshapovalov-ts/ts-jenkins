<?php

namespace Retailplace\Search\Model\Autocomplete;

use Magento\Catalog\Block\Product\Context;
use Magento\Catalog\Helper\Image;
use Magento\Catalog\Helper\Product;
use Magento\Catalog\Model\Category\Attribute\Source\Layout;
use Magento\Catalog\Model\Layer\Resolver;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory;
use Magento\CatalogSearch\Model\Autocomplete\DataProvider;
use Magento\Framework\App\Config\ScopeConfigInterface as ScopeConfig;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\Session\Config\ConfigInterface;
use Magento\Framework\Stdlib\Cookie\CookieMetadataFactory;
use Magento\Framework\Stdlib\CookieManagerInterface;
use Magento\Search\Model\Autocomplete\ItemFactory;
use Magento\Search\Model\QueryFactory;
use Magento\Search\Model\ResourceModel\Query\Collection;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Full text search implementation of autocomplete.
 */
class SearchDataProvider extends DataProvider
{
    public $request;
    public $cookieMetadataFactory;
    public $cookieManagerInterface;
    public $configInterface;
    public $helper;
    /**
     * Price currency
     *
     * @var PriceCurrencyInterface
     */
    protected $_priceCurrency;
    /**
     * Catalog Layer Resolver
     *
     * @var Resolver
     */
    protected $_layerResolver;
    /**
     * Layout
     *
     * @var Layout
     */
    protected $_layout;
    /**
     * Catalog Product collection
     *
     * @var Collection
     */
    protected $_productCollection;
    /**
     * Image helper
     *
     * @var Image
     */
    protected $_imageHelper;
    /**
     * @var StoreManagerInterface
     */
    private $storeManager;
    /**
     * @var \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory
     */
    private $_productCollectionFactory;
    /**
     * @var CollectionFactory
     */
    private $_categoryCollectionFactory;


    /**
     * @param Context $context
     * @param QueryFactory $queryFactory
     * @param ItemFactory $itemFactory
     * @param ScopeConfig $scopeConfig
     * @param PriceCurrencyInterface $priceCurrency
     * @param Resolver $layerResolver
     * @param StoreManagerInterface $storeManager
     * @param Http $request
     * @param \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory
     * @param CookieMetadataFactory $cookieMetadataFactory
     * @param CookieManagerInterface $cookieManagerInterface
     * @param ConfigInterface $configInterface
     * @param CollectionFactory $categoryCollectionFactory
     */
    public function __construct(
        Context $context,
        QueryFactory $queryFactory,
        ItemFactory $itemFactory,
        ScopeConfig $scopeConfig,
        PriceCurrencyInterface $priceCurrency,
        Resolver $layerResolver,
        StoreManagerInterface $storeManager,
        Http $request,
        \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory,
        CookieMetadataFactory $cookieMetadataFactory,
        CookieManagerInterface $cookieManagerInterface,
        ConfigInterface $configInterface,
        CollectionFactory $categoryCollectionFactory
    ) {
        $this->_imageHelper = $context->getImageHelper();
        $this->_priceCurrency = $priceCurrency;
        $this->_layout = $context->getLayout();
        $this->_layerResolver = $layerResolver;
        $this->_layerResolver->create(Resolver::CATALOG_LAYER_SEARCH);

        $this->storeManager = $storeManager;
        $this->_productCollectionFactory = $productCollectionFactory;
        $this->request = $request;
        $this->cookieMetadataFactory = $cookieMetadataFactory;
        $this->cookieManagerInterface = $cookieManagerInterface;
        $this->configInterface = $configInterface;
        $this->_categoryCollectionFactory = $categoryCollectionFactory;
        parent::__construct($queryFactory, $itemFactory, $scopeConfig);
    }

    /**
     * Get items
     *
     * @return array
     */
    public function getItems()
    {
        return parent::getItems();
    }


    /**
     * Retrieve loaded product collection
     *
     * @return Collection
     */

    protected function _getProductCollection()
    {
        if (null === $this->_productCollection) {
            $this->_productCollection = $this->_layout->getBlock('search_result_list')->getLoadedProductCollection();
        }
        return $this->_productCollection;
    }

    /**
     * Get product price
     *
     * @param Product $product
     * @return string
     */
    protected function _getProductPrice($product)
    {
        return $this->_priceCurrency->format($product->getFinalPrice($product), false, PriceCurrencyInterface::DEFAULT_PRECISION, $product->getStore());
    }

    /**
     * Get product reviews
     *
     * @param Product $product
     * @return string
     */
    protected function _getProductReviews($product)
    {
        return $this->_layout->createBlock('Magento\Review\Block\View')
            ->getReviewsSummaryHtml($product, 'short');
    }

    /**
     * Product image url getter
     *
     * @param Product $product
     * @return string
     */
    protected function _getImageUrl($product)
    {
        return $this->_imageHelper->init($product, 'product_page_image_small')->getUrl();
    }
}
