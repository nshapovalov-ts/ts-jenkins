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

namespace Mageplaza\ImportExportCategories\Model\Import;

use Exception;
use InvalidArgumentException;
use Magento\Catalog\Model\Category as CategoryModel;
use Magento\Catalog\Model\Category\Attribute\Source\Mode;
use Magento\Catalog\Model\Category\Attribute\Source\Sortby;
use Magento\Catalog\Model\CategoryFactory;
use Magento\Catalog\Model\ProductFactory;
use Magento\Catalog\Model\ResourceModel\Category\Collection;
use Magento\CatalogImportExport\Model\Import\Uploader;
use Magento\CatalogImportExport\Model\Import\UploaderFactory;
use Magento\Cms\Model\Page\Source\PageLayout;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Filesystem;
use Magento\Framework\Filter\TranslitUrl;
use Magento\Framework\Json\Helper\Data as JsonHelper;
use Magento\Framework\Phrase;
use Magento\ImportExport\Helper\Data as ImportHelper;
use Magento\ImportExport\Model\Import;
use Magento\ImportExport\Model\Import\Entity\AbstractEntity;
use Magento\ImportExport\Model\Import\ErrorProcessing\ProcessingError;
use Magento\ImportExport\Model\Import\ErrorProcessing\ProcessingErrorAggregatorInterface;
use Magento\ImportExport\Model\ResourceModel\Helper as ResourceHelper;
use Magento\ImportExport\Model\ResourceModel\Import\Data as ImportData;
use Mageplaza\Core\Helper\AbstractData as HelperData;
use Zend_Serializer_Exception;

/**
 * Class CustomerGroup
 * @package Mageplaza\ProductAttachments\Model\Import
 */
class Category extends AbstractEntity
{
    /**
     * Error code
     */
    const ERROR_ROW_VALUE_IS_EMPTY     = 'EmptyRowValue';
    const ERROR_LABEL_IS_EMPTY         = 'EmptyLabel';
    const ERROR_ROW_VALUE_IS_DUPLICATE = 'RowValueIsDuplicate';
    /**
     * Import file cols
     */
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
    /**
     * Include database cols
     */
    const COL_ATTRIBUTE_SET_ID = 'attribute_set_id';
    const COL_PARENT_ID        = 'parent_id';
    const COL_PATH             = 'path';
    const COL_LEVEL            = 'level';
    const COL_POSTED_PRODUCTS  = 'posted_products';

    /**
     * @var array
     */
    protected $_permanentAttributes = [
        self::COL_CATEGORY_ID,
        self::COL_STORE_ID,
        self::COL_PARENT,
        self::COL_URL_KEY,
        self::COL_PRODUCT_SKU,
        self::COL_DEFAULT_SORT_BY,
        self::COL_AVAILABLE_SORT_BY,
        self::COL_NAME,
        self::COL_DISPLAY_MODE,
    ];

    /**
     * @var array
     */
    protected $_permanentRows = [
        self::COL_NAME,
        self::COL_URL_KEY,
    ];

    /**
     * If we should check column names
     *
     * @var bool
     */
    protected $needColumnCheck = true;

    /**
     * Valid column names
     *
     * @array
     */
    protected $validColumnNames = [
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
    ];

    /**
     * Need to log in import history
     *
     * @var bool
     */
    protected $logInHistory = true;

    /**
     * @var ProductFactory
     */
    protected $_productFactory;

    /**
     * @var Mode
     */
    protected $_displayMode;

    /**
     * @var Sortby
     */
    protected $_sortBy;

    /**
     * @var UploaderFactory
     */
    protected $_uploaderFactory;

    /**
     * @var Filesystem
     */
    protected $_mediaDirectory;

    /**
     * @var CategoryFactory
     */
    protected $_categoryFactory;

    /**
     * @var PageLayout
     */
    protected $_pageLayout;

    /**
     * @var TranslitUrl
     */
    protected $_tranSlitUrl;

    /**
     * @var HelperData
     */
    protected $_helperData;

