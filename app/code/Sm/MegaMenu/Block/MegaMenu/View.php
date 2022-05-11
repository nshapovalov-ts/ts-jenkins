<?php
/**
 * Sm_MegaMenu
 *
 * @copyright   Copyright (c) 2022 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Alexander Korsun <aleksandr@tradesquare.com.au>
 * @author      Evgeniy Derevyanko <evgeniy@tradesquare.com.au>
 * @author      Ruslan Mukhatov <ruslan@tradesquare.com.au>
 */

declare(strict_types=1);

/*------------------------------------------------------------------------
# SM Mega Menu - Version 3.2.0
# Copyright (c) 2015 YouTech Company. All Rights Reserved.
# @license - Copyrighted Commercial Software
# Author: YouTech Company
# Websites: http://www.magentech.com
-------------------------------------------------------------------------*/

namespace Sm\MegaMenu\Block\MegaMenu;

use Exception;
use Magento\Catalog\Api\Data\CategoryInterface;
use Magento\Catalog\Helper\Category as CategoryHelper;
use Magento\Catalog\Model\Category;
use Magento\Catalog\Model\CategoryRepository;
use Magento\Catalog\Model\Indexer\Category\Flat\State;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ProductFactory;
use Magento\Cms\Helper\Page;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\FrontControllerInterface;
use Magento\Framework\Data\Tree\Node\Collection;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Image\AdapterFactory;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\Store;
use Sm\MegaMenu\Helper\Defaults;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Url\DecoderInterface;
use Magento\Framework\Filter\Email;
use Magento\Catalog\Helper\Data;
use Magento\Catalog\Block\Product\AbstractProduct;
use Magento\Framework\View\Context as ViewContext;
use Sm\MegaMenu\Block\Cache\Lite;
use Sm\MegaMenu\Model\Config\Source\Align;
use Sm\MegaMenu\Model\Config\Source\Html;
use Sm\MegaMenu\Model\Config\Source\Status;
use Sm\MegaMenu\Model\Config\Source\Type;
use Magento\Framework\Filesystem;
use Sm\MegaMenu\Model\MenuGroup;
use Sm\MegaMenu\Model\MenuGroupFactory;
use Sm\MegaMenu\Model\MenuItems;
use Magento\Cms\Model\ResourceModel\Page as PageResourceModel;
use Magento\Framework\Registry;
use Magento\Catalog\Model\ResourceModel\Product as ProductResource;
use Magento\Review\Model\Review\SummaryFactory;

/**
 * View Class
 */
class View extends Template
{
    /**
     * @var int
     */
    const EXTERNALLINK = Type::EXTERNALLINK;
    const PRODUCT = Type::PRODUCT;
    const CATEGORY = Type::CATEGORY;
    const CMSBLOCK = Type::CMSBLOCK;
    const CMSPAGE = Type::CMSPAGE;
    const CONTENT = Type::CONTENT;
    const STATUS_ENABLED = Status::STATUS_ENABLED;
    const PAGE_MODULE = Type::PAGE_MODULE;

    /**
     * @var string
     */
    const PREFIX = Html::PREFIX;
    const SELLER_VIEW_PARAM = '?seller_view=1';

    /**
     * @var int[]|null
     */
    protected $defaults = null;

    /**
     * @var DecoderInterface
     */
    protected $_urlDecoder;

    /**
     * @var AbstractProduct
     */
    protected $productBlock;

    /**
     * @var Filesystem
     */
    protected $filesystem;

    /**
     * Content data
     *
     * @var Data
     */
    protected $_contentData = null;

    /**
     * @var Email
     */
    protected $filter;

    /**
     * @var AdapterFactory
     */
    protected $_imageFactory;

    /**
     * @var null
     */
    protected $urlinterface = null;

