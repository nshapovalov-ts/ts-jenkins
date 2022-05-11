<?php
/**
 * Retailplace_Recentview
 *
 * @copyright   Copyright © 2021 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Satish Gumudavelly <satish@kipanga.com.au>
 * @author      Dmitriy Fionov <dmitriy@tradesquare.com.au>
 * @author      Evgeniy Derevyanko <evgeniy@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\Recentview\Block;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Block\Product\AbstractProduct;
use Magento\Catalog\Block\Product\Context;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\Customer\Model\Session;
use Magento\Framework\App\ActionInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Serialize\Serializer\Json as SerializerJson;
use Magento\Reports\Block\Product\Viewed as ReportProductViewed;
use Magento\Review\Model\Review;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Retailplace\ChannelPricing\Api\Data\ProductAttributesInterface;
use Retailplace\ChannelPricing\Model\AttributesVisibilityManagement;
use Retailplace\MiraklPromotion\Model\PromotionManagement;
use Retailplace\SellerTags\Api\Data\SellerTagsAttributes;
use Zend_Db_Expr;
use Magento\Framework\Url\EncoderInterface;
use Magento\Framework\App\Response\RedirectInterface;
use Magento\Framework\Phrase;
use Exception;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\View\Element\RendererList;
use Magento\Framework\View\Element\BlockInterface;
use Magento\Framework\View\Element\AbstractBlock;
use Magento\Framework\View\Element\Template;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;

/**
 * Class ListingTabsRecentlyViewed
 */
class ListingTabsRecentlyViewed extends AbstractProduct
{
    /**
     * Cache Tag
     */
    const CACHE_TAGS = 'SM_LISTING_TABS';
    /**
     * Limit for product collection
     */
    const LIMIT = 8;

    /**
     * @var ObjectManagerInterface
     */
    protected $_objectManager;

    /**
     * @var ResourceConnection
     */
    protected $_resource;

    /**
     * @var Visibility
     */
    protected $_catalogProductVisibility;

    /**
     * @var Review
     */
    protected $_review;

    /**
     * @var SerializerJson
     */
    private $jsonSerializer;

    /**
     * @var ReportProductViewed
     */
    protected $reportProductViewed;

    /**
     * @var Session
     */
    private $customerSession;

    /**
     * @var StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @var ScopeConfigInterface
     */
    protected $_scopeConfig;

    /**
     * @var string
     */
    protected $_storeId;

    /**
     * @var string
     */
    protected $_storeCode;

    /**
     * @var array
     */
    protected $_config = null;

    /**
     * @var Collection
     */
    private $collection;
    /**
     * @var EncoderInterface
     */
    private $urlEncoder;
    /**
     * @var RedirectInterface
     */
    private $redirect;
    /**
     * @var array
     */
    private $wislistItems;

    /**
     * @var AttributesVisibilityManagement
     */
    private $attributesVisibilityManagement;

    /**
     * @var \Retailplace\MiraklPromotion\Model\PromotionManagement
     */
    private $promotionManagement;

    /**
     * @var array
     */
    private $sellerPromotions;

    /** @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface */
    private $timezone;

    /**
     * @var array
     */
    private $productList = [];