    /**
     * Category constructor.
     *
     * @param JsonHelper $jsonHelper
     * @param ImportHelper $importExportData
     * @param ImportData $importData
     * @param ResourceHelper $resourceHelper
     * @param ProcessingErrorAggregatorInterface $errorAggregator
     * @param ProductFactory $productFactory
     * @param Mode $displayMode
     * @param Sortby $sortBy
     * @param UploaderFactory $uploaderFactory
     * @param Filesystem $filesystem
     * @param CategoryFactory $categoryFactory
     * @param PageLayout $pageLayout
     * @param TranslitUrl $tranSlitUrl
     * @param HelperData $helperData
     *
     * @throws FileSystemException
     */
    public function __construct(
        JsonHelper $jsonHelper,
        ImportHelper $importExportData,
        ImportData $importData,
        ResourceHelper $resourceHelper,
        ProcessingErrorAggregatorInterface $errorAggregator,
        ProductFactory $productFactory,
        Mode $displayMode,
        Sortby $sortBy,
        UploaderFactory $uploaderFactory,
        Filesystem $filesystem,
        CategoryFactory $categoryFactory,
        PageLayout $pageLayout,
        TranslitUrl $tranSlitUrl,
        HelperData $helperData
    ) {
        $this->jsonHelper        = $jsonHelper;
        $this->_importExportData = $importExportData;
        $this->_resourceHelper   = $resourceHelper;
        $this->_dataSourceModel  = $importData;
        $this->errorAggregator   = $errorAggregator;
        $this->_productFactory   = $productFactory;
        $this->_displayMode      = $displayMode;
        $this->_sortBy           = $sortBy;
        $this->_uploaderFactory  = $uploaderFactory;
        $this->_mediaDirectory   = $filesystem->getDirectoryWrite(DirectoryList::ROOT);
        $this->_categoryFactory  = $categoryFactory;
        $this->_pageLayout       = $pageLayout;
        $this->_tranSlitUrl      = $tranSlitUrl;
        $this->_helperData       = $helperData;
    }

    /**
     * Entity type code getter.
     *
     * @return string
     */
    public function getEntityTypeCode()
    {
        return 'mageplaza_categories_import';
    }

