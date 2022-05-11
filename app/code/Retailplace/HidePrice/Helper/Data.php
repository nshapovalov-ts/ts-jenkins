<?php

namespace Retailplace\HidePrice\Helper;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory;
use Magento\Customer\Model\SessionFactory;
use Magento\Customer\Model\Url;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\Http\Context as HttpContext;
use Magento\Framework\App\Request\Http;
use Magento\Framework\DataObject;
use Magento\Framework\Filter\FilterManager;
use Magento\Framework\Json\EncoderInterface;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\StoreManagerInterface;
use Retailplace\CustomerAccount\Model\ApprovalContext;

class Data extends \Amasty\HidePrice\Helper\Data
{
    /**
     * @var CollectionFactory
     */
    private $categoryCollectionFactory;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var EncoderInterface
     */
    private $jsonEncoder;
    /**
     * @var SessionFactory
     */
    private $sessionFactory;

    /**
     * @var FilterManager
     */
    private $filterManager;

    /**
     * @var Url
     */
    private $customerUrl;

    /**
     * @var HttpContext
     */
    protected $httpContext;

    /**
     * Data constructor.
     * @param HttpContext $httpContext
     * @param Context $context
     * @param SessionFactory $sessionFactory
     * @param StoreManagerInterface $storeManager
     * @param EncoderInterface $jsonEncoder
     * @param CollectionFactory $categoryCollectionFactory
     * @param FilterManager $filterManager
     * @param Url $customerUrl
     * @param Http $request
     */
    public function __construct(
        HttpContext $httpContext,
        Context $context,
        SessionFactory $sessionFactory,
        StoreManagerInterface $storeManager,
        EncoderInterface $jsonEncoder,
        CollectionFactory $categoryCollectionFactory,
        FilterManager $filterManager,
        Url $customerUrl,
        Http $request
    ) {
        parent::__construct(
            $context,
            $sessionFactory,
            $storeManager,
            $jsonEncoder,
            $categoryCollectionFactory,
            $filterManager,
            $customerUrl,
            $request
        );
        $this->httpContext = $httpContext;
        $this->categoryCollectionFactory = $categoryCollectionFactory;
        $this->storeManager = $storeManager;
        $this->jsonEncoder = $jsonEncoder;
        $this->sessionFactory = $sessionFactory;
        $this->filterManager = $filterManager;
        $this->customerUrl = $customerUrl;
    }

    private function getCustomerSession()
    {
        return $this->sessionFactory->create();
    }

    /**
     * @param ProductInterface $product
     * @return bool
     */
    public function isApplied(ProductInterface $product)
    {
        if (!$this->isModuleEnabled()) {
            return false;
        }
        if (!$this->issetCachedResult($product->getId())) {
            /* Checking settings by product and customer group. Order is important.*/
            $result = $this->checkGlobalSettings($product);
            $result = $this->checkCustomerAttributes($result, $product);
            $result = $this->checkStockStatus($result, $product);
            /**
             * Disabled category checks to improve performance
             */
            //$result = $this->checkCategorySettings($result, $product);
            $result = $this->checkProductSettings($result, $product);
            $result = $this->checkIgnoreSettings($result, $product);
            /* save result to cache for multiple places: price button add to wishlist and other*/
            $this->saveResultToCache($result, $product->getId());
        } else {
            $result = $this->getResultFromCache($product->getId());
        }

        return $result;
    }

    /**
     * Hide Price depend on selected categories and customer groups in configuration
     * @param ProductInterface $product
     * @return bool
     */
    private function checkGlobalSettings(ProductInterface $product)
    {
        $result = false;

        $settingCustomerGroup = $this->convertStringSettingToArray('general/customer_group');
        if (in_array($this->currentCustomerGroup, $settingCustomerGroup)) {
            $productCategories = $product->getCategoryIds();
            $settingCategories = $this->convertStringSettingToArray('general/category');

            //check for root category - hide price for all
            $result = in_array(self::ROOT_CATEGORY_ID, $settingCategories)
            || count(array_uintersect($productCategories, $settingCategories, "strcmp")) > 0
                ? true : false;
        }

        return $result;
    }