    /**
     * Symbol convert table
     *
     * @var array
     */
    protected $_convertTable = [
        '&amp;' => 'and',
        '@' => 'at',
        '©' => 'c',
        '®' => 'r',
        'À' => 'a',
        'Á' => 'a',
        'Â' => 'a',
        'Ä' => 'a',
        'Å' => 'a',
        'Æ' => 'ae',
        'Ç' => 'c',
        'È' => 'e',
        'É' => 'e',
        'Ë' => 'e',
        'Ì' => 'i',
        'Í' => 'i',
        'Î' => 'i',
        'Ï' => 'i',
        'Ò' => 'o',
        'Ó' => 'o',
        'Ô' => 'o',
        'Õ' => 'o',
        'Ö' => 'o',
        'Ø' => 'o',
        'Ù' => 'u',
        'Ú' => 'u',
        'Û' => 'u',
        'Ü' => 'u',
        'Ý' => 'y',
        'ß' => 'ss',
        'à' => 'a',
        'á' => 'a',
        'â' => 'a',
        'ä' => 'a',
        'å' => 'a',
        'æ' => 'ae',
        'ç' => 'c',
        'è' => 'e',
        'é' => 'e',
        'ê' => 'e',
        'ë' => 'e',
        'ì' => 'i',
        'í' => 'i',
        'î' => 'i',
        'ï' => 'i',
        'ò' => 'o',
        'ó' => 'o',
        'ô' => 'o',
        'õ' => 'o',
        'ö' => 'o',
        'ø' => 'o',
        'ù' => 'u',
        'ú' => 'u',
        'û' => 'u',
        'ü' => 'u',
        'ý' => 'y',
        'þ' => 'p',
        'ÿ' => 'y',
        'Ā' => 'a',
        'ā' => 'a',
        'Ă' => 'a',
        'ă' => 'a',
        'Ą' => 'a',
        'ą' => 'a',
        'Ć' => 'c',
        'ć' => 'c',
        'Ĉ' => 'c',
        'ĉ' => 'c',
        'Ċ' => 'c',
        'ċ' => 'c',
        'Č' => 'c',
        'č' => 'c',
        'Ď' => 'd',
        'ď' => 'd',
        'Đ' => 'd',
        'đ' => 'd',
        'Ē' => 'e',
        'ē' => 'e',
        'Ĕ' => 'e',
        'ĕ' => 'e',
        'Ė' => 'e',
        'ė' => 'e',
        'Ę' => 'e',
        'ę' => 'e',
        'Ě' => 'e',
        'ě' => 'e',
        'Ĝ' => 'g',
        'ĝ' => 'g',
        'Ğ' => 'g',
        'ğ' => 'g',
        'Ġ' => 'g',
        'ġ' => 'g',
        'Ģ' => 'g',
        'ģ' => 'g',
        'Ĥ' => 'h',
        'ĥ' => 'h',
        'Ħ' => 'h',
        'ħ' => 'h',
        'Ĩ' => 'i',
        'ĩ' => 'i',
        'Ī' => 'i',
        'ī' => 'i',
        'Ĭ' => 'i',
        'ĭ' => 'i',
        'Į' => 'i',
        'į' => 'i',
        'İ' => 'i',
        'ı' => 'i',
        'Ĳ' => 'ij',
        'ĳ' => 'ij',
        'Ĵ' => 'j',
        'ĵ' => 'j',
        'Ķ' => 'k',
        'ķ' => 'k',
        'ĸ' => 'k',
        'Ĺ' => 'l',
        'ĺ' => 'l',
        'Ļ' => 'l',
        'ļ' => 'l',
        'Ľ' => 'l',
        'ľ' => 'l',
        'Ŀ' => 'l',
        'ŀ' => 'l',
        'Ł' => 'l',
        'ł' => 'l',
        'Ń' => 'n',
        'ń' => 'n',
        'Ņ' => 'n',
        'ņ' => 'n',
        'Ň' => 'n',
        'ň' => 'n',
        'ŉ' => 'n',
        'Ŋ' => 'n',
        'ŋ' => 'n',
        'Ō' => 'o',
        'ō' => 'o',
        'Ŏ' => 'o',
        'ŏ' => 'o',
        'Ő' => 'o',
        'ő' => 'o',
        'Œ' => 'oe',
        'œ' => 'oe',
        'Ŕ' => 'r',
        'ŕ' => 'r',
        'Ŗ' => 'r',
        'ŗ' => 'r',
        'Ř' => 'r',
        'ř' => 'r',
        'Ś' => 's',
        'ś' => 's',
        'Ŝ' => 's',
        'ŝ' => 's',
        'Ş' => 's',
        'ş' => 's',
        'Š' => 's',
        'š' => 's',
        'Ţ' => 't',
        'ţ' => 't',
        'Ť' => 't',
        'ť' => 't',
        'Ŧ' => 't',
        'ŧ' => 't',
        'Ũ' => 'u',
        'ũ' => 'u',
        'Ū' => 'u',
        'ū' => 'u',
        'Ŭ' => 'u',
        'ŭ' => 'u',
        'Ů' => 'u',
        'ů' => 'u',
        'Ű' => 'u',
        'ű' => 'u',
        'Ų' => 'u',
        'ų' => 'u',
        'Ŵ' => 'w',
        'ŵ' => 'w',
        'Ŷ' => 'y',
        'ŷ' => 'y',
        'Ÿ' => 'y',
        'Ź' => 'z',
        'ź' => 'z',
        'Ż' => 'z',
        'ż' => 'z',
        'Ž' => 'z',
        'ž' => 'z',
        'ſ' => 'z',
        'Ə' => 'e',
        'ƒ' => 'f',
        'Ơ' => 'o',
        'ơ' => 'o',
        'Ư' => 'u',
        'ư' => 'u',
        'Ǎ' => 'a',
        'ǎ' => 'a',
        'Ǐ' => 'i',
        'ǐ' => 'i',
        'Ǒ' => 'o',
        'ǒ' => 'o',
        'Ǔ' => 'u',
        'ǔ' => 'u',
        'Ǖ' => 'u',
        'ǖ' => 'u',
        'Ǘ' => 'u',
        'ǘ' => 'u',
        'Ǚ' => 'u',
        'ǚ' => 'u',
        'Ǜ' => 'u',
        'ǜ' => 'u',
        'Ǻ' => 'a',
        'ǻ' => 'a',
        'Ǽ' => 'ae',
        'ǽ' => 'ae',
        'Ǿ' => 'o',
        'ǿ' => 'o',
        'ə' => 'e',
        'Ё' => 'jo',
        'Є' => 'e',
        'І' => 'i',
        'Ї' => 'i',
        'А' => 'a',
        'Б' => 'b',
        'В' => 'v',
        'Г' => 'g',
        'Д' => 'd',
        'Е' => 'e',
        'Ж' => 'zh',
        'З' => 'z',
        'И' => 'i',
        'Й' => 'j',
        'К' => 'k',
        'Л' => 'l',
        'М' => 'm',
        'Н' => 'n',
        'О' => 'o',
        'П' => 'p',
        'Р' => 'r',
        'С' => 's',
        'Т' => 't',
        'У' => 'u',
        'Ф' => 'f',
        'Х' => 'h',
        'Ц' => 'c',
        'Ч' => 'ch',
        'Ш' => 'sh',
        'Щ' => 'sch',
        'Ъ' => '-',
        'Ы' => 'y',
        'Ь' => '-',
        'Э' => 'je',
        'Ю' => 'ju',
        'Я' => 'ja',
        'а' => 'a',
        'б' => 'b',
        'в' => 'v',
        'г' => 'g',
        'д' => 'd',
        'е' => 'e',
        'ж' => 'zh',
        'з' => 'z',
        'и' => 'i',
        'й' => 'j',
        'к' => 'k',
        'л' => 'l',
        'м' => 'm',
        'н' => 'n',
        'о' => 'o',
        'п' => 'p',
        'р' => 'r',
        'с' => 's',
        'т' => 't',
        'у' => 'u',
        'ф' => 'f',
        'х' => 'h',
        'ц' => 'c',
        'ч' => 'ch',
        'ш' => 'sh',
        'щ' => 'sch',
        'ъ' => '-',
        'ы' => 'y',
        'ь' => '-',
        'э' => 'je',
        'ю' => 'ju',
        'я' => 'ja',
        'ё' => 'jo',
        'є' => 'e',
        'і' => 'i',
        'ї' => 'i',
        'Ґ' => 'g',
        'ґ' => 'g',
        'א' => 'a',
        'ב' => 'b',
        'ג' => 'g',
        'ד' => 'd',
        'ה' => 'h',
        'ו' => 'v',
        'ז' => 'z',
        'ח' => 'h',
        'ט' => 't',
        'י' => 'i',
        'ך' => 'k',
        'כ' => 'k',
        'ל' => 'l',
        'ם' => 'm',
        'מ' => 'm',
        'ן' => 'n',
        'נ' => 'n',
        'ס' => 's',
        'ע' => 'e',
        'ף' => 'p',
        'פ' => 'p',
        'ץ' => 'C',
        'צ' => 'c',
        'ק' => 'q',
        'ר' => 'r',
        'ש' => 'w',
        'ת' => 't',
        '™' => 'tm',
    ];

    /**
     * @var null|array
     */
    protected $_allLeafId;
    protected $_allItemsFirstColumnId = null;

    /**
     * @var null|int
     */
    protected $_typeCurrentUrl = null;
    protected $_itemCurrentUrl = null;

    /**
     * @var MenuItems|null
     */
    protected $menuItem = null;

    /**
     * @var Product|null
     */
    protected $productModel = null;

    /**
     * @var Category|null
     */
    protected $categoryModel = null;

    /**
     * @var Store
     */
    protected $storeManager;

    /**
     * @var Category
     */
    private $categoryHelper;

    /**
     * @var State
     */
    private $categoryFlatConfig;

    /**
     * @var PageResourceModel
     */
    private $pageResourceModel;

    /**
     * @var Registry
     */
    private $registry;

    /**
     * @var MenuGroupFactory
     */
    private $menuGroupFactory;

    /**
     * @var CategoryRepository
     */
    private $categoryRepository;

    /**
     * @var Page
     */
    private $pageHelper;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var ProductResource
     */
    private $productResource;

    /**
     * @var FrontControllerInterface
     */
    private $frontController;

    /**
     * @var ProductFactory
     */
    private $productFactory;

    /**
     * @var SummaryFactory
     */
    private $summaryFactory;


