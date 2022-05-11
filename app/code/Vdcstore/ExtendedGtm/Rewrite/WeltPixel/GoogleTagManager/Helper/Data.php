<?php

/**
 * Vdcstore_ExtendedGtm
 *
 * @copyright   Copyright (c) 2021 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Ruslan Mukhatov <ruslan@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Vdcstore\ExtendedGtm\Rewrite\WeltPixel\GoogleTagManager\Helper;

use Magento\Framework\Session\SessionManagerInterface;
use Magento\Framework\Stdlib\Cookie\CookieMetadataFactory;
use Magento\Framework\Stdlib\CookieManagerInterface;

class Data extends \WeltPixel\GoogleTagManager\Helper\Data
{
    /**
     * Data constructor.
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Magento\Framework\View\Element\BlockFactory $blockFactory
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Catalog\Model\ResourceModel\Category\CollectionFactory $categoryCollectionFactory
     * @param \Magento\Catalog\Model\ResourceModel\Category $resourceCategory
     * @param \Magento\Framework\Escaper $escaper
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Magento\Sales\Api\OrderRepositoryInterface $orderRepository
     * @param \Magento\Checkout\Model\Session\SuccessValidator $checkoutSuccessValidator
     * @param \WeltPixel\GoogleTagManager\Model\Storage $storage
     * @param \Magento\Cookie\Helper\Cookie $cookieHelper
     * @param \Magento\Framework\App\CacheInterface $cache
     * @param \Magento\Framework\App\Cache\StateInterface $cacheState
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Framework\View\Element\BlockFactory $blockFactory,
        \Magento\Framework\Registry $registry,
        \Magento\Catalog\Model\ResourceModel\Category\CollectionFactory $categoryCollectionFactory,
        \Magento\Catalog\Model\ResourceModel\Category $resourceCategory,
        \Magento\Framework\Escaper $escaper,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
        \Magento\Checkout\Model\Session\SuccessValidator $checkoutSuccessValidator,
        \WeltPixel\GoogleTagManager\Model\Storage $storage,
        \Magento\Cookie\Helper\Cookie $cookieHelper,
        \Magento\Framework\App\CacheInterface $cache,
        \Magento\Framework\App\Cache\StateInterface $cacheState,
        \Magento\Checkout\Model\Cart $cartModel,
        \Magento\Customer\Model\Session $customer,
        CookieManagerInterface $cookieManager,
        CookieMetadataFactory $cookieMetadataFactory,
        SessionManagerInterface $sessionManager,
        \Magento\Framework\ObjectManagerInterface $objectManager
    ) {
        parent::__construct($context,
            $blockFactory,
            $registry,
            $categoryCollectionFactory,
            $resourceCategory,
            $escaper,
            $storeManager,
            $checkoutSession,
            $orderRepository,
            $checkoutSuccessValidator,
            $storage,
            $cookieHelper,
            $cache,
            $cacheState, $cookieManager, $cookieMetadataFactory, $sessionManager, $objectManager);
        $this->_gtmOptions = $this->scopeConfig->getValue('weltpixel_googletagmanager', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        $this->blockFactory = $blockFactory;
        $this->registry = $registry;
        $this->categoryCollectionFactory = $categoryCollectionFactory;
        $this->resourceCategory = $resourceCategory;
        $this->escaper = $escaper;
        $this->storeCategories = [];
        $this->storeManager = $storeManager;
        $this->checkoutSession = $checkoutSession;
        $this->orderRepository = $orderRepository;
        $this->checkoutSuccessValidator = $checkoutSuccessValidator;
        $this->storage = $storage;
        $this->cookieHelper = $cookieHelper;
        $this->cache = $cache;
        $this->cacheState = $cacheState;
        $this->cartModel = $cartModel;
        $this->customer = $customer;
    }

    /**
     * @return string
     */
    public function getDataLayerScript()
    {
        $script = '';

        if (!($block = $this->createBlock('Core', 'datalayer.phtml'))) {
            return $script;
        }

        $block->setNameInLayout('wp.gtm.datalayer.scripts');

        $this->addDefaultInformation();
        $this->addCategoryPageInformation();
        $this->addSearchResultPageInformation();
        $this->addProductPageInformation();
        $this->addCartPageInformation();
        $this->addCheckoutInformation();
        $this->addOrderInformation();

        $html = $block->toHtml();

        return $html;
    }

    public function addCartPageInformation()
    {
        $requestPath = $this->_request->getModuleName() .
            DIRECTORY_SEPARATOR . $this->_request->getControllerName() .
            DIRECTORY_SEPARATOR . $this->_request->getActionName();

        if ($requestPath == 'checkout/cart/index') {
            $cartBlock = $this->blockFactory->createBlock('\Vdcstore\ExtendedGtm\Rewrite\WeltPixel\GoogleTagManager\Block\Cart')
                ->setTemplate('Vdcstore_ExtendedGtm::cart.phtml');

            if ($cartBlock) {
                $quote = $this->cartModel->getQuote();
                $cartBlock->setQuote($quote);
                $cartBlock->toHtml();
            }
        }
    }

    /**
     * @param \Magento\Catalog\Model\Product $product
     * @return array
     */
    public function newsletterSubscriptionPushData($email)
    {
        $result = [];
        $result['event'] = 'emailSignup';
        $result['eventLabel'] = 'Newsletter';
        $result['ecommerce'] = [];
        $result['ecommerce']['subscription_email'] = $email;
        return $result;
    }

    public function addCheckoutInformation()
    {
        $requestPath = $this->_request->getModuleName() .
            DIRECTORY_SEPARATOR . $this->_request->getControllerName() .
            DIRECTORY_SEPARATOR . $this->_request->getActionName();

        if ($requestPath == 'checkout/index/index' || $requestPath == 'firecheckout/index/index') {
            $checkoutBlock = $this->blockFactory->createBlock('\Vdcstore\ExtendedGtm\Rewrite\WeltPixel\GoogleTagManager\Block\Checkout')
                ->setTemplate('Vdcstore_ExtendedGtm::checkout.phtml');

            if ($checkoutBlock) {
                $quote = $this->cartModel->getQuote();
                $checkoutBlock->setQuote($quote);
                $checkoutBlock->toHtml();
            }
        }
    }

    /**
     * Set product page detail infromation
     */
    public function addProductPageInformation()
    {
        $currentProduct = $this->getCurrentProduct();

        if (!empty($currentProduct)) {
            $productBlock = $this->blockFactory->createBlock('\WeltPixel\GoogleTagManager\Block\Product')
                ->setTemplate('WeltPixel_GoogleTagManager::product.phtml');

            if ($productBlock) {
                $productBlock->setCurrentProduct($currentProduct);
                $productBlock->toHtml();
            }
        }
    }

    public function addCheckoutStepPushData($step, $checkoutOption)
    {
        $checkoutStepResult = [];

        $checkoutStepResult['event'] = 'checkout';
        $checkoutStepResult['eventLabel'] = 'Checkout';
        $checkoutStepResult['ecommerce'] = [];
        $checkoutStepResult['ecommerce']['currencyCode'] = $this->getCurrencyCode();
        $checkoutStepResult['ecommerce']['checkout']['actionField'] = [
            'step'   => $step,
            'option' => $checkoutOption
        ];

        $products = [];
        $checkoutBlock = $this->blockFactory->createBlock('\Vdcstore\ExtendedGtm\Rewrite\WeltPixel\GoogleTagManager\Block\Checkout')
            ->setTemplate('Vdcstore_ExtendedGtm::checkout.phtml');

        if ($checkoutBlock) {
            $quote = $this->cartModel->getQuote();
            $checkoutBlock->setQuote($quote);
            $products = $checkoutBlock->getProducts();
        }

        $checkoutStepResult['ecommerce']['checkout']['products'] = $products;

        $checkoutOptionResult['event'] = 'checkoutOption';
        $checkoutOptionResult['eventLabel'] = 'Checkout Steps';
        $checkoutOptionResult['ecommerce'] = [];
        $checkoutOptionResult['ecommerce']['currencyCode'] = $this->getCurrencyCode();
        $checkoutOptionResult['ecommerce']['checkout_option'] = [];
        $optionData = [];
        $optionData['step'] = $step;
        $optionData['option'] = $checkoutOption;
        $checkoutOptionResult['ecommerce']['checkout_option']['actionField'] = $optionData;

        $result = [];
        $result[] = $checkoutStepResult;
        $result[] = $checkoutOptionResult;

        return $result;
    }

    /**
     * Returns category tree path
     * @param array $categoryIds
     * @return string
     */
    public function getGtmCategoryFromCategoryIds($categoryIds)
    {
        if (!count($categoryIds)) {
            return '';
        }
        if (empty($this->storeCategories)) {
            $this->populateStoreCategories();
        }
        $categoryId = $categoryIds[count($categoryIds) - 1];
        $categoryPath = '';
        if (isset($this->storeCategories[$categoryId])) {
            $categoryPath = $this->storeCategories[$categoryId]['path'];
        }
        return $this->buildCategoryPath($categoryPath);
    }

    public function buildCategoryPath($categoryPath)
    {
        /* first 2 categories can be ignored */
        $categoriIds = array_slice(explode('/', $categoryPath), 2);
        $categoriesWithNames = [];

        foreach ($categoriIds as $categoriId) {
            if (isset($this->storeCategories[$categoriId])) {
                $categoriesWithNames[] = $this->storeCategories[$categoriId]['name'];
            }
        }

        return implode('/', $categoriesWithNames);
    }

    /**
     * Get all categories id, name for the current store view
     */
    public function populateStoreCategories()
    {
        if (!$this->isEnabled() || !empty($this->storeCategories)) {
            return;
        }

        $rootCategoryId = $this->storeManager->getStore()->getRootCategoryId();
        $storeId = $this->storeManager->getStore()->getStoreId();

        $isWpGtmCacheEnabled = $this->cacheState->isEnabled(\WeltPixel\GoogleTagManager\Model\Cache\Type::TYPE_IDENTIFIER);
        $cacheKey = self::CACHE_ID_CATEGORIES . '-' . $rootCategoryId . '-' . $storeId;
        if ($isWpGtmCacheEnabled) {
            $this->_eventManager->dispatch('weltpixel_googletagmanager_cachekey_after', ['cache_key' => $cacheKey]);
            $cachedCategoriesData = $this->cache->load($cacheKey);
            if ($cachedCategoriesData) {
                $this->storeCategories = json_decode($cachedCategoriesData, true);
                return;
            }
        }
        $categories = $this->categoryCollectionFactory->create()
            ->setStoreId($storeId)
            ->addAttributeToFilter('path', ['like' => "1/{$rootCategoryId}/%"])
            ->addAttributeToSelect('name');
        foreach ($categories as $categ) {
            $this->storeCategories[$categ->getData('entity_id')] = [
                'name' => $categ->getData('name'),
                'path' => $categ->getData('path')
            ];
        }
        if ($isWpGtmCacheEnabled) {
            $cachedCategories = json_encode($this->storeCategories);
            $this->cache->save($cachedCategories, $cacheKey, [\WeltPixel\GoogleTagManager\Model\Cache\Type::CACHE_TAG]);
        }
    }
}
