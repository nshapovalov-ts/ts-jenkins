<?php
/**
 * Retailplace_MageplazaCategoryImportExport
 *
 * @copyright   Copyright © 2021 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Satish Gumudavelly <satish@kipanga.com.au>
 */
namespace Retailplace\MageplazaCategoryImportExport\Model\Import;

use Magento\Cms\Model\Page\Source\PageLayout;
use Magento\Catalog\Model\ProductFactory;
use Magento\ImportExport\Helper\Data as ImportHelper;
use Magento\Framework\Filter\TranslitUrl;
use Magento\ImportExport\Model\ResourceModel\Import\Data as ImportData;
use Magento\Catalog\Model\Category\Attribute\Source\Sortby;
use Magento\CatalogImportExport\Model\Import\UploaderFactory;
use Magento\Framework\Filesystem;
use Magento\Catalog\Model\CategoryFactory;
use Magento\ImportExport\Model\ResourceModel\Helper as ResourceHelper;
use Magento\ImportExport\Model\Import\ErrorProcessing\ProcessingErrorAggregatorInterface;
use Magento\Framework\Json\Helper\Data as JsonHelper;
use Mageplaza\Core\Helper\AbstractData as HelperData;
use Magento\Catalog\Model\Category\Attribute\Source\Mode;
use Magento\Catalog\Model\Category as CategoryModel;

/**
 * Class Category
 */
class Category extends \Mageplaza\ImportExportCategories\Model\Import\Category
{
    /** @var string */
    const CHILD_CATEGORIES = 'child_categories';

    /**
     * If we should check column names
     *
     * @var bool
     */
    protected $needColumnCheck = false;

    /**
     * Save category
     *
     * @return \Mageplaza\ImportExportCategories\Model\Import\Category
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

                if (isset($rowData[self::CHILD_CATEGORIES])) {
                    $rowData[self::CHILD_CATEGORIES] = str_replace("|", ",", $rowData[self::CHILD_CATEGORIES]);
                }

                /** get parent category depends on the given parent id */
                $parentCategory = $this->_getParentCategory($rowData[self::COL_PARENT]);
                $rowData[self::COL_PATH] = ($parentCategory->getPath()) ?: (string) CategoryModel::TREE_ROOT_ID;
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
                $children = $parentCategory->getChildrenCategories();

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
}