    /**
     * @param Context $context
     * @param Defaults $defaults
     * @param AbstractProduct $abstractBlockProduct
     * @param DecoderInterface $urlDecoder
     * @param Email $email
     * @param Data $catalogData
     * @param AdapterFactory $imageFactory
     * @param ViewContext $viewContext
     * @param Filesystem $filesystem
     * @param CategoryHelper $categoryHelper
     * @param State $categoryFlatState
     * @param Product $product
     * @param CategoryRepository $categoryRepository
     * @param MenuItems $menuItem
     * @param PageResourceModel $pageResourceModel
     * @param Registry $registry
     * @param MenuGroupFactory $menuGroupFactory
     * @param Page $pageHelper
     * @param ProductResource $productResource
     * @param ProductFactory $productFactory
     * @param SummaryFactory $summaryFactory
     * @param array $data
     */
    public function __construct(
        Context            $context,
        Defaults           $defaults,
        AbstractProduct    $abstractBlockProduct,
        DecoderInterface   $urlDecoder,
        Email              $email,
        Data               $catalogData,
        AdapterFactory     $imageFactory,
        ViewContext        $viewContext,
        Filesystem         $filesystem,
        CategoryHelper     $categoryHelper,
        State              $categoryFlatState,
        Product            $product,
        CategoryRepository $categoryRepository,
        MenuItems          $menuItem,
        PageResourceModel  $pageResourceModel,
        Registry           $registry,
        MenuGroupFactory   $menuGroupFactory,
        Page               $pageHelper,
        ProductResource    $productResource,
        ProductFactory     $productFactory,
        SummaryFactory     $summaryFactory,
        array              $data = []
    ) {
        parent::__construct($context, $data);
        $this->_storeManager = $context->getStoreManager();
        $this->defaults = $defaults->get($data);
        $this->_urlDecoder = $urlDecoder;
        $this->productBlock = $abstractBlockProduct;
        $this->filesystem = $filesystem;
        $this->_contentData = $catalogData;
        $this->frontController = $viewContext->getFrontController();
        $this->filter = $email;
        $this->_imageFactory = $imageFactory;

        if (!$this->defaults['isenabled'] || !$this->defaults['group_id']) {
            return;
        }
        $this->productModel = $product;
        $this->categoryRepository = $categoryRepository;
        $this->categoryHelper = $categoryHelper;
        $this->categoryFlatConfig = $categoryFlatState;
        $this->menuItem = $menuItem;
        $this->pageResourceModel = $pageResourceModel;
        $this->registry = $registry;
        $this->menuGroupFactory = $menuGroupFactory;
        $this->pageHelper = $pageHelper;
        $this->scopeConfig = $context->getScopeConfig();
        $this->productResource = $productResource;
        $this->productFactory = $productFactory;
        $this->summaryFactory = $summaryFactory;
    }

    /**
     * Create Menu Items
     *
     * @return MenuItems|null
     */
    public function createMenuItems(): ?MenuItems
    {
        return $this->menuItem;
    }

    /**
     * Get All Items Ids
     *
     * @param $data
     * @return array
     */
    public function getAllItemsIds($data): array
    {
        $itemsIds = [];

        if (!is_array($data) && !is_object($data)) {
            return $itemsIds;
        }

        if (count($data) > 0) {
            foreach ($data as $item) {
                $itemsIds[] = $item['items_id'];
            }

            return $itemsIds;
        }

        return $itemsIds;
    }

    /**
     * Set Config
     *
     * @param $name
     * @param $value
     * @return bool
     */
    public function _setConfig($name, $value = null)
    {
        if (!$this->defaults) {
            return false;
        }

        if (is_array($name)) {
            $this->defaults = array_merge($this->defaults, $name);
            return true;
        }
        if (!empty($name) && isset($this->defaults[$name])) {
            $this->defaults[$name] = $value;
            return true;
        }

        return false;
    }

    /**
     * Filter Router
     *
     * @return bool
     * @throws NoSuchEntityException
     */
    public function filterRouter(): bool
    {
        $current_page = '';
        /*
        * Check to see if its a CMS page
        * if it is then get the page identifier
        */
        if ($this->getRequest()->getRouteName() == 'cms') {
            $this->_typeCurrentUrl = self::CMSPAGE;
            $stores = $this->_storeManager->getStore();
            $storesId = $stores->getId() ? $stores->getId() : Store::DEFAULT_STORE_ID;
            $this->_itemCurrentUrl = $this->pageResourceModel->checkIdentifier('home', $storesId);
            return true;
        }
        /*
        * If its not CMS page, then just get the route name
        */
        if (empty($current_page)) {
            $current_page = $this->getRequest()->getRouteName();
        }
        /*
        * What if its a catalog page?
        * Then we can get the catalog category or catalog product :)
        */
        if ($current_page == 'catalog') {
            if ($this->getRequest()->getControllerName() == 'product') {
                $this->_typeCurrentUrl = self::PRODUCT;
                $this->_itemCurrentUrl = $this->registry->registry('current_product');
                return true;
            }//do something
            if ($this->getRequest()->getControllerName() == 'category') {
                $this->_typeCurrentUrl = self::CATEGORY;
                $this->_itemCurrentUrl = $this->registry->registry('current_category');
                return true;
            } //do others
        }
        return false;
    }

    /*
     * 	filter router current page
     * 	return true mean url current have _typeCurrentUrl and _itemCurrentUrl
     *  return false mean url current not
     *  */

    /**
     * Retrieve front controller
     *
     * @return FrontControllerInterface
     */
    public function getFrontController(): FrontControllerInterface
    {
        return $this->frontController;
    }

//    public function nameTable()
//    {
//        return $this->_objectManager->create('Sm\MegaMenu\Model\ResourceModel\MenuItems')->getMainTable();
//    }

    /**
     * Get Config Object
     *
     * @return array|null
     */
    public function getConfigObject(): ?array
    {
        return $this->defaults;
    }

    /**
     * Get Items
     *
     * @return array|void
     */
    public function getItems()
    {
        $groupItem = $this->createMenuGroup()->load($this->defaults['group_id']);
        if ($groupItem->getStatus() == self::STATUS_ENABLED) {
            return $this->createMenuItems()->getItemsByLv($this->defaults['group_id'], $this->defaults['start_level']);
        } else {
            return [];
        }
    }

    /**
     * Create Menu Group
     *
     * @return MenuGroup
     */
    public function createMenuGroup(): MenuGroup
    {
        return $this->menuGroupFactory->create();
    }

    /**
     * Is Leaf
     *
     * @param array $item
     * @return bool
     */
    public function isLeaf(array $item): bool
    {
        if ($this->_allLeafId === null) {
            $itemsLeaf = $this->createMenuItems()->getAllLeafByGroupId($this->defaults['group_id']);
            $itemsids = $this->getAllItemsIds($itemsLeaf);
            $this->_allLeafId = ($itemsLeaf) ? $itemsids : '';
        }

        return in_array($item['items_id'], $this->_allLeafId);
    }

    /**
     * Has Conntent Type
     *
     * @param array $item
     * @return bool
     */
    public function hasConntentType(array $item): bool
    {
        $contentType = [
            self::CMSBLOCK,
            self::CONTENT
        ];
        return in_array($item['type'], $contentType);
    }

    /**
     * Is Align Right
     *
     * @param array $item
     * @return bool
     */
    public function isAlignRight(array $item): bool
    {
        return $item['align'] == Align::RIGHT;
    }