    /**
     *  Hide Price depend on selected individual category settings
     * @param $result
     * @param ProductInterface $product
     * @return bool
     */
    private function checkCategorySettings($result, ProductInterface $product)
    {
        $productCategories = $product->getCategoryIds();
        if (!$this->matchedCategories) {
            /* get categories only with not empty attributes customer_gr_cat and mode_cat */
            $collection =  $this->categoryCollectionFactory->create()
                ->addAttributeToSelect('am_hide_price_mode_cat')
                ->addAttributeToSelect('am_hide_price_customer_gr_cat')
                ->addAttributeToFilter('am_hide_price_mode_cat', ['notnull' => true])
                ->addAttributeToFilter('am_hide_price_customer_gr_cat', ['notnull' => true]);
            $this->matchedCategories = $collection->getData();
        }

        if (!empty($this->matchedCategories)) {
            foreach ($this->matchedCategories as $category) {
                if (!in_array($category['entity_id'], $productCategories)) {
                    continue;
                }
                $customerGroups = $this->trimAndExplode($category['am_hide_price_customer_gr_cat']);
                if (in_array($this->currentCustomerGroup, $customerGroups)) {
                    $result = !(bool)$category['am_hide_price_mode_cat'];
                }
            }
        }

        return $result;
    }

    /**
     *  Hide Price depend on selected individual product settings
     * @param $result
     * @param ProductInterface $product
     * @return bool
     */
    private function checkProductSettings($result, ProductInterface $product)
    {
        $mode = $product->getData('am_hide_price_mode');
        $customerGroups = $product->getData('am_hide_price_customer_gr');

        if ($mode !== null && $customerGroups) {
            $customerGroups = $this->trimAndExplode($customerGroups);
            if (in_array($this->currentCustomerGroup, $customerGroups)) {
                $result = !(bool)$mode;
            }
        }

        return $result;
    }

    /**
     * Check ignore settings - the most important
     * @param $result
     * @param ProductInterface $product
     * @return bool
     */
    private function checkIgnoreSettings($result, ProductInterface $product)
    {
        $currentCustomerId = $this->getCustomerSession()->getCustomerId();
        if ($currentCustomerId) {
            $ignoredCustomers = $this->convertStringSettingToArray('general/ignore_customer');
            if (in_array($currentCustomerId, $ignoredCustomers)) {
                return false;
            }
        }

        $ignoredProductIds = $this->convertStringSettingToArray('general/ignore_products');
        if (in_array($product->getId(), $ignoredProductIds)) {
            return false;
        }

        return $result;
    }

