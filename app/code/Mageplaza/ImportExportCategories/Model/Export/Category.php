<?php
/**
 * Mageplaza
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Mageplaza.com license that is
 * available through the world-wide-web at this URL:
 * https://www.mageplaza.com/LICENSE.txt
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this extension to newer
 * version in the future.
 *
 * @category    Mageplaza
 * @package     Mageplaza_ImportExportCategories
 * @copyright   Copyright (c) Mageplaza (http://www.mageplaza.com/)
 * @license     https://www.mageplaza.com/LICENSE.txt
 */

namespace Mageplaza\ImportExportCategories\Model\Export;

use DateTime;
use Exception;
use IntlDateFormatter;
use Magento\Catalog\Model\ResourceModel\Category\Attribute\Collection as AttributeCollection;
use Magento\Catalog\Model\ResourceModel\Category\Attribute\CollectionFactory as AttributeColFactory;
use Magento\Catalog\Model\ResourceModel\Category\Collection;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory as CategoryColFactory;
use Magento\Eav\Model\Config;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\ImportExport\Model\Export\Entity\AbstractEntity;
use Magento\ImportExport\Model\Import;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Class Category
 * @package Mageplaza\ImportExportCategories\Model\Export
 */
class Category extends AbstractEntity
{
    const COL_CATEGORY_ID                = 'category_id';
    const COL_STORE_ID                   = 'store_id';
    const COL_PARENT                     = 'parent';
    const COL_IS_ACTIVE                  = 'is_active';
    const COL_INCLUDE_IN_MENU            = 'include_in_menu';
    const COL_NAME                       = 'name';
    const COL_IMAGE                      = 'image';
    const COL_DESCRIPTION                = 'description';
    const COL_LANDING_PAGE               = 'landing_page';
    const COL_DISPLAY_MODE               = 'display_mode';
    const COL_IS_ANCHOR                  = 'is_anchor';
    const COL_AVAILABLE_SORT_BY          = 'available_sort_by';
    const COL_DEFAULT_SORT_BY            = 'default_sort_by';
    const COL_FILTER_PRICE_RANGE         = 'filter_price_range';
    const COL_URL_KEY                    = 'url_key';
    const COL_META_TITLE                 = 'meta_title';
    const COL_META_KEYWORD               = 'meta_keywords';
    const COL_META_DESCRIPTION           = 'meta_description';
    const COL_PRODUCT_SKU                = 'product_sku';
    const COL_CUSTOM_DESIGN              = 'custom_design';
    const COL_POSITION                   = 'position';
    const COL_PAGE_LAYOUT                = 'page_layout';
    const COL_CUSTOM_DESIGN_FORM         = 'custom_design_from';
    const COL_CUSTOM_DESIGN_TO           = 'custom_design_to';
    const COL_CUSTOM_USE_PARENT_SETTINGS = 'custom_use_parent_settings';
    const COL_CUSTOM_APPLY_TO_PRODUCTS   = 'custom_apply_to_products';
    const COL_CUSTOM_LAYOUT_UPDATE       = 'custom_layout_update';
    const COL_ATTRIBUTE_SET_ID           = 'attribute_set_id';
    const COL_PARENT_ID                  = 'parent_id';
    const COL_PATH                       = 'path';
    const COL_PRODUCT_COUNT              = 'product_count';
    const COL_PRODUCTS                   = 'product_sku';

    /**
     * Permanent entity columns
     *
     * @var array
     */
    protected $_permanentAttributes = [self::COL_CATEGORY_ID];