    /**
     * Get Item Html
     *
     * @param array $item
     * @param string $isFirstColumn
     * @param string $idActive
     * @return string
     * @throws LocalizedException
     */
    public function getItemHtml(array $item, bool $isFirstColumn = false, string $idActive = ''): string
    {
        $align_right = '';
        $prefix = self::PREFIX;
        $divClassName = $prefix . 'col_' . $item['cols_nb'];
        $firstClassName = ($this->isFirstCol($item) || $isFirstColumn) ? $prefix . 'firstcolumn ' : '';
        $aClassName = ($this->isDrop($item)) ? $prefix . 'drop' : $prefix . 'nodrop';
        $contentType = $this->getContentType($item);
        $hasLinkType = $this->hasLinkType($item);

        if (!empty($item['align']) && $item['align'] == Align::RIGHT) {
            $align_right = $prefix . "right";
        }

        $_active = '';
        $extenal_link = '';
        if ($hasLinkType) {
            $extenal_link = $this->getCurrentUrl();
            if (strcasecmp($this->getLinkOfType($item), $extenal_link) == 0) {
                $_active = $prefix . 'actived';
            }
        }

        $html = '<div data-link="' . $extenal_link . '" class="' . $divClassName . ' ' . $firstClassName . ' ' . $align_right . ' ' . $_active . ' ' . $item['custom_class'] . '">';
        $link = ($hasLinkType) ? $this->getLinkOfType($item) : '#';
        $title = (!empty($item['show_title']) && $item['show_title'] == self::STATUS_ENABLED) ? '<span class="' . $prefix . 'title_lv-' . $item['depth'] . '">' . __($item['title']) . '</span>' : '';
        $icon_title = $this->hasIcon($item) ? '<span class="icon_items_sub"><img src=' . $this->filterImage($item) . ' alt="icon items sub" /></span><span class="' . $prefix . 'icon">' . $title . '</span>' : $title;

        if ($this->isDrop($item) || $hasLinkType) {
            $headTitle = !empty($item['depth']) && $item['depth'] > 1 ? '<a  class="' . $aClassName . ' " href="' . $link . '" ' . $this->getTargetAttr($item['target']) . ' >' . $icon_title . '</a>' : '';
        } else {
            $headTitle = !empty($item['depth']) && $item['depth'] > 1 ? $icon_title : '';
        }

        if (!empty($item['depth'])) {
            $html .= $item['depth'] > 1 ? '<div class="' . $prefix . 'head_item' . '">' : '';

            if ($item['show_title'] || $this->hasIcon($item)) {
                $addClass['title'] = $prefix . 'title';
                $html .= $item['depth'] > 1 ? '<div class="' . implode(' ', $addClass) . '  ' . $_active . '">' : '';
                $html .= $item['depth'] > 1 ? $headTitle : '';

                if ($item['type'] == self::PRODUCT) {
                    $html .= $item['depth'] > 1 ? $this->getProduct($item) : '';
                }

                if ($item['type'] == self::CATEGORY) {
                    $html .= $item['depth'] > 1 ? $this->getCategory($item, $idActive) : '';
                }
                if (!empty($item['description'])) {
                    $addClass['description'] = $prefix . 'description';
                    $html .= $item['depth'] > 1 ? '<div class="' . implode(' ', $addClass) . '"><p>' . __($item['description']) . '</p></div>' : '';
                }

                $lv = $this->createMenuItems()->getAllItemsInEqLv($item, 1, 'items_id');
                if (!$this->isLv($item, $lv)) {
                    if ($item['depth'] + 1 <= $this->defaults['end_level']) {
                        $childItems = $this->createMenuItems()->getAllItemsByItemsIdEnabled($item['items_id'], $item['group_id']);
                        if (!count($childItems)) {    //fix issue: if item have child but child only and status child is disable
                            if (!$hasLinkType) {
                                $html .= $item['depth'] > 1 ? '<div class="' . $prefix . 'content">' . $contentType . '</div>' : '';
                            }
                        }
                        $cols_total = $item['cols_nb'];
                        $cols_sub = intval($cols_total);

                        foreach ($childItems as $childItem) {
                            $cols_sub = $cols_sub - intval($childItem['cols_nb']);
                            $isFirst = false;
                            if ($cols_sub < 0) {            // if cols_sub
                                $isFirst = true;
                                $cols_sub = $cols_total - intval($childItem['cols_nb']);    //reset cols_sub for new row
                            }

                            if ($childItem['type'] == '4') {
                                $hide_category_number = $this->getScopeConfigValue('hide_category/hide_category/hide_category');
                                $categoryid = substr($childItem['data_type'], strpos($childItem['data_type'], "/") + 1);
                                $subcategory = $this->categoryRepository->get((int)$categoryid);
                                $procount = $subcategory->getProductCollection()->count();

                                if (($procount >= $hide_category_number)) {
                                    $html .= $this->getCategoryItemHtml($childItem, $isFirst, $idActive);
                                    //$html .= $sub_cat_count;
                                }
                            } else {
                                $html .= $this->getItemHtml($childItem, $isFirst, $idActive);
                            }
                        }
                    } else {
                        if (!$hasLinkType) {
                            $html .= $item['depth'] > 1 ? '<div class="' . $prefix . 'content">' . $contentType . '</div>' : '';
                        }
                    }
                } else {
                    if (!$hasLinkType) {
                        $html .= $item['depth'] > 1 ? '<div class="' . $prefix . 'content">' . $contentType . '</div>' : '';
                    }
                }
                $html .= $item['depth'] > 1 ? '</div>' : '';
            }
            $html .= $item['depth'] > 1 ? '</div>' : '';
        }
        $html .= '</div>';

        return $html;
    }

    public function getScopeConfigValue(string $name): string
    {
        return $this->scopeConfig->getValue($name, ScopeInterface::SCOPE_STORES);
    }

    /**
     * Is First Col
     *
     * @param array $item
     * @return bool
     */
    public function isFirstCol(array $item): bool
    {
        if ($this->_allItemsFirstColumnId === null) {
            $itemsFirstColumn = $this->createMenuItems()->getAllItemsFirstByGroupId($this->defaults['group_id']);
            $itemsids_firstcol = $this->getAllItemsIds($itemsFirstColumn);
            $this->_allItemsFirstColumnId = ($itemsFirstColumn) ? $itemsids_firstcol : '';
        }

        return in_array($item['items_id'], $this->_allItemsFirstColumnId);
    }

    /**
     * Is Drop
     *
     * @param array $item
     * @return bool
     */
    public function isDrop(array $item): bool
    {
        return $item['status'] == Status::STATUS_DISABLED;
    }

    /**
     * Get Content Type
     *
     * @param array $item
     * @return false|string
     * @throws LocalizedException
     */
    public function getContentType(array $item)
    {
        if ($item['type'] == self::CMSBLOCK) {
            return $this->getBlockPageHtml($item);
        } elseif ($item['type'] == self::CONTENT) {
            return $this->getContentHtml($item);
        } else {
            return false;
        }
    }

    /**
     * Get Block Page Html
     *
     * @param array $item
     * @return string
     * @throws LocalizedException
     */
    public function getBlockPageHtml(array $item): string
    {
        $blockId = !empty($item['data_type']) ? $item['data_type'] : null;
        if (!$blockId) {
            return "";
        }

        return $this->getLayout()
            ->createBlock('Magento\Cms\Block\Block')
            ->setBlockId($blockId)
            ->toHtml();
    }

    /**
     * Get Content Html
     *
     * @param array $item
     * @return string
     * @throws LocalizedException
     */
    public function getContentHtml(array $item): string
    {
        return $this->filterContent($item['content']);
    }

    /**
     * @param $content
     * @return string
     * @throws LocalizedException
     */
    public function filterContent($content): string
    {
        $helper = $this->_contentData;
        $processor = $helper->getPageTemplateProcessor();
        return $processor->filter($content);
    }

    /**
     * Has Link Type
     *
     * @param array $item
     * @return bool
     */
    public function hasLinkType(array $item): bool
    {
        $linkType = [
            self::EXTERNALLINK,
            self::PRODUCT,
            self::CATEGORY,
            self::CMSPAGE,
            self::PAGE_MODULE
        ];
        return in_array($item['type'], $linkType);
    }

    /**
     * Get Current Url
     *
     * @return string
     * @throws NoSuchEntityException
     */
    public function getCurrentUrl(): string
    {
        return $this->_storeManager->getStore()->getBaseUrl();
    }