    /**
     * ListingTabsRecentlyViewed constructor.
     *
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     * @param \Magento\Framework\App\ResourceConnection $resource
     * @param \Magento\Catalog\Model\Product\Visibility $catalogProductVisibility
     * @param \Magento\Review\Model\Review $review
     * @param \Magento\Catalog\Block\Product\Context $context
     * @param \Magento\Framework\Serialize\Serializer\Json $jsonSerializer
     * @param \Magento\Reports\Block\Product\Viewed $reportProductViewed
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Framework\Url\EncoderInterface $urlEncoder
     * @param \Magento\Framework\App\Response\RedirectInterface $redirect
     * @param \Retailplace\ChannelPricing\Model\AttributesVisibilityManagement $attributesVisibilityManagement
     * @param \Retailplace\MiraklPromotion\Model\PromotionManagement $promotionManagement
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone
     * @param array $data
     * @param null $attr
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function __construct(
        ObjectManagerInterface $objectManager,
        ResourceConnection $resource,
        Visibility $catalogProductVisibility,
        Review $review,
        Context $context,
        SerializerJson $jsonSerializer,
        ReportProductViewed $reportProductViewed,
        Session $customerSession,
        EncoderInterface $urlEncoder,
        RedirectInterface $redirect,
        AttributesVisibilityManagement $attributesVisibilityManagement,
        PromotionManagement $promotionManagement,
        TimezoneInterface $timezone,
        array $data = [],
        $attr = null
    ) {
        parent::__construct($context, $data);
        $this->_objectManager = $objectManager;
        $this->_resource = $resource;
        $this->_storeManager = $context->getStoreManager();
        $this->_scopeConfig = $context->getScopeConfig();
        $this->_catalogProductVisibility = $catalogProductVisibility;
        $this->_storeId = (int) $this->_storeManager->getStore()->getId();
        $this->_storeCode = $this->_storeManager->getStore()->getCode();
        $this->jsonSerializer = $jsonSerializer;
        $this->_review = $review;
        $this->reportProductViewed = $reportProductViewed;
        $this->customerSession = $customerSession;
        if ($context->getRequest() && $context->getRequest()->isAjax()) {
            $this->_config = $context->getRequest()->getParam('config');
        } else {
            $this->_config = $this->_getCfg($attr, $data);
        }
        $this->urlEncoder = $urlEncoder;
        $this->redirect = $redirect;
        $this->attributesVisibilityManagement = $attributesVisibilityManagement;
        $this->promotionManagement = $promotionManagement;
        $this->timezone = $timezone;
    }

    public function _getCfg($attr = null, $data = null)
    {
        $defaults = [];
        $_cfg_xml = $this->_scopeConfig->getValue('listingtabs', ScopeInterface::SCOPE_STORE, $this->_storeCode);
        if (empty($_cfg_xml)) {
            return;
        }
        $groups = [];
        foreach ($_cfg_xml as $def_key => $def_cfg) {
            $groups[] = $def_key;
            foreach ($def_cfg as $_def_key => $cfg) {
                $defaults[$_def_key] = $cfg;
            }
        }

        if (empty($groups)) {
            return;
        }
        $cfgs = [];
        foreach ($groups as $group) {
            $_cfgs = $this->_scopeConfig->getValue('listingtabs/' . $group . '', ScopeInterface::SCOPE_STORE, $this->_storeCode);
            foreach ($_cfgs as $_key => $_cfg) {
                $cfgs[$_key] = $_cfg;
            }
        }

        if (empty($defaults)) {
            return;
        }
        $configs = [];
        foreach ($defaults as $key => $def) {
            if (isset($defaults[$key])) {
                $configs[$key] = $cfgs[$key];
            } else {
                unset($cfgs[$key]);
            }
        }
        $cf = ($attr != null) ? array_merge($configs, $attr) : $configs;
        $this->_config = ($data != null) ? array_merge($cf, $data) : $cf;
        return $this->_config;
    }

    /**
     * @return array
     * @throws NoSuchEntityException
     */
    public function getCacheKeyInfo()
    {
        $params = $this->getRequest()->getParams();
        return [
            'BLOCK_TPL_SM_LISTING_TABS',
            $this->_storeManager->getStore()->getCode(),
            $this->_storeManager->getStore()->getId(),
            $this->_getNameLayout(),
            $this->getTemplateFile(),
            'base_url' => $this->getBaseUrl(),
            'template' => $this->getTemplate(),
            $this->jsonSerializer->serialize($params)
        ];
    }

    /**
     * @return string
     */
    private function _getNameLayout()
    {
        $name_layout = $this->getNameInLayout();
        if ($this->_isAjax()) {
            $name_layout = $this->getRequest()->getPost('moduleid');
        }
        return $name_layout;
    }