    /**
     * Attributes codes which are appropriate for export and not the part of additional_attributes.
     *
     * @var array
     */
    protected $_headerColumns = [
        self::COL_CATEGORY_ID,
        self::COL_STORE_ID,
        self::COL_PARENT,
        self::COL_IS_ACTIVE,
        self::COL_INCLUDE_IN_MENU,
        self::COL_NAME,
        self::COL_IMAGE,
        self::COL_DESCRIPTION,
        self::COL_LANDING_PAGE,
        self::COL_DISPLAY_MODE,
        self::COL_IS_ANCHOR,
        self::COL_AVAILABLE_SORT_BY,
        self::COL_DEFAULT_SORT_BY,
        self::COL_FILTER_PRICE_RANGE,
        self::COL_URL_KEY,
        self::COL_META_TITLE,
        self::COL_META_KEYWORD,
        self::COL_META_DESCRIPTION,
        self::COL_PRODUCT_SKU,
        self::COL_CUSTOM_DESIGN,
        self::COL_POSITION,
        self::COL_PAGE_LAYOUT,
        self::COL_CUSTOM_DESIGN_FORM,
        self::COL_CUSTOM_DESIGN_TO,
        self::COL_CUSTOM_USE_PARENT_SETTINGS,
        self::COL_CUSTOM_APPLY_TO_PRODUCTS,
        self::COL_CUSTOM_LAYOUT_UPDATE,
        //        self::COL_ATTRIBUTE_SET_ID,
        //        self::COL_PARENT_ID,
        //        self::COL_PATH,
        //        self::COL_PRODUCT_COUNT,
        self::COL_PRODUCTS,
    ];

    /**
     * Codes of attributes which are displayed as dates
     *
     * @var array
     */
    protected $dateAttrCodes = [
        'custom_design_from',
        'custom_design_to'
    ];

    /**
     * Category collection
     *
     * @var Collection
     */
    protected $_entityCollection;

    /**
     * @var CategoryColFactory
     */
    protected $_entityColFactory;

    /**
     * @var AttributeColFactory
     */
    protected $_attributeColFactory;

    /**
     * @var LoggerInterface
     */
    protected $_logger;

    /**
     * Items per page for collection limitation
     *
     * @var null
     */
    protected $_itemsPerPage;

    /**
     * Attribute types
     *
     * @var array
     */
    protected $_attributeTypes = [];

    /**
     * Attributes defined by user
     *
     * @var array
     */
    private $userDefinedAttributes = [];

    /**
     * Array of pairs store ID to its code.
     *
     * @var array
     */
    protected $_storeIdToCode = [];

    /**
     * Category constructor.
     *
     * @param TimezoneInterface $localeDate
     * @param Config $config
     * @param ResourceConnection $resource
     * @param StoreManagerInterface $storeManager
     * @param CategoryColFactory $entityColFactory
     * @param AttributeColFactory $attributeColFactory
     * @param LoggerInterface $logger
     */
    public function __construct(
        TimezoneInterface $localeDate,
        Config $config,
        ResourceConnection $resource,
        StoreManagerInterface $storeManager,
        CategoryColFactory $entityColFactory,
        AttributeColFactory $attributeColFactory,
        LoggerInterface $logger
    ) {
        $this->_entityColFactory    = $entityColFactory;
        $this->_attributeColFactory = $attributeColFactory;
        $this->_logger              = $logger;

        parent::__construct($localeDate, $config, $resource, $storeManager);

        $this->initAttributes()
            ->_initStores();
    }

    /**
     * Export process
     *
     * @return string
     * @throws LocalizedException
     */
    public function export()
    {
        //Execution time may be very long
        set_time_limit(0);

        $writer = $this->getWriter();
        $page   = 0;
        while (true) {
            ++$page;
            $entityCollection = $this->_getEntityCollection(true);
            $entityCollection->setStoreId(Store::DEFAULT_STORE_ID);
            $this->_prepareEntityCollection($entityCollection);
            $this->paginateCollection($page, $this->getItemsPerPage());
            if ($entityCollection->count() === 0) {
                break;
            }
            $exportData = $this->getExportData();
            if ($page === 1) {
                $writer->setHeaderCols($this->_getHeaderColumns());
            }
            foreach ($exportData as $dataRow) {
                $writer->writeRow($dataRow);
            }
            if ($entityCollection->getCurPage() >= $entityCollection->getLastPageNumber()) {
                break;
            }
        }

        return $writer->getContents();
    }

    /**
     * Get export data for collection
     *
     * @return array
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     * @SuppressWarnings(PHPMD.UnusedLocalVariable)
     */
    public function getExportData()
    {
        $exportData = [];
        try {
            $rawData = $this->collectRawData();
            foreach ($rawData as $categoryId => $categoryData) {
                foreach ($categoryData as $storeId => $dataRow) {
                    $dataRow[self::COL_CATEGORY_ID] = $categoryId;

                    if ($dataRow) {
                        $exportData[] = $dataRow;
                    }
                }
            }
        } catch (Exception $e) {
            $this->_logger->critical($e);
        }

        return $exportData;
    }