    /**
     * Get Link Of Type
     *
     * @param array $item
     * @return string|null
     * @throws NoSuchEntityException
     */
    public function getLinkOfType(array $item): ?string
    {
        if ($item['type'] == self::EXTERNALLINK) {
            return $this->filterUrl($item);
        } elseif ($item['type'] == self::PRODUCT) {
            return $this->getProductLink($item);
        } elseif ($item['type'] == self::CATEGORY) {
            return $this->getCategoryLink($item);
        } elseif ($item['type'] == self::CMSPAGE) {
            return $this->getCMSPageLink($item);
        } elseif ($item['type'] == self::PAGE_MODULE) {
            return $this->_storeManager->getStore()->getBaseUrl(UrlInterface::URL_TYPE_WEB) . $item['data_type'];
        } else {
            return '#';
        }
    }

    /**
     * @param array $item
     * @return string
     */
    public function filterUrl(array $item): string
    {
        $link = $this->formatUrl($item['data_type']);
        $link = strtolower($link);
        $haveHttp = strpos($link, "https://");
        if (!$haveHttp && ($haveHttp !== 0)) {
            return "https://" . $link;
        } else {
            return $link;
        }
    }

    /**
     * Format Url
     *
     * @param string $string
     * @return string
     */
    public function formatUrl(string $string): string
    {
        return strtr($string, $this->getConvertTable());
    }

    /**
     * Get Convert Table
     *
     * @return array
     */
    public function getConvertTable(): array
    {
        return $this->_convertTable;
    }

    /**
     * Get Product Link
     *
     * @param array $item
     * @return string
     */
    public function getProductLink(array $item)
    {
        $filter = explode('/', $item['data_type']);    // product/3
        $productId = $filter[1];            //3
        $modelProducts = $this->productModel;
        $product = $modelProducts->load($productId);
        return $product->getProductUrl();
    }

    /**
     * Get Category Link
     *
     * @param array $item
     * @return string
     * @throws NoSuchEntityException
     */
    public function getCategoryLink(array $item)
    {
        $filter = explode('/', $item['data_type']);    // category/3
        $categoryId = $filter[1];            //3
        $category = $this->categoryRepository->get((int) $categoryId);
        $categoryUrl = $category->getUrl();
        //if ($category->getLevel() == 2) {
        $categoryUrl .= self::SELLER_VIEW_PARAM;
        //}

        return $categoryUrl;
    }

    /**
     * Get CMS Page Link
     *
     * @param array $item
     * @return string|null
     */
    public function getCMSPageLink(array $item): ?string
    {
        if (empty($item['data_type'])) {
            return null;
        }
        return $this->pageHelper->getPageUrl($item['data_type']);
    }

    /**
     * Has Icon
     *
     * @param array $item
     * @return bool
     */
    public function hasIcon(array $item): bool
    {
        return ($item['icon_url']) ? true : false;
    }

    /**
     * Filter Image
     *
     * @param array $item
     * @return string|void
     * @throws NoSuchEntityException
     */
    public function filterImage(array $item)
    {
        $params = explode('/', $item['icon_url']);
        $key = array_search('___directive', $params);
        if ($key) {
            $directive = $params[$key + 1];
            $directive = $this->_urlDecoder->decode($directive);
            $url = $this->filter->filter($directive);

            if ($url) {
                return $item['icon_url'];
            }
        } else {
            return $this->_getMegaMenuDirMedia() . $item['icon_url'];
        }
    }

    /**
     * Get Mega Menu Dir Media
     *
     * @return string
     * @throws NoSuchEntityException
     */
    protected function _getMegaMenuDirMedia()
    {
        return $this->_storeManager->getStore()->getBaseUrl(UrlInterface::URL_TYPE_MEDIA);
    }

    /**
     * Get Target Attr
     *
     * @param string|int|null $type
     * @return string
     */
    public function getTargetAttr($type = ''): string
    {
        $attribs = '';
        switch ($type) {
            case '1':
            case '_blank':
                $attribs = "target=\"_blank\"";
                break;
            case '2':
            case '_popup':
                $attribs = "onclick=\"window.open(this.href,'targetWindow','toolbar=no,location=no,status=no,menubar=no,scrollbars=yes,resizable=yes,false');return false;\"";
                break;
            default:
                break;
        }
        return $attribs;
    }

    /**
     * Get Product
     *
     * @param array $item
     * @return string
     * @throws NoSuchEntityException|FileSystemException
     */
    public function getProduct(array $item): string
    {
        $output = '';
        $prefix = self::PREFIX;
        $addClass['title'] = $prefix . 'title';
        $filter = explode('/', $item['data_type']);    // product/3
        $productId = $filter[1];            //3
        $targetAttr = $this->getTargetAttr($item['target']);

        $modelProduct = $this->productFactory->create();
        $product = $this->productResource->load($modelProduct, $productId);
        $productName = $this->escapeHtml($product->getName());
        $image = $this->getProductImage($product);
        $myBlock = $this->registry->registry('current_product');
        $activedClassName = '';
        if (!empty($myBlock)) {
            if ($myBlock->getId() == $productId) {
                $activedClassName = $prefix . 'actived';
            }
        }
        $config = [
            'width' => 135
        ];
        $productIdReview = $product->getId();
        $stores = $this->_storeManager->getStore();
        $storesId = $stores->getId() ? $stores->getId() : Store::DEFAULT_STORE_ID;

        $summaryData = $this->summaryFactory->create()
            ->setStoreId($storesId)
            ->load($productIdReview);

        $output .= '<div class="' . implode(' ', $addClass) . ' ' . $activedClassName . ' product-items">';
        if ($item['show_image_product'] == self::STATUS_ENABLED) {
            $output .= '<a href="' . $product->getProductUrl() . '" ' . $targetAttr . ' title="' . $productName . '" class="product-image"><img src="' . $this->_resizeImage($image, $config) . '" alt="' . $productName . '" /></a>';
        }
        if ($item['show_title_product'] == self::STATUS_ENABLED) {
            $output .= '<h3 class="product-name"><a href="' . $product->getProductUrl() . '" ' . $targetAttr . ' title="' . $productName . '">' . __($productName) . '</a></h3>';
        }
        if ($item['show_rating_product'] == self::STATUS_ENABLED) {
            if ($summaryData['rating_summary']) {
                $output .= '<div class="product-reviews-summary short">';
                $output .= '<div class="rating-summary">';
                $output .= '<div class="rating-result" title="' . $summaryData['rating_summary'] . '%">';
                $output .= '<span style="width:' . $summaryData['rating_summary'] . '%;"><span>' . $summaryData['rating_summary'] . '</span></span>';
                $output .= '</div>';
                $output .= '</div>';
                $output .= '</div>';
            }
        }
        if ($item['show_price_product'] == self::STATUS_ENABLED) {
            $output .= '<div class="price-box">';
            $output .= '<span class="price-excluding-tax">';
            $output .= '<span class="price">' . $this->productBlock->getProductPrice($product) . '</span>';
            $output .= '</span>';
            $output .= '</div>';
        }
        $output .= '</div>';
        return $output;
    }

    /**
     * Get Product Image
     *
     * @param ProductResource $product
     * @return string|null
     * @throws FileSystemException
     */
    public function getProductImage(ProductResource $product): ?string
    {
        $baseDirMedia = $this->_getBaseDirMedia();
        $imgPro = ($product->getImage() != null) ?
            $product->getImage() :
            ($product->thumbnail != null ? $product->thumbnail : '');
        $_media_dir = $baseDirMedia . 'catalog/product';
        $imagesUrl = $_media_dir . $imgPro;
        $images_path = [];
        if (file_exists($imagesUrl) || @getimagesize($imagesUrl) !== false) {
            $images_path[] = $imagesUrl;
        }
        return is_array([$images_path]) && count($images_path) ? $images_path[0] : null;
    }