    /**
     * @return bool
     */
    public function _isAjax(): bool
    {
        $isAjax = $this->getRequest()->isAjax();
        $is_ajax_listing_tabs = $this->getRequest()->getPost('is_ajax_listing_tabs');
        if ($isAjax && $is_ajax_listing_tabs == 1) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * @param Product $product
     * @return mixed|string
     */
    public function getProductDetailsHtml(Product $product)
    {
        $renderer = $this->getDetailsRenderer($product->getTypeId());
        if ($renderer) {
            $renderer->setProduct($product);
            return $renderer->toHtml();
        }
        return '';
    }

    /**
     * @param null $type
     * @return bool|AbstractBlock|Template|null
     */
    public function getDetailsRenderer($type = null)
    {
        if ($type === null || $type !== 'configurable') {
            $type = 'default';
            return null;
        }
        $rendererList = $this->getDetailsRendererList();
        if ($rendererList) {
            return $rendererList->getRenderer($type, 'default');
        }
        return null;
    }

    /**
     * @return bool|AbstractBlock|BlockInterface|RendererList
     * @throws LocalizedException
     */
    protected function getDetailsRendererList()
    {
        $name_layout = $this->getNameInLayout();
        if ($this->_isAjax()) {
            $name_layout = $this->getRequest()->getPost('moduleid');
        }
        return $this->getDetailsRendererListName() ? $this->getLayout()->getBlock(
            $this->getDetailsRendererListName()
        ) : $this->getChildBlock(
            $name_layout . '.details.renderers'
        );
    }

    /**
     * @return array
     * @throws Exception
     */
    public function _getList(): array
    {
        $this->_config = $this->_getCfg(null, $this->getData());
        $type_show = $this->_getConfig('type_show');
        $type_listing = $this->_getConfig('type_listing');
        $under_price = $this->_getConfig('under_price');
        $tabs_select = $this->_getConfig('tabs_select');
        $category_select = $this->_getConfig('category_select');
        $order_by = $this->_getConfig('order_by');
        $order_dir = $this->_getConfig('order_dir');
        $limitation = $this->_getConfig('limitation');
        $type_filter = $this->_getConfig('type_filter');
        $category_id = $this->_getConfig('category_tabs');

        $field_tabs = $this->_getConfig('field_tabs');
        $list = [];
        $cat_filter = [];
        switch ($type_filter) {
            case 'categories':
                if (!empty($category_id)) {
                    $catids = explode(',', $category_id);
                    $all_childrens = $this->_getAllChildren($catids);
                    if (!empty($all_childrens)) {
                        $flag = true;
                        foreach ($all_childrens as $key => $children) {
                            $cat_children = implode(',', $children);
                            $object_manager = $this->_objectManager->create('Magento\Catalog\Model\Category')->load($key);
                            $list[$key]['name_tab'] = $object_manager->getName();
                            $list[$key]['id_tab'] = $key;
                            $list[$key]['cat_children'] = $cat_children;
                            if ($flag) {
                                $list[$key]['sel'] = 'active';
                                $list[$key]['products_list'] = $this->_getProductsBasic($children);
                                $flag = false;
                            }
                        }
                    }
                }
                break;
            case 'fieldproducts':
                if (!empty($category_select)) {
                    $catids = explode(',', $category_select);
                    $all_childrens = $this->_getAllChildren($catids, true);
                    if (!empty($field_tabs)) {
                        $tabs = explode(',', $field_tabs);
                        $flag = true;
                        foreach ($tabs as $key => $tab) {
                            $list[$tab]['name_tab'] = $this->getLabel($tab);
                            $list[$tab]['id_tab'] = $tab;
                            $list[$tab]['cat_children'] = implode(',', $all_childrens);
                            if ($flag) {
                                $list[$tab]['sel'] = 'active';
                                $list[$tab]['products_list'] = $this->_getProductsBasic($all_childrens, $tab);
                                $flag = false;
                            }
                        }
                    }
                }
                break;
        }

        return $list;
    }

    /**
     * @param null $name
     * @param null $value_def
     * @return array|mixed|void|null
     */
    public function _getConfig($name = null, $value_def = null)
    {
        if (is_null($this->_config)) {
            $this->_getCfg();
        }
        if (!is_null($name)) {
            $value_def = isset($this->_config[$name]) ? $this->_config[$name] : $value_def;
            return $value_def;
        }
        return $this->_config;
    }

    /**
     * @param $name
     * @param null $value
     * @return bool|void
     */
    public function _setConfig($name, $value = null)
    {
        if (is_null($this->_config)) {
            $this->_getCfg();
        }
        if (is_array($name)) {
            $this->_config = array_merge($this->_config, $name);

            return;
        }
        if (!empty($name) && isset($this->_config[$name])) {
            $this->_config[$name] = $value;
        }
        return true;
    }

    /**
     * @param $catids
     * @param false $group
     * @return array
     */
    private function _getAllChildren($catids, $group = false): array
    {
        $list = [];
        $cat_tmps = '';
        !is_array($catids) && $catids = preg_split('/[\s|,|;]/', $catids, -1, PREG_SPLIT_NO_EMPTY);
        if (!empty($catids) && is_array($catids)) {
            foreach ($catids as $i => $catid) {
                $object_manager = $this->_objectManager->create('Magento\Catalog\Model\Category')->load($catid);
                if ($group) {
                    $cat_tmps .= $object_manager->getAllChildren() . ($i < count($catids) - 1 ? ',' : '');
                } else {
                    $list[$catid] = $object_manager->getAllChildren(true);
                }
            }
            if ($group) {
                if (!empty($cat_tmps)) {
                    $list = explode(',', $cat_tmps);
                    return array_unique($list);
                }
            }
        }
        return $list;
    }

    /**
     * @param null $catids
     * @param string $tab
     * @return Collection|mixed
     * @throws Exception
     */
    public function _getProductsBasic($catids = null, $tab = false)
    {
        if ($this->collection == null) {
            $type_filter = $this->_getConfig('type_filter');
            $limit = self::LIMIT;
            $type_listing = $this->_getConfig('type_listing');
            $under_price = $this->_getConfig('under_price', '4.99');
            $catids = $catids == null ? $this->_getConfig('category_tabs') : $catids;
            $productIds = $this->_getConfig('product_ids');

            !is_array($catids) && $catids = preg_split('/[\s|,|;]/', $catids, -1, PREG_SPLIT_NO_EMPTY);
            $collection = $this->_objectManager->create(Collection::class);
            $connection = $this->_resource->getConnection();
            if ($type_listing == 'under') {
                $collection->addPriceDataFieldFilter('%s < %s', ['min_price', $under_price]);
            }
            $collection->addMinimalPrice()
                ->addFinalPrice()
                ->addTaxPercents()
                ->addAttributeToSelect($this->_catalogConfig->getProductAttributes())
                ->addAttributeToSelect('special_from_date')
                ->addAttributeToSelect('special_to_date')
                ->addUrlRewrite()
                ->setStoreId($this->_storeId)
                ->addAttributeToFilter('status', Status::STATUS_ENABLED);

            $collection->addAttributeToFilter('is_saleable', ['eq' => 1], 'left');
            $collection->setVisibility($this->_catalogProductVisibility->getVisibleInCatalogIds());
            $this->_getViewedCount($collection);
            $this->_getOrderedQty($collection);
            $this->_getReviewsCount($collection);

            $collection->getSelect()
                ->joinLeft(
                    ["rvpi" => $this->_resource->getTableName('report_viewed_product_index')],
                    "e.entity_id = rvpi.product_id AND rvpi.store_id=" . $this->_storeId,
                    ["rvpi.product_id"]
                )->where("(rvpi.visitor_id IS NOT NULL OR rvpi.customer_id IS NOT NULL)");

            if ($customerId = $this->customerSession->getCustomer()->getId()) {
                $collection->getSelect()->where("rvpi.customer_id = ?", $customerId);
            } else {
                $productIds = explode(",", $productIds);
                $collection->addAttributeToFilter('entity_id', ['in' => $productIds]);
            }
            $lastLoginSelect = $connection->select()
                ->from(['re' => $this->_resource->getTableName('report_event')], 'logged_at')
                ->where("re.object_id = rvpi.product_id")
                ->where("re.store_id = rvpi.store_id")
                ->where("re.subject_id = rvpi.customer_id or re.subject_id = rvpi.visitor_id")
                ->order("re.logged_at DESC")
                ->limit(1);

            $collection->getSelect()->columns(['logged_at' => new Zend_Db_Expr("($lastLoginSelect)")]);
            $collection->getSelect()->order("logged_at DESC");
            $collection->getSelect()->order("rvpi.added_at DESC");
            $tab ? $this->_getOrderFields($collection, $tab) : $this->_getOrderFields($collection);
            $collection->clear();
            $collection->getSelect()->distinct(true)->group('e.entity_id');
            $start = (int) $this->getRequest()->getPost('ajax_listingtabs_start');

            if (!$start) {
                $start = 0;
            }
            $_limit = $limit;
            $_limit = $_limit <= 0 ? 0 : $_limit;
            $collection->getSelect()->limit($_limit, $start);
            $this->collection = $collection;
        }
        return $this->collection;
    }

    /**
     * @param $collection
     */
    private function _getViewedCount(&$collection): void
    {
        $connection = $this->_resource->getConnection();
        $select = $connection
            ->select()
            ->from($connection->getTableName($this->_resource->getTableName('report_event')), ['*', 'num_view_counts' => 'COUNT(`event_id`)'])
            ->where("event_type_id = 1 AND store_id=" . $this->_storeId . "")
            ->group('object_id');
        $collection->getSelect()
            ->joinLeft(
                ['mv' => $select],
                'mv.object_id = e.entity_id'
            );
    }

    /**
     * @param $collection
     */
    private function _getOrderedQty(&$collection): void
    {
        $connection = $this->_resource->getConnection();
        $select = $connection
            ->select()
            ->from($connection->getTableName($this->_resource->getTableName('sales_bestsellers_aggregated_monthly')), ['product_id', 'ordered_qty' => 'SUM(`qty_ordered`)'])
            ->where("store_id=" . $this->_storeId . "")
            ->group('product_id');

        $collection->getSelect()
            ->joinLeft(
                ['bs' => $select],
                'bs.product_id = e.entity_id'
            );
    }

    /**
     * @param $collection
     */
    private function _getReviewsCount(&$collection): void
    {
        $connection = $this->_resource->getConnection();
        $collection->getSelect()
            ->joinLeft(
                ["ra" => $connection->getTableName($this->_resource->getTableName('review_entity_summary'))],
                "e.entity_id = ra.entity_pk_value AND ra.store_id=" . $this->_storeId,
                [
                    'num_reviews_count'  => "ra.reviews_count",
                    'num_rating_summary' => "ra.rating_summary"
                ]
            );
    }

    /**
     * @param $collection
     * @param string $tab
     * @return mixed
     */
    public function _getOrderFields(&$collection, $tab = false)
    {
        $order_by = $tab ? $tab : $this->_getConfig('order_by');
        $order_dir = $this->_getConfig('order_dir');
        switch ($order_by) {
            default:
            case 'entity_id':
            case 'name':
                $collection->addAttributeToSort($order_by, $order_dir);
                break;
            case 'lastest_products':
            case 'created_at':
                $tab ? $collection->getSelect()->order('created_at  DESC') : $collection->getSelect()->order('created_at ' . $order_dir . '');
                break;
            case 'price':
                $collection->getSelect()->order('final_price ' . $order_dir . '');
                break;
            case 'num_rating_summary':
                $tab ? $collection->getSelect()->order('num_rating_summary DESC') : $collection->getSelect()->order('num_rating_summary ' . $order_dir . '');
                break;
            case 'num_reviews_count':
                $tab ? $collection->getSelect()->order('num_reviews_count DESC') : $collection->getSelect()->order('num_reviews_count ' . $order_dir . '');
                break;
            case 'num_view_counts':
                $tab ? $collection->getSelect()->order('num_view_counts DESC') : $collection->getSelect()->order('num_view_counts ' . $order_dir . '');
                break;
            case 'ordered_qty':
                $tab ? $collection->getSelect()->order('ordered_qty DESC') : $collection->getSelect()->order('ordered_qty ' . $order_dir . '');
                break;

        }

        return $collection;
    }

    /**
     * @param $filter
     * @return Phrase
     */
    public function getLabel($filter): Phrase
    {
        switch ($filter) {
            case 'name':
                return __('Name');
            case 'entity_id':
                return __('Id');
            case 'price':
                return __('Price');
            case 'lastest_products':
                return __('New Products');
            case 'num_rating_summary':
                return __('Top Rating');
            case 'num_reviews_count':
                return __('Most Reviews');
            case 'num_view_counts':
                return __('Most Viewed');
            case 'ordered_qty':
                return __('Most Selling');
        }
    }

    /**
     * @param Product $product
     * @return array
     */
    public function getAddToCartPostParams(Product $product): array
    {
        $url = $this->getAddToCartUrl($product);
        return [
            'action' => $url,
            'data'   => [
                'product'                               => $product->getEntityId(),
                ActionInterface::PARAM_NAME_URL_ENCODED => $this->_objectManager->get('\Magento\Framework\Url\Helper\Data')->getEncodedUrl($url),
            ]
        ];
    }

    /**
     * @return string
     * @throws NoSuchEntityException
     */
    public function getAjaxUrl(): string
    {
        return $this->_storeManager->getStore()->getBaseUrl() . 'listingtabs/index/index';
    }

    public function _setSerialize($str)
    {
        $serializer = $this->_objectManager->get('\Magento\Framework\Serialize\Serializer\Json');
        if (!empty($str)) {
            $items = $serializer->serialize($str);
            return $items;
        }
        return true;
    }

    /**
     * Resource initialization
     */
    protected function _construct()
    {
        parent::_construct();
        $this->addData(
            [
                'cache_lifetime' => null,
                'cache_tags'     => [self::CACHE_TAGS]
            ]
        );
    }

    protected function _prepareLayout()
    {
        $name_layout = $this->_getNameLayout();
        $this->getLayout()->addBlock(
            'Magento\Framework\View\Element\RendererList',
            $name_layout . '.renderlist',
            $this->getNameInLayout(),
            $name_layout . '.details.renderers'
        );
        $this->getLayout()->addBlock(
            'Sm\ListingTabs\Block\Product\Renderer\Listing\Configurable',
            $name_layout . '.colorswatches',
            $name_layout . '.renderlist',
            'configurable'
        )->setTemplate('Sm_ListingTabs::product/listing/renderer.phtml')->setData(['tagid' => $this->_tagId()]);
    }

    public function _tagId()
    {
        $tag_id = $this->_getNameLayout();
        $tag_id = strpos($tag_id, '.') !== false ? str_replace('.', '_', $tag_id) : $tag_id;
        return $tag_id;
    }

    protected function _toHtml()
    {
        if (!(int) $this->_getConfig('isactive', 1)) {
            return;
        }
        if ($this->_isAjax()) {
            $datacustom_content = $this->getRequest()->getPost('datacustomcontent');

            if ($datacustom_content == 'data-custom-content') {
                $template_file = "default_items_v3.phtml";
            } elseif ($datacustom_content == 'data-custom-left') {
                $template_file = "default_items_v4.phtml";
            } elseif ($datacustom_content == 'data-custom-center') {
                $template_file = "default_items_v6.phtml";
            } else {
                $template_file = "default_items.phtml";
            }
        } else {
            $template_file = $this->getTemplate();
            $template_file = (!empty($template_file)) ? $template_file : "Sm_ListingTabs::default.phtml";
        }
        $this->setTemplate($template_file);
        return parent::_toHtml();
    }

    private function isHomepage()
    {
        $objectManager = ObjectManager::getInstance();
        $request = $objectManager->get('Magento\Framework\App\Action\Context')->getRequest();
        if ($request->getFullActionName() == 'cms_index_index') {
            return true;
        }
        return true;
    }

    /**
     * Retrieve add to wishlist params
     *
     * @param Product $product
     * @return string
     */
    public function getAddToWishlistParams($product): string
    {
        if ($this->redirect->getRefererUrl()) {
            return $this->_wishlistHelper->getAddParams($product, [
                ActionInterface::PARAM_NAME_URL_ENCODED => $this->urlEncoder->encode($this->redirect->getRefererUrl())
            ]);
        }
        return parent::getAddToWishlistParams($product);
    }

    /**
     * Get wishlist item ids
     *
     * @return array
     */
    public function getWislistItemIds()
    {
        if ($this->wislistItems == null) {
            $collection = $this->_wishlistHelper->getWishlistItemCollection();
            $collection->clear()
                ->setInStockFilter(true)
                ->setOrder('added_at');
            $this->wislistItems = $collection->getColumnValues('product_id');
        }
        return $this->wislistItems;
    }

    public function productIsAuPostExclusive(ProductInterface $product): bool
    {
        $isAttributeVisible = $this->attributesVisibilityManagement
            ->checkAttributeVisibility(ProductAttributesInterface::AU_POST_EXCLUSIVE);

        return $isAttributeVisible && $product->getData(ProductAttributesInterface::AU_POST_EXCLUSIVE);
    }

    public function productIsNlnaExclusive(ProductInterface $product): bool
    {
        $isAttributeVisible = $this->attributesVisibilityManagement
            ->checkAttributeVisibility(ProductAttributesInterface::NLNA_EXCLUSIVE);

        return $isAttributeVisible && $product->getData(ProductAttributesInterface::NLNA_EXCLUSIVE);
    }

    public function getAuPostExclusiveLabel()
    {
        return $this->attributesVisibilityManagement
            ->getAttributeLabelByCode(ProductAttributesInterface::AU_POST_EXCLUSIVE);
    }

    public function getNlnaExclusiveLabel()
    {
        return $this->attributesVisibilityManagement
            ->getAttributeLabelByCode(ProductAttributesInterface::NLNA_EXCLUSIVE);
    }

    /**
     * Get promotions by product
     *
     * @param ProductInterface $product
     * @return array
     */
    public function getPromotionsByProduct($product)
    {
        $sellerPromotions = $this->getSellerPromotions();

        return $sellerPromotions[$product->getSku()] ?? [];
    }

    /**
     * Get visible promotions by product
     *
     * @param ProductInterface $product
     * @return array
     */
    public function getVisiblePromotionsByProduct($product)
    {
        $sellerPromotions = $this->getSellerPromotions();
        $result = $this->promotionManagement->getVisiblePromotionsByProduct($product, $sellerPromotions);

        return $result;
    }

    /**
     * Check is promotion block visible
     *
     * @param ProductInterface $product
     */
    public function isPromotionsBlockVisible($product)
    {
        $result = true;
        if (!count($this->getPromotionsByProduct($product))) {
            $result = false;
        }

        return $result;
    }

    /**
     * Get seller promotions
     *
     * @return array
     */
    private function getSellerPromotions(): array
    {
        if ($this->sellerPromotions === null) {
            try {
                $this->sellerPromotions = $this->promotionManagement->getPromotions($this->getProductList());
            } catch (\Exception $e) {
                $this->_logger->warning($e->getMessage());
                $this->sellerPromotions = [];
            }
        }

        return $this->sellerPromotions;
    }

    /**
     * Set Product List
     *
     * @param array $products
     * @return $this
     */
    public function setProductList($products)
    {
        $this->productList = $products;

        return $this;
    }

    /**
     * Get Product List
     *
     * @return array
     */
    public function getProductList()
    {
        return $this->productList;
    }

    /**
     * Check Attribute Value
     *
     * @param \Magento\Catalog\Api\Data\ProductInterface $product
     * @return bool
     */
    public function productIsOpenDuringXmas(ProductInterface $product): bool
    {
        return (bool) $product->getData(SellerTagsAttributes::PRODUCT_OPEN_DURING_XMAS);
    }

    /**
     * Get Attribute Label
     *
     * @return string
     */
    public function getOpenDuringXmasLabel(): string
    {
        return $this->attributesVisibilityManagement
            ->getAttributeLabelByCode(SellerTagsAttributes::PRODUCT_OPEN_DURING_XMAS);
    }

    /**
     * Check Attribute Value
     *
     * @param \Magento\Catalog\Api\Data\ProductInterface $product
     * @return bool
     */
    public function productShopIsClosed(ProductInterface $product): bool
    {
        return (bool) $product->getData(SellerTagsAttributes::PRODUCT_CLOSED_TO);
    }

    /**
     * Get Attribute Label
     *
     * @param \Magento\Catalog\Api\Data\ProductInterface $product
     * @return string
     */
    public function getClosedShopLabel(ProductInterface $product): string
    {
        $label = $this->attributesVisibilityManagement
            ->getAttributeLabelByCode(SellerTagsAttributes::PRODUCT_CLOSED_TO);
        $date = $this->timezone->date(
            strtotime($product->getData(SellerTagsAttributes::PRODUCT_CLOSED_TO))
        )->format('d/m');

        return sprintf($label .' %s', $date);
    }
}