    /**
     * Generate button html depend on module configuration
     * @param $product
     * @return string
     */
    public function getNewPriceHtmlBox($product)
    {
        // help for magento swatches detect category page
        $html = sprintf('<div class="price-box price-final_price" data-product-id="%d"></div>', $product->getId());

        $text = $this->filterManager->stripTags(
            $this->getModuleConfig('frontend/text'),
            [
                'allowableTags' => null,
                'escape' => true
            ]
        );

        $image = $this->getModuleConfig('frontend/image');
        if ($text || $image) {
            $href = (string)$this->getModuleConfig('frontend/link');
            if ($href) {
                if ($href == self::HIDE_PRICE_POPUP_IDENTIFICATOR) {
                    $tag = $this->generatePopup($product);
                } elseif ($href == self::SIGN_UP_POPUP_IDENTIFICATOR) {
                    $tag = '<a href="javascript:void(0)" id="' . self::SIGN_UP_POPUP_IDENTIFICATOR . '" ';
                } else {
                    $href = $this->checkLoginUrl($href);
                    $tag = '<a href="' . $this->storeManager->getStore()->getBaseUrl() . $href . '" ';
                }
                $closeTag = '</a>';
            } else {
                $tag = '<div ';
                $closeTag = '</div>';
            }

            $customStyles = $this->getModuleConfig('frontend/custom_css');
            if ($customStyles) {
                $customStyles = 'style="' . $customStyles . '"';
            }

            if ($this->_request->getFullActionName() =='catalog_product_view') {
                $html .= '<div class="signup-before-block-bg"><div class="signup-before">Sign up to see wholesale pricing and make an order</div>';
            }
            //Override for approval context
            $approvalContext = (bool) $this->httpContext->getValue(ApprovalContext::APPROVAL_CONTEXT);
            $isLogged = (bool) $this->httpContext->getValue(\Magento\Customer\Model\Context::CONTEXT_AUTH);
            $url = '';
            if ($isLogged && !$approvalContext) {
                $url = $this->_getUrl('customer/account/edit');
            }
            if ($url) {
                $tag = '<a href="' . $url . '" ';
                $closeTag = '</a>';
            }

            $html .= $tag . ' class="amasty-hide-price-container" ' . $customStyles . '>';

            if ($image) {
                $mediaPath = $this->storeManager->getStore()->getBaseUrl(UrlInterface::URL_TYPE_MEDIA);
                $image = $mediaPath . '/amasty/hide_price/' . $image;
                $html .= '<img class="amasty-hide-price-image" src="' . $image . '">';
            }

            if ($text) {
                if ($isLogged && !$approvalContext) {
                    $html .= '<span class="amasty-hide-price-text-other">' . __('Complete the application form
                    ') . '</span>';
                } else {
                    if ($this->_request->getFullActionName() =='catalog_product_view') {
                        $html .= '<span class="amasty-hide-price-text">' . $text . '</span>';
                    } else {
                        $html .= '<span class="amasty-hide-price-text-other">' . __('Sign up to see wholesale pricing
                    ') . '</span>';
                    }
                }
            }

            $html .= $closeTag;

            if ($this->_request->getFullActionName() =='catalog_product_view') {
                $html .= '<div class="toolpick">
            <span>Business only</span>
                        <div class="toolpick-ico">
                            <i>i</i>
                             <div class="toolpick-text">
                            <p>Only businesses and organizations can buy products on TradeSquare. We block consumer access to protect the brands and businesses who buy on TradeSquare. Schools, universities, governments and other organizations are eligible to buy on TradeSquare. Amazon resellers and 3rd party marketplace suppliers are not eligible to purchase on TradeSquare. To see wholesale pricing information, you must be logged in.</p>
                        </div>
                        </div>
                        </div>
                        </div>';
            }
        }
        //return ($this->_request->getFullActionName() != 'catalog_category_view') ? $html : '';
        //return ($this->_request->getFullActionName() == 'catalog_product_view') ? $html : '';
        return $html;
    }

    /**
     * generate Js code for Get a Quote Form
     * @param Product|DataObject $product
     * @return string
     */
    private function generateFormJs($product)
    {
        $js = '<script>';
        $js .= 'require([
                "jquery",
                 "Amasty_HidePrice/js/amhidepriceForm"
            ], function ($, amhidepriceForm) {
                amhidepriceForm.addProduct(' . $this->generateFormConfig($product) . ');
            });';
        $js .= '</script>';

        return $js;
    }

    private function generateFormConfig($product)
    {
        $customer = $this->getCustomerSession()->getCustomer();
        return $this->jsonEncoder->encode([
            'url' => $this->_getUrl('amasty_hide_price/request/add'),
            'id' => $product->getId(),
            'name'   => $product->getName(),
            'customer' => [
                'name'  => $customer->getName(),
                'email' => $customer->getEmail(),
                'phone' => $customer->getPhone()
            ]
        ]);
    }

    private function convertStringSettingToArray($name)
    {
        $setting = $this->getModuleConfig($name);
        $setting = $this->trimAndExplode($setting);

        return $setting;
    }

    /**
     * @param $string
     * @return array
     */
    private function trimAndExplode($string)
    {
        $string = str_replace(' ', '', $string);
        $array = explode(',', $string);

        return $array;
    }

    /**
     * @param $productId
     * @return bool
     */
    private function issetCachedResult($productId)
    {
        if (!array_key_exists($this->currentCustomerGroup, $this->cache)) {
            return false;
        }

        return array_key_exists($productId, $this->cache[$this->currentCustomerGroup]);
    }

    /**
     * @param $productId
     * @return mixed
     */
    private function getResultFromCache($productId)
    {
        return $this->cache[$this->currentCustomerGroup][$productId];
    }

    /**
     * @param $result
     * @param $productId
     */
    private function saveResultToCache($result, $productId)
    {
        if (!array_key_exists($this->currentCustomerGroup, $this->cache)) {
            $this->cache[$this->currentCustomerGroup] = [];
        }

        $this->cache[$this->currentCustomerGroup][$productId] = $result;
    }

    /**
     * @param Product|DataObject $product
     * @return string
     */
    private function generatePopup($product)
    {
        $popupHtml = $this->generateFormJs($product)
            . '<a data-product-id="' . $product->getId() . '" data-amhide="'
            . self::HIDE_PRICE_POPUP_IDENTIFICATOR . '" ';

        return $popupHtml;
    }
}