    /**
     * Get Base Dir Media
     *
     * @return string
     * @throws FileSystemException
     */
    protected function _getBaseDirMedia(): string
    {
        return $this->filesystem->getDirectoryWrite(DirectoryList::MEDIA)
            ->getAbsolutePath();
    }

    /**
     * Resize Image
     *
     * @param string|null $image
     * @param array $config
     * @param string $type
     * @param string $folder
     * @return string
     * @throws FileSystemException
     * @throws NoSuchEntityException
     * @throws Exception
     */
    public function _resizeImage(
        ?string $image,
        array   $config,
        string  $type = "product",
        string  $folder = 'resized'
    ): string {
        $baseDirPub = $this->_getBaseDirPub();
        $baseDirMedia = $this->_getBaseDirMedia();
        if ($config['width'] <= 0) {
            return $image;
        }
        $_file_name = substr(strrchr($image, "/"), 1);
        $_media_dir = $baseDirMedia . 'catalog' . '/' . $type . '/';
        $absPath = $image;
        $cache_dir = $_media_dir . $folder . '/' . $config['width'] . '/' . md5(serialize($config));
        $dirImg = $baseDirPub . str_replace("/", "/", strstr($image, 'media'));
        $from_skin_nophoto = $baseDirPub . str_replace("/", "/", strstr($image, 'static'));
        $dirImg = strpos($dirImg, 'media') !== false ? $dirImg : '';
        $dirImg = (strpos($from_skin_nophoto, 'skin') !== false && $dirImg == '') ? $from_skin_nophoto : $dirImg;
        if (file_exists($cache_dir . '/' . $_file_name) && @getimagesize($cache_dir . '/' . $_file_name) !== false) {
            $new_image = $this->_storeManager
                    ->getStore()
                    ->getBaseUrl(UrlInterface::URL_TYPE_MEDIA) . 'catalog/' . $type . '/' . $folder . '/' . $config['width'] . '/' . md5(serialize($config)) . '/' . $_file_name;
        } elseif ((file_exists($dirImg) && $dirImg != '')) {
            if (!is_dir($cache_dir)) {
                @mkdir($cache_dir, 0777, true);
            }
            $image = $this->_imageFactory->create();
            $image->open($absPath);
            $image->resize($config['width'], null);
            $image->save($cache_dir . '/' . $_file_name);
            $new_image = $this->_storeManager
                    ->getStore()
                    ->getBaseUrl(UrlInterface::URL_TYPE_MEDIA) . 'catalog/' . $type . '/' . $folder . '/' . $config['width'] . '/' . md5(serialize($config)) . '/' . $_file_name;
        } else {
            return $image;
        }
        return $new_image;
    }

    /**
     * Get Base Dir Pub
     *
     * @return string
     * @throws FileSystemException
     */
    protected function _getBaseDirPub(): string
    {
        return $this->filesystem
            ->getDirectoryWrite(DirectoryList::PUB)
            ->getAbsolutePath();
    }

    /**
     * Get Category
     *
     * @param array $item
     * @param int|string $itemId
     * @return string|void
     * @throws NoSuchEntityException
     */
    public function getCategory(array $item, $itemId)
    {
        $output = '';
        $dem = 0;
        $id_all_cat = '';
        $limitCat = (int)$item['limit_category'];
        $limitSubCat = (int)$item['limit_sub_category'];
        $prefix = self::PREFIX;
        $activedClassName = $this->isActived($itemId, $item) ? $prefix . 'actived' : '';
        $addClass['title'] = $prefix . 'title';
        $aClassName = ($this->isDrop($item)) ? $prefix . 'drop' : $prefix . 'nodrop';
        $filter = explode('/', $item['data_type']);
        $categoryId = $filter[1];
        $modelCategory = $this->categoryModel;
        $category = $modelCategory->load($categoryId);
        $name_cat_parent = $category->getName();
        if ($category->getChildren()) {
            $id_all_cat = $category->getChildrenCategories();
            if (is_object($id_all_cat)) {
                $id_all_cat = $id_all_cat->toArray();
            }
            $id_all_cat = array_keys($id_all_cat);
        }
        if ($item['show_title_category'] == self::STATUS_ENABLED) {
            $output .= '<div class="' . implode(' ', $addClass) . '">';
            $output .= '<h3 class="' . $aClassName . ' ' . $activedClassName . ' title-cat">' . __($name_cat_parent) . '</h3>';
            $output .= '</div>';
        }
        if ($id_all_cat) {
            if (count($id_all_cat) > $limitCat) {
                $limit = $limitCat;
            } else {
                $limit = count($id_all_cat);
            }

            foreach ($id_all_cat as $ia) {
                $activedClassName = $this->isActivedChildCat($itemId, $ia) ? $prefix . 'actived' : '';
                $dem++;
                if (($limit == '') || ($dem <= $limit)) {
                    $namecat = null;
                    $categoryChild = $this->categoryRepository->get((int)$ia);
                    $link = $categoryChild->getUrl();

                    if ($item['type'] == '4') {
                        if ($item['type']) {
                            $hide_category_number = $this->getScopeConfigValue('hide_category/hide_category/hide_category');
                            //$categoryid = substr($item['data_type'], strpos($item['data_type'], "/") + 1);
                            //echo $ia;
                            $allcategoryproduct = $this->categoryRepository->get((int)$ia)
                                ->getProductCollection()
                                ->addAttributeToSelect('*');
                            $cat_count = $allcategoryproduct->count();

                            if ($cat_count >= $hide_category_number) {
                                //$title = '<span class="'.$prefix.'title_l1v-'.$item['depth'].$item['type'].'">'.$categoryChild->getName().'</span>';
                                if ($categoryChild->getName() == "Other (Temp)") {
                                    $title = '<span class="' . $prefix . 'title_lv1-' . $item['depth'] . '">' . 'Other' . '</span>';
                                } else {
                                    $title = '<span class="' . $prefix . 'title_lv-' . $item['depth'] . '">' . $categoryChild->getName() . '</span>';
                                }
                                $namecat = '<a class="' . $aClassName . '" href="' . $link . '" ' . $this->getTargetAttr($item['target']) . '>' . __($title) . '</a>';
                            }
                        }
                    } else {
                        //$title = '<span class="'.$prefix.'title_l2v-'.$item['depth'].$item['type'].'">'.$categoryChild->getName().'</span>';
                        if ($categoryChild->getName() == "Other (Temp)") {
                            $title = '<span class="' . $prefix . 'title_lv1-' . $item['depth'] . '">' . 'Other' . '</span>';
                        } else {
                            $title = '<span class="' . $prefix . 'title_lv-' . $item['depth'] . '">' . $categoryChild->getName() . '</span>';
                        }
                        $namecat = '<a class="' . $aClassName . '" href="' . $link . '" ' . $this->getTargetAttr($item['target']) . '>' . __($title) . '</a>';
                    }

                    $output .= '<div class="' . implode(' ', $addClass) . ' ' . $activedClassName . '">';
                    $output .= $namecat;
                    if ($item['show_sub_category'] == self::STATUS_ENABLED) {
                        if ($categoryChild->getChildren()) {
                            $id_all_cat_child = $categoryChild->getChildrenCategories();
                            if (is_object($id_all_cat_child)) {
                                $id_all_cat_child = $id_all_cat_child->toArray();
                            }
                            $id_all_cat_child = array_keys($id_all_cat_child);
                            if (count($id_all_cat_child) > $limitSubCat) {
                                $limitSub = $limitSubCat;
                            } else {
                                $limitSub = count($id_all_cat_child);
                            }

                            $output .= $this->getCategoryChild($item, $id_all_cat_child, (int)$limitSub, $itemId);
                        }
                    }
                    $output .= '</div>';
                }
            }
        } else {
            return;
        }
        return $output;
    }