    /**
     * @return $this
     * @throws LocalizedException
     * @throws Zend_Serializer_Exception
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    protected function _saveValidatedBunches()
    {
        $source          = $this->_getSource();
        $currentDataSize = 0;
        $bunchRows       = [];
        $startNewBunch   = false;
        $nextRowBackup   = [];
        $maxDataSize     = $this->_resourceHelper->getMaxDataSize();
        $bunchSize       = $this->_importExportData->getBunchSize();

        $source->rewind();
        $this->_dataSourceModel->cleanBunches();
        while ($source->valid() || $bunchRows) {
            if ($startNewBunch || !$source->valid()) {
                $this->_dataSourceModel->saveBunch($this->getEntityTypeCode(), $this->getBehavior(), $bunchRows);

                $bunchRows       = $nextRowBackup;
                $currentDataSize = strlen($this->_helperData->serialize($bunchRows));
                $startNewBunch   = false;
                $nextRowBackup   = [];
            }
            if ($source->valid()) {
                try {
                    $rowData = $source->current();
                } catch (InvalidArgumentException $e) {
                    $this->addRowError($e->getMessage(), $this->_processedRowsCount);
                    $this->_processedEntitiesCount++;
                    $this->_processedRowsCount++;
                    $source->next();
                    continue;
                }
                $this->_processedEntitiesCount++;
                $this->_processedRowsCount++;

                if ($this->validateRow($rowData, $source->key())) {
                    // add row to bunch for save
                    $rowData = $this->_prepareRowForDb($rowData);
                    $rowSize = strlen($this->jsonHelper->jsonEncode($rowData));

                    $isBunchSizeExceeded = $bunchSize > 0 && count($bunchRows) >= $bunchSize;

                    if ($currentDataSize + $rowSize >= $maxDataSize || $isBunchSizeExceeded) {
                        $startNewBunch = true;
                        $nextRowBackup = [$source->key() => $rowData];
                    } else {
                        $bunchRows[$source->key()] = $rowData;
                        $currentDataSize           += $rowSize;
                    }
                }
                $source->next();
            }
        }

        return $this;
    }

    /**
     * Row validation.
     *
     * @param array $rowData
     * @param int $rowNum
     *
     * @return bool
     */
    public function validateRow(array $rowData, $rowNum)
    {
        if (isset($this->_validatedRows[$rowNum])) {
            return !$this->getErrorAggregator()->isRowInvalid($rowNum);
        }
        $this->_validatedRows[$rowNum] = true;

        /** check if rows is empty value */
        foreach ($this->_permanentRows as $permanentRow) {
            if (!isset($rowData[$permanentRow]) || empty($rowData[$permanentRow]) && $rowData[$permanentRow] !== '0') {
                $this->addRowError(
                    self::ERROR_ROW_VALUE_IS_EMPTY,
                    $rowNum,
                    $permanentRow,
                    $this->getRowEmptyMessage($permanentRow, $rowNum)
                );

                return false;
            }
        }
        if ($rowData[self::COL_PARENT] === $rowData[self::COL_CATEGORY_ID]) {
            $this->addRowError(
                self::ERROR_ROW_VALUE_IS_DUPLICATE,
                $rowNum,
                self::COL_PARENT,
                __('Col parent and category ID must be different')
            );

            return false;
        }
        $tmpParentCategory = $this->_categoryFactory->create()->load($rowData[self::COL_PARENT]);
        $parentIds         = $tmpParentCategory->getParentIds();
        if (in_array($rowData[self::COL_CATEGORY_ID], $parentIds)) {
            $this->addRowError(
                self::ERROR_ROW_VALUE_IS_DUPLICATE,
                $rowNum,
                self::COL_PARENT,
                __('The new parent field could not be the current child of this category')
            );

            return false;
        }

        return !$this->getErrorAggregator()->isRowInvalid($rowNum);
    }

    /**
     * Get row empty message description
     *
     * @param string $colName
     * @param int $rowNum
     *
     * @return Phrase
     */
    public function getRowEmptyMessage($colName, $rowNum)
    {
        return __('Value of Column %1 is empty', $colName, $rowNum);
    }

    /**
     * Import data actions
     *
     * @return bool Result of operation.
     * @throws Exception
     */
    protected function _importData()
    {
        if (Import::BEHAVIOR_DELETE === $this->getBehavior()) {
            $this->deleteCategories();
        } elseif (Import::BEHAVIOR_REPLACE === $this->getBehavior()) {
            $this->replaceCategories();
        } elseif (Import::BEHAVIOR_APPEND === $this->getBehavior()) {
            $this->_saveCategoriesData();
        }

        return true;
    }