    /**
     * Collect export data for all products
     *
     * @return array
     * @throws Exception
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function collectRawData()
    {
        $data       = [];
        $collection = $this->_getEntityCollection();
        foreach ($this->_storeIdToCode as $storeId => $storeCode) {
            $collection->setStoreId($storeId);
            /**
             * @var int $itemId
             * @var \Magento\Catalog\Model\Category $item
             */
            foreach ($collection as $itemId => $item) {
                if ($itemId === \Magento\Catalog\Model\Category::TREE_ROOT_ID) {
                    continue;
                }
                foreach ($this->_getExportAttrCodes() as $code) {
                    $attrValue = $item->getData($code);
                    if (!$this->isValidAttributeValue($code, $attrValue)) {
                        continue;
                    }

                    if (isset($this->_attributeValues[$code][$attrValue]) && !empty($this->_attributeValues[$code])) {
                        $attrValue = $this->_attributeValues[$code][$attrValue];
                    }

                    if ($this->_attributeTypes[$code] === 'datetime') {
                        if (in_array($code, $this->dateAttrCodes, true)
                            || in_array($code, $this->userDefinedAttributes, true)
                        ) {
                            $attrValue = $this->_localeDate->formatDateTime(
                                new DateTime($attrValue),
                                IntlDateFormatter::SHORT,
                                IntlDateFormatter::NONE,
                                null,
                                date_default_timezone_get()
                            );
                        } else {
                            $attrValue = $this->_localeDate->formatDateTime(new DateTime($attrValue));
                        }
                    }
                    switch ($attrValue) {
                        case 'Yes':
                            $attrValue                                     = 1;
                            $data[$itemId][Store::DEFAULT_STORE_ID][$code] = $attrValue;
                            break;
                        case 'No':
                            $attrValue                                     = 0;
                            $data[$itemId][Store::DEFAULT_STORE_ID][$code] = $attrValue;
                            break;
                    }
                    if ($code === self::COL_URL_KEY) {
                        if (empty($attrValue)) {
                            $data[$itemId][Store::DEFAULT_STORE_ID][$code]
                                = $this->convertUrl($data[$itemId][Store::DEFAULT_STORE_ID][self::COL_NAME]);
                        }
                    }
                    if (!$this->isValidAttributeValue($code, $attrValue)) {
                        continue;
                    }
                    if ((int) $storeId !== Store::DEFAULT_STORE_ID
                        && isset($data[$itemId][Store::DEFAULT_STORE_ID][$code])
                        && $data[$itemId][Store::DEFAULT_STORE_ID][$code] == $attrValue
                    ) {
                        continue;
                    }
                    $productSku = [];
                    /** @var \Magento\Catalog\Model\ResourceModel\Product\Collection $productCollection */
                    if ($productCollection = $item->getProductCollection()) {
                        foreach ($productCollection as $product) {
                            $productSku[] = $product->getSku() . ':' . ($product->getCatIndexPosition() ?: 0);
                        }
                    }
                    $productSku = implode(',', $productSku);

                    $data[$itemId][$storeId][$code]              = htmlspecialchars_decode($attrValue);
                    $data[$itemId][$storeId][self::COL_PARENT]   = $item->getParentId();
                    $data[$itemId][$storeId][self::COL_PRODUCTS] = $productSku;
                    $data[$itemId][$storeId][self::COL_STORE_ID] = $storeId;
                }
            }
            $collection->clear();
        }

        return $data;
    }

    /**
     * @param $name
     *
     * @return mixed|string|string[]|null
     */
    public function convertUrl($name)
    {
        $name = strtolower($name);
        $name = strip_tags($name);
        $name = stripslashes($name);
        $name = html_entity_decode($name);

        $name = str_replace('\'', '', $name);

        $match   = '/[^a-z0-9]+/';
        $replace = '-';
        $name    = preg_replace($match, $replace, $name);

        $name = trim($name, '-');

        return $name;
    }

    /**
     * Initialize attribute option values and types.
     *
     * @return $this
     */
    protected function initAttributes()
    {
        foreach ($this->getAttributeCollection() as $attribute) {
            $this->_attributeValues[$attribute->getAttributeCode()] = $this->getAttributeOptions($attribute);
            $this->_attributeTypes[$attribute->getAttributeCode()]  =
                Import::getAttributeType($attribute);
            if ($attribute->getIsUserDefined()) {
                $this->userDefinedAttributes[] = $attribute->getAttributeCode();
            }
        }

        return $this;
    }

    /**
     * @param string $code
     * @param mixed $value
     *
     * @return bool
     */
    protected function isValidAttributeValue($code, $value)
    {
        $isValid = true;
        if (!is_numeric($value) && empty($value)) {
            $isValid = false;
        }

        if (!isset($this->_attributeValues[$code])) {
            $isValid = false;
        }

        return $isValid;
    }

    /**
     * Entity attributes collection getter.
     *
     * @return AttributeCollection
     */
    public function getAttributeCollection()
    {
        return $this->_attributeColFactory->create();
    }

    /**
     * EAV entity type code getter.
     *
     * @return string
     */
    public function getEntityTypeCode()
    {
        return 'catalog_category';
    }

    /**
     * @return array
     */
    protected function _getHeaderColumns()
    {
        $allAttrInHeaderColumns = [];
        $inclAttr               = [];

        foreach ($this->filterAttributeCollection($this->getAttributeCollection()) as $attribute) {
            foreach ($this->_headerColumns as $column) {
                if ($column === $attribute->getAttributeCode()) {
                    $allAttrInHeaderColumns[] = $attribute->getAttributeCode();
                }
            }
        }
        foreach ($this->_getExportAttrCodes() as $code) {
            foreach ($allAttrInHeaderColumns as $attrInHeaderColumn) {
                if ($code === $attrInHeaderColumn) {
                    $inclAttr[] = $attrInHeaderColumn;
                }
            }
        }
        $exclAttr = array_diff($allAttrInHeaderColumns, $inclAttr);

        return array_diff($this->_headerColumns, $exclAttr);
    }

    /**
     * {@inheritdoc}
     */
    protected function _getEntityCollection($resetCollection = false)
    {
        if ($resetCollection || empty($this->_entityCollection)) {
            $this->_entityCollection = $this->_entityColFactory->create();
        }

        return $this->_entityCollection;
    }

    /**
     * Set page and page size to collection
     *
     * @param int $page
     * @param int $pageSize
     *
     * @return void
     */
    protected function paginateCollection($page, $pageSize)
    {
        $this->_getEntityCollection()->setPage($page, $pageSize);
    }

    /**
     * Get items per page
     *
     * @return int
     */
    protected function getItemsPerPage()
    {
        if ($this->_itemsPerPage === null) {
            $memoryLimitConfig  = trim(ini_get('memory_limit'));
            $lastMemoryLimitLet = strtolower($memoryLimitConfig[strlen($memoryLimitConfig) - 1]);
            $memoryLimit        = (int) $memoryLimitConfig;
            switch ($lastMemoryLimitLet) {
                case 'g':
                    $memoryLimit *= 1024;
                // fall-through intentional
                case 'm':
                    $memoryLimit *= 1024;
                // fall-through intentional
                case 'k':
                    $memoryLimit *= 1024;
                    break;
                default:
                    // minimum memory required by Magento
                    $memoryLimit = 250000000;
            }

            // Tested one product to have up to such size
            $memoryPerProduct = 500000;
            // Decrease memory limit to have supply
            $memoryUsagePercent = 0.8;
            // Minimum Products limit
            $minProductsLimit = 500;
            // Maximal Products limit
            $maxProductsLimit = 5000;

            $this->_itemsPerPage =
                (int) (($memoryLimit * $memoryUsagePercent - memory_get_usage(true)) / $memoryPerProduct);
            if ($this->_itemsPerPage < $minProductsLimit) {
                $this->_itemsPerPage = $minProductsLimit;
            }
            if ($this->_itemsPerPage > $maxProductsLimit) {
                $this->_itemsPerPage = $maxProductsLimit;
            }
        }

        return $this->_itemsPerPage;
    }
}