    /**
     * Is Actived
     *
     * @param int|string $itemId
     * @param array $item
     * @return bool
     */
    public function isActived($itemId, array $item): bool
    {
        $id = "";
        if ($item['type'] != 1) {
            if ($item['type'] == self::EXTERNALLINK) {
                return false;
            } elseif ($item['type'] == self::PRODUCT) {
                $filter = explode('/', $item['data_type']);
                $id = $filter[1];
            } elseif ($item['type'] == self::CATEGORY) {
                $filter = explode('/', $item['data_type']);
                $id = $filter[1];
            } elseif ($item['type'] == self::CMSBLOCK) {
                return false;
            } elseif ($item['type'] == self::CMSPAGE || $item['type'] == self::PAGE_MODULE) {
                $id = $item['data_type'];
            } elseif ($item['type'] == self::CONTENT) {
                return false;
            }

            if ($itemId != '') {
                return $itemId == $id;
            } else {
                return false;
            }
        }
        return false;
    }

    /**
     * Is Actived Child Cat
     *
     * @param $itemId
     * @param int[]|string[] $item
     * @return bool
     */
    public function isActivedChildCat($itemId, array $item): bool
    {
        if ($itemId != '') {
            return $itemId == $item;
        } else {
            return false;
        }
    }

    /**
     * Get Category Child
     *
     * @param array $item
     * @param int[]|string[] $id_all_cat_child
     * @param int $limit
     * @param string $itemId
     * @return false|string
     * @throws NoSuchEntityException
     */
    public function getCategoryChild(array $item, array $id_all_cat_child, int $limit, string $itemId = '')
    {
        $dem = 0;
        $output = '';
        $prefix = self::PREFIX;
        $aClassName = ($this->isDrop($item)) ? $prefix . 'drop' : $prefix . 'nodrop';
        $addClass['title'] = $prefix . 'title';
        if ($id_all_cat_child) {
            foreach ($id_all_cat_child as $iac) {
                $activedClassName = ($this->isActivedChildCat($itemId, $iac)) ? $prefix . 'actived' : '';
                $dem++;
                if (($limit == '') || ($dem <= $limit)) {
                    $category_child = $this->categoryRepository->get((int)$iac);
                    $link = $category_child->getUrl();
                    $title = '<span class="' . $prefix . 'title_lv-' . $item['depth'] . '">' . __($category_child->getName()) . '</span>';
                    $namecat = '<a class="' . $aClassName . '" href="' . $link . '" ' . $this->getTargetAttr($item['target']) . ' >' . __($title) . '</a>';

                    $output .= '<div class="' . implode(' ', $addClass) . ' ' . $activedClassName . '">';
                    $output .= $namecat;
                    if ($category_child->getChildren()) {
                        $id_all_cat_child = $category_child->getChildrenCategories();
                        if (is_object($id_all_cat_child)) {
                            $id_all_cat_child = $id_all_cat_child->toArray();
                        }
                        $id_all_cat_child = array_keys($id_all_cat_child);
                        $output .= $this->getCategoryChild($item, $id_all_cat_child, $limit, $itemId);
                    }
                    $output .= '</div>';
                }
            }
        } else {
            return false;
        }
        return $output;
    }

    /**
     * Is Lv
     *
     * @param array $item
     * @param $lv
     * @return bool
     */
    public function isLv(array $item, $lv): bool
    {
        return in_array($item['items_id'], $lv);
    }

    /**
     * Get Category Item Html
     *
     * @param $category
     * @param string $isFirstColumn
     * @param string $idActive
     * @param bool $isLastColumn
     * @param bool $isDivide
     * @return string
     * @throws NoSuchEntityException
     */
    public function getCategoryItemHtml(
        $category,
        bool $isFirstColumn = false,
        string $idActive = '',
        bool $isLastColumn = false,
        bool $isDivide = false
    ): string {
        $item = $this->getCategoryData($category);
        $align_right = '';
        $prefix = self::PREFIX;
        $divClassName = $prefix . 'col_' . $item['cols_nb'];
        $firstClassName = ($this->isFirstCol($item) || $isFirstColumn) ? $prefix . 'firstcolumn ' : '';
        $aClassName = ($this->isDrop($item)) ? $prefix . 'drop' : $prefix . 'nodrop';
        $contentType = $this->getContentType($item);
        $hasLinkType = $this->hasLinkType($item);

        if ($item['align'] && $item['align'] == Align::RIGHT) {
            $align_right = $prefix . "right";
        }

        $_active = '';
        $extenal_link = '';
        if ($hasLinkType) {
            $extenal_link = $this->getCurrentUrl();
            if (strcasecmp($this->getLinkOfType($item), $extenal_link) == 0) {
                $_active = $prefix . 'actived';
            }
        }

        $html = '';
        if ($isFirstColumn) {
            $html .= '<div style="column-count:1 !important" data-link="' . $extenal_link . '" class="' . $divClassName . ' ' . $firstClassName . ' ' . $align_right . ' ' . $_active . ' ' . $item['custom_class'] . '">';
        } else {
            if ($isDivide) {
                $html .= '</div><div style="column-count:1 !important" data-link="' . $extenal_link . '" class="' . $divClassName . ' ' . $firstClassName . ' ' . $align_right . ' ' . $_active . ' ' . $item['custom_class'] . '">';
            }
        }
        $link = ($hasLinkType) ? $this->getLinkOfType($item) : '#';
        $title = ($item['show_title'] == self::STATUS_ENABLED) ? '<span class="' . $prefix . 'title_lv-' . $item['depth'] . '">' . __($item['title']) . '</span>' : '';
        $icon_title = ($this->hasIcon($item)) ? '<span class="icon_items_sub"><img src=' . $this->filterImage($item) . ' alt="icon items sub" /></span><span class="' . $prefix . 'icon">' . $title . '</span>' : $title;

        if ($this->isDrop($item) || $hasLinkType) {
            $headTitle = $item['depth'] > 1 ? '<a  class="' . $aClassName . ' " href="' . $link . '" ' . $this->getTargetAttr($item['target']) . ' >' . $icon_title . '</a>' : '';
        } else {
            $headTitle = $item['depth'] > 1 ? $icon_title : '';
        }
        $html .= $item['depth'] == 2 ? '<div class="sm_megamenu_col_2">' : '';

        if ($item['depth']) {
            $html .= $item['depth'] > 1 ? '<div class="' . $prefix . 'head_item' . '">' : '';

            if ($item['show_title'] || $this->hasIcon($item)) {
                $addClass['title'] = $prefix . 'title';
                $html .= $item['depth'] > 1 ? '<div class="' . implode(' ', $addClass) . '  ' . $_active . '">' : '';
                $html .= $item['depth'] > 1 ? $headTitle : '';

                if ($item['description']) {
                    $addClass['description'] = $prefix . 'description';
                    $html .= $item['depth'] > 1 ? '<div class="' . implode(' ', $addClass) . '"><p>' . __($item['description']) . '</p></div>' : '';
                }

                $lv = $this->createMenuItems()->getAllItemsInEqLv($item, 1, 'items_id');
                if (!$this->isLv($item, $lv)) {
                    if ($item['depth'] + 1 <= $this->defaults['end_level']) {
                        $childCategoryItems = $this->getChildCategories($category);
                        if (!count($childCategoryItems)) {    //fix issue: if item have child but child only and status child is disable
                            if (!$hasLinkType) {
                                $html .= $item['depth'] > 1 ? '<div class="' . $prefix . 'content">' . $contentType . '</div>' : '';
                            }
                        }
                        $cols_total = $item['cols_nb'];
                        $cols_sub = intval($cols_total);
                        $counter = 0;
                        foreach ($childCategoryItems as $childCategory) {
                            $isFirstColumn = false;//($counter == 0) ? true : false;
                            $isLastColumn = ($counter == count($childCategoryItems)) ? true : false;
                            $counter++;
                            $childItem = $this->getCategoryData($childCategory);
                            $cols_sub = $cols_sub - intval($childItem['cols_nb']);
                            if ($cols_sub < 0) {            // if cols_sub
                                $cols_sub = $cols_total - intval($childItem['cols_nb']);    //reset cols_sub for new row
                            }

                            if ($childItem['type'] == '4') {
                                if ($childItem['type']) {
                                    $html .= $this->getCategoryItemHtml($childCategory, $isFirstColumn, $idActive, false, $isDivide);
                                }
                            } else {
                                $html .= $this->getCategoryItemHtml($childCategory, $isFirstColumn, $idActive, false, $isDivide);
                            }
                        }
                    } else {
                        if (!$hasLinkType) {
                            $html .= $item['depth'] > 1 ? '<div class="' . $prefix . 'content">' . $contentType . '</div>' : '';
                        }
                    }
                } else {
                    if (!$hasLinkType) {
                        $html .= $item['depth'] > 1 ? '<div class="' . $prefix . 'content">' . $contentType . '</div>' : '';
                    }
                }

                $html .= $item['depth'] == 2 ? '</div>' : '';

                $html .= $item['depth'] > 1 ? '</div>' : '';
            }
            $html .= $item['depth'] > 1 ? '</div>' : '';
        }
        if ($isLastColumn) {
            $html .= '</div>';
        }

        return $html;
    }