    /**
     * Save category
     *
     * @return $this
     * @throws Exception
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    protected function _saveCategoriesData()
    {
        $mappingParentIds = [];
        while ($bunch = $this->_dataSourceModel->getNextBunch()) {
            foreach ($bunch as $rowNum => $rowData) {
                if (!$this->validateRow($rowData, $rowNum)) {
                    $this->addRowError(self::ERROR_LABEL_IS_EMPTY, $rowNum);
                    continue;
                }
                if ($this->getErrorAggregator()->getErrorsCount()
                    > $this->getErrorAggregator()->getAllowedErrorsCount()) {
                    $this->getErrorAggregator()->addRowToSkip($rowNum);
                    continue;
                }
                foreach ($mappingParentIds as $oldParentId => $newParentId) {
                    if ($rowData[self::COL_PARENT] == $oldParentId && $oldParentId != 0) {
                        $rowData[self::COL_PARENT] = $newParentId;
                        break;
                    }
                }
                /** get parent category depends on the given parent id */
                $parentCategory               = $this->_getParentCategory($rowData[self::COL_PARENT]);
                $rowData[self::COL_PATH]      = ($parentCategory->getPath()) ?: (string) CategoryModel::TREE_ROOT_ID;
                $rowData[self::COL_PARENT_ID] = $parentCategory->getId() ?: (string) CategoryModel::TREE_ROOT_ID;
                /** check value array for display mode field */
                $rowData[self::COL_DISPLAY_MODE] = $this->_getDisplayModeField($rowData[self::COL_DISPLAY_MODE]);
                /** check value array for page layout field */
                $rowData[self::COL_PAGE_LAYOUT] = $this->_getPageLayoutField($rowData[self::COL_PAGE_LAYOUT]);
                /** check value array for available sort by field */
                $rowData[self::COL_AVAILABLE_SORT_BY] = $this->_getSortByField($rowData[self::COL_AVAILABLE_SORT_BY]);
                /** check value for default sort by field */
                if ($rowData[self::COL_DEFAULT_SORT_BY]
                    && !in_array($rowData[self::COL_DEFAULT_SORT_BY], $rowData[self::COL_AVAILABLE_SORT_BY])) {
                    unset($rowData[self::COL_DEFAULT_SORT_BY]);
                }
                $category = $this->_categoryFactory->create();
                /** get attribute set id for category */
                $rowData[self::COL_ATTRIBUTE_SET_ID] = $category->getDefaultAttributeSetId();
                $children                            = $parentCategory->getChildrenCategories();

                /** save products to category */
                $rowData[self::COL_POSTED_PRODUCTS]
                    = $this->_getProductsData($rowData[self::COL_PRODUCT_SKU]);
                unset($rowData[self::COL_PRODUCT_SKU]);
                /** upload all image files */
                if ($rowData[self::COL_IMAGE]) {
                    $this->_uploadFile()->move($rowData[self::COL_IMAGE], false);
                }