    /**
     * Get Category Data
     *
     * @param $category
     * @return array
     */
    public function getCategoryData($category): array
    {
        return [
            "items_id" => $category->getId(),
            "title" => $category->getName(),
            "show_title" => 1,
            "description" => '',
            "status" => 1,
            "align" => 1,
            "depth" => $category->getLevel() - 1,
            "group_id" => '99',
            "cols_nb" => 6,
            "icon_url" => '',
            "target" => 3,
            "type" => 4,
            "data_type" => 'category/' . $category->getId(),
            "content" => '',
            "custom_class" => '',
            "parent_id" => 1000,
            "order_item" => $category->getId(),
            "position_item" => 2,
            "priorities" => 1,
            "show_image_product" => 0,
            "show_title_product" => 0,
            "show_rating_product" => 0,
            "show_price_product" => 0,
            "show_title_category" => 2,
            "limit_category" => "",
            "show_sub_category" => 2,
            "limit_sub_category" => "",
            "category" => $category
        ];
    }

    /**
     * Retrieve child store categories
     *
     * @param $category
     * @return \Magento\Framework\Data\Tree\Node\Collection|array
     */
    public function getChildCategories($category)
    {
        if ($this->categoryFlatConfig->isFlatEnabled() && $category->getUseFlatResource()) {
            $subcategories = (array)$category->getChildrenNodes();
        } else {
            $subcategories = $category->getChildren();
        }
        return $subcategories;
    }

    /**
     * Get Mega Menu Categories
     *
     * @return array
     */
    public function getMegaMenuCategories(): array
    {
        $categoriesData = [];
        $categories = $this->getStoreCategories(true, false, true);
        foreach ($categories as $category) {
            if (!$category->getIsActive()) {
                continue;
            }
            $categoriesData[] = $this->getCategoryData($category);
        }
        return $categoriesData;
    }

    /**
     * Get Store Categories
     *
     * @param bool $sorted
     * @param bool $asCollection
     * @param bool $toLoad
     * @return array|Collection
     */
    public function getStoreCategories(bool $sorted = false, bool $asCollection = false, bool $toLoad = true)
    {
        return $this->categoryHelper->getStoreCategories($sorted, $asCollection, $toLoad);
    }

    /**
     * To Html
     *
     * @return bool|string
     * @throws FileSystemException
     */
    protected function _toHtml()
    {
        $delete = $this->getIsCacheDelete();

        if (!$this->defaults['isenabled'] || !$this->defaults['group_id']) {
            return "";
        }

        $use_cache = (int)$this->getConfig('use_cache');
        $cache_time = (int)$this->getConfig('cache_time');
        $folder_cache = $this->getCacheDir();
        $folder_cache = $folder_cache . 'Sm/MegaMenu/';

        $options = array(
            'cacheDir' => $folder_cache,
            'lifeTime' => $cache_time,
            'readControl' => false
        );

        $Cache_Lite = new Lite($options);

        if (!file_exists($folder_cache)) {
            mkdir($folder_cache, 0777, true);
        }

        if ($use_cache) {
            $hash = md5(serialize($this->getConfig()));
            $oldFilename = 'cache_' . md5('default') . '_' . md5($hash);

            if ($data = $Cache_Lite->get($hash) && !$delete && file_exists($folder_cache . $oldFilename)) {
                return $data != 1 ? $data : @file_get_contents($folder_cache . $oldFilename);
            } else {
                $template_file = $this->getTemplate();
                $template_file = (!empty($template_file)) ? $template_file : "Sm_MegaMenu::megamenu.phtml";
                $this->setTemplate($template_file);
                $data = parent::_toHtml();
                $hash2 = md5(serialize($this->getConfig()));
                $newFilename = 'cache_' . md5('default') . '_' . md5($hash2);

                if (file_exists($folder_cache . $oldFilename) && $delete) {
                    $Cache_Lite->get($hash2);
                }
                $Cache_Lite->save($data);
                if ($delete && file_exists($folder_cache . $oldFilename) && file_exists($folder_cache . $newFilename)) {
                    @rename($folder_cache . $newFilename, $folder_cache . $oldFilename);
                }
                return $data;
            }
        } else {
            if (file_exists($folder_cache)) {
                $Cache_Lite->_cleanDir($folder_cache);
            }

            $template_file = $this->getTemplate();
            $template_file = (!empty($template_file)) ? $template_file : "Sm_MegaMenu::megamenu.phtml";
            $this->setTemplate($template_file);
        }
        return parent::_toHtml();
    }

    /**
     * Get Config
     *
     * @param $name
     * @param $value
     * @return int|int[]|mixed|null
     */
    public function getConfig($name = null, $value = null)
    {
        if ($name) {
            return $this->defaults[$name] ?? $value;
        }
        return $this->defaults;
    }

    /**
     * Get Cache Dir
     *
     * @return string
     * @throws FileSystemException
     */
    public function getCacheDir(): string
    {
        $cache = $this->filesystem->getDirectoryWrite(DirectoryList::CACHE);
        return $cache->getAbsolutePath();
    }

    /**
     * Get Category
     *
     * @param string|int $categoryid
     * @return CategoryInterface|mixed|null
     * @throws NoSuchEntityException
     */
    public function getCategoryModel($categoryid)
    {
        return $this->categoryRepository->get((int)$categoryid);
    }
}