                $mappingParentIds [$rowData[self::COL_CATEGORY_ID]]
                    = $this->_saveCategoriesFinish($rowData, $category, $children, $rowNum);
            }
            if ($this->getErrorAggregator()->getErrorsCount() <= $this->getErrorAggregator()->getAllowedErrorsCount()) {
                $this->getErrorAggregator()->clear();
            }
        }

        return $this;
    }

    /**
     * @param string $rowDisplayMode
     *
     * @return string
     */
    protected function _getDisplayModeField($rowDisplayMode)
    {
        foreach ($this->_displayMode->getAllOptions() as $displayMode) {
            if ($rowDisplayMode === $displayMode['label']->getText()) {
                return $displayMode['value'];
            }
        }

        return CategoryModel::DM_PRODUCT;
    }

    /**
     * @param string $rowPageLayout
     *
     * @return string
     */
    protected function _getPageLayoutField($rowPageLayout)
    {
        $pageLayouts = [];
        foreach ($this->_pageLayout->toOptionArray() as $pageLayout) {
            $pageLayouts[] = $pageLayout['value'];
        }
        if (!in_array($rowPageLayout, $pageLayouts, true)) {
            return '';
        }

        return $rowPageLayout;
    }

    /**
     * @param string $rowSortBy
     *
     * @return array
     */
    protected function _getSortByField($rowSortBy)
    {
        $sortsBy = [];
        foreach ($this->_sortBy->getAllOptions() as $sortBy) {
            $sortsBy[] = $sortBy['value'];
        }
        $delimiter = $this->_parameters[Import::FIELD_FIELD_MULTIPLE_VALUE_SEPARATOR];

        $rowSortBy = explode($delimiter, $rowSortBy);
        if ($sortByArray = array_intersect($sortsBy, $rowSortBy)) {
            return $sortByArray;
        }

        return [];
    }

    /**
     * Save product data
     *
     * @param string $rowSku
     *
     * @return array
     */
    protected function _getProductsData($rowSku)
    {
        $delimiter     = $this->_parameters[Import::FIELD_FIELD_MULTIPLE_VALUE_SEPARATOR];
        $rowProductSku = [];
        if ($rowSku) {
            $productSku = explode($delimiter, $rowSku);
            $product    = $this->_productFactory->create();
            $productIds = [];
            foreach ($productSku as $skus) {
                [$sku, $skuPosition] = explode(':', $skus);
                if ($product->getIdBySku($sku)) {
                    $productIds[$product->getIdBySku($sku)] = $skuPosition;
                }
            }
            $rowProductSku = $productIds;
        }

        return $rowProductSku;
    }

    /**
     * Save categories to the db
     *
     * @param array $rowData
     * @param CategoryModel $category
     * @param Collection|CategoryModel[] $children
     * @param int $rowNum
     *
     * @return string
     * @throws LocalizedException
     */
    protected function _saveCategoriesFinish($rowData, $category, $children, $rowNum)
    {
        $newCategoryId = 0;
        if ($rowData[self::COL_CATEGORY_ID]
            && $rowData[self::COL_CATEGORY_ID] !== '1'
            && $category->getResource()->checkId($rowData[self::COL_CATEGORY_ID])) {
            $oldUrls = [];
            /** get old URLs */
            foreach ($children as $child) {
                /** @var CategoryModel $child */
                if ($rowData[self::COL_CATEGORY_ID] != $child->getId()) {
                    $oldUrls [] = $child->getUrlKey();
                }
            }
            $updateCategory             = $category->load($rowData[self::COL_CATEGORY_ID]);
            $rowData[self::COL_URL_KEY] = $this->generateUrlKey($rowData[self::COL_URL_KEY], $oldUrls);
            $rowData[self::COL_PATH]    .= '/' . $updateCategory->getEntityId();
            $newCategoryId              = $updateCategory->getEntityId();
            try {
                if ($rowData[self::COL_PARENT] !== $updateCategory->getParentId()) {
                    $this->updateChildCategories($updateCategory, $rowData[self::COL_PATH]);
                    $rowData[self::COL_LEVEL] = count(explode('/', $rowData[self::COL_PATH])) - 1;
                }
                $updateCategory->addData($rowData)->save();
                $this->countItemsUpdated++;
            } catch (Exception $e) {
                if ($this->_parameters[Import::FIELD_NAME_VALIDATION_STRATEGY]
                    == ProcessingErrorAggregatorInterface::VALIDATION_STRATEGY_STOP_ON_ERROR) {
                    throw new LocalizedException(__($e->getMessage()));
                }
                $this->getErrorAggregator()->addError(
                    self::ERROR_CODE_ATTRIBUTE_NOT_VALID,
                    ProcessingError::ERROR_LEVEL_CRITICAL,
                    $rowNum
                );
            }
        } else {
            /** save category entity */
            $category = $this->_categoryFactory->create();
            $data     = $rowData;
            unset($data[self::COL_CATEGORY_ID]);
            try {
                $category->addData($data)->save();
                $newCategoryId = $category->getId();
                $this->countItemsCreated++;
            } catch (Exception $e) {
                if ($this->_parameters[Import::FIELD_NAME_VALIDATION_STRATEGY]
                    == ProcessingErrorAggregatorInterface::VALIDATION_STRATEGY_STOP_ON_ERROR) {
                    throw new LocalizedException(__($e->getMessage()));
                }
                $this->getErrorAggregator()->addError(
                    self::ERROR_CODE_ATTRIBUTE_NOT_VALID,
                    ProcessingError::ERROR_LEVEL_CRITICAL,
                    $rowNum
                );
            }
        }

        return $newCategoryId;
    }

    /**
     * @param CategoryModel $updateCategory
     * @param string $oldPath
     *
     * @throws Exception
     */
    public function updateChildCategories($updateCategory, $oldPath)
    {
        if ($updateCategory->hasChildren()) {
            $children = $updateCategory->getChildrenCategories();
            foreach ($children as $child) {
                /** @var CategoryModel $child */
                $newPath = $oldPath . '/' . $child->getEntityId();
                $level   = count(explode('/', $newPath)) - 1;
                $this->updateChildCategories($child, $newPath);
                $child->setPath($newPath)
                    ->setLevel($level)
                    ->save();
            }
        }
    }

    /**
     * Delete category
     *
     * @return $this
     * @throws Exception
     */
    public function deleteCategories()
    {
        $category = $this->_categoryFactory->create();
        while ($bunch = $this->_dataSourceModel->getNextBunch()) {
            foreach ($bunch as $rowNum => $rowData) {
                $this->validateRow($rowData, $rowNum);

                if (!$this->getErrorAggregator()->isRowInvalid($rowNum)
                    && $rowData[self::COL_CATEGORY_ID]
                    && $category->getResource()->checkId($rowData[self::COL_CATEGORY_ID])) {
                    try {
                        $category->load($rowData[self::COL_CATEGORY_ID])->delete();
                        $this->countItemsDeleted++;
                    } catch (Exception $e) {
                        if ($this->_parameters[Import::FIELD_NAME_VALIDATION_STRATEGY]
                            == ProcessingErrorAggregatorInterface::VALIDATION_STRATEGY_STOP_ON_ERROR) {
                            throw new LocalizedException(__($e->getMessage()));
                        }
                        $this->getErrorAggregator()->addError(
                            self::ERROR_CODE_ATTRIBUTE_NOT_VALID,
                            ProcessingError::ERROR_LEVEL_CRITICAL,
                            $rowNum
                        );
                    }
                }
            }
            if ($this->getErrorAggregator()->getErrorsCount() <= $this->getErrorAggregator()->getAllowedErrorsCount()) {
                $this->getErrorAggregator()->clear();
            }
        }

        return $this;
    }

    /**
     * Replace category
     *
     * @return $this
     * @throws Exception
     */
    public function replaceCategories()
    {
        $this->deleteCategories();
        $this->_saveCategoriesData();

        return $this;
    }

    /**
     * Get parent category
     *
     * @param int $parentId
     *
     * @return CategoryModel
     */
    protected function _getParentCategory($parentId)
    {
        if (!$parentId) {
            $parentId = CategoryModel::TREE_ROOT_ID;
        }

        return $this->_categoryFactory->create()->load($parentId);
    }

    /**
     * @return Uploader
     * @throws LocalizedException
     * @throws FileSystemException
     */
    protected function _uploadFile()
    {
        $fileUploader = $this->_uploaderFactory->create();
        $dirConfig    = DirectoryList::getDefaultConfig();
        $dirAddOn     = $dirConfig[DirectoryList::MEDIA][DirectoryList::PATH];

        if (!empty($this->_parameters[Import::FIELD_NAME_IMG_FILE_DIR])) {
            $tmpPath = $this->_parameters[Import::FIELD_NAME_IMG_FILE_DIR];
        } else {
            $tmpPath = $dirAddOn . DIRECTORY_SEPARATOR . $this->_mediaDirectory->getRelativePath('import');
        }
        if (!$fileUploader->setTmpDir($tmpPath)) {
            throw new LocalizedException(
                __('File directory \'%1\' is not readable.', $tmpPath)
            );
        }
        $destinationPath = $dirAddOn . DIRECTORY_SEPARATOR . $this->_mediaDirectory
                ->getRelativePath('catalog/category');

        $this->_mediaDirectory->create($destinationPath);
        if (!$fileUploader->setDestDir($destinationPath)) {
            throw new LocalizedException(
                __('File directory \'%1\' is not writable.', $destinationPath)
            );
        }

        return $fileUploader;
    }

    /**
     * @param string $newUrl
     * @param array $oldUrls
     *
     * @return string
     * @throws LocalizedException
     */
    public function generateUrlKey($newUrl, $oldUrls)
    {
        $attempt   = 0;
        $originUrl = $newUrl = $this->_tranSlitUrl->filter($newUrl);
        while (in_array($newUrl, $oldUrls)) {
            if ($attempt++ > 10) {
                throw new LocalizedException(
                    __('Unable to generate url key. Please check the setting and try again.')
                );
            }
            $newUrl = $originUrl . $attempt;
        }

        return $newUrl;
    }
}
