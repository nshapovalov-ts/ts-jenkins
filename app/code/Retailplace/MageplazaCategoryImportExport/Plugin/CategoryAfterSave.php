<?php

/**
 * Retailplace_MageplazaCategoryImportExport
 *
 * @copyright Copyright © 2021 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author    Satish Gumudavelly <satish@kipanga.com.au>
 */
declare(strict_types=1);

namespace Retailplace\MageplazaCategoryImportExport\Plugin;

use Magento\Catalog\Model\Category;
use Vdcstore\CategoryTree\Helper\UpdateAttributeHelper;
use Mirakl\Mci\Helper\Config;

/**
 * Class CategoryAfterSave
 */
class CategoryAfterSave
{

    /**
     * @var \Vdcstore\CategoryTree\Model\Category
     */
    private $categoryModel;

    /**
     * @var Config
     */
    private $miraklConfigHelper;

    /**
     * @var UpdateAttributeHelper
     */
    private $updateAttributeHelper;

    /**
     * SetCategoryProducts constructor.
     * @param \Vdcstore\CategoryTree\Model\Category $categoryModel
     * @param Config $miraklConfigHelper
     * @param UpdateAttributeHelper $updateAttributeHelper
     */
    public function __construct(
        \Vdcstore\CategoryTree\Model\Category $categoryModel,
        Config $miraklConfigHelper,
        UpdateAttributeHelper $updateAttributeHelper
    ) {
        $this->categoryModel = $categoryModel;
        $this->miraklConfigHelper = $miraklConfigHelper;
        $this->updateAttributeHelper = $updateAttributeHelper;
    }

    /**
     * @param Category $category
     * @param $result
     * @return void|null
     */
    public function afterSave(Category $category, $result)
    {

        try {
            $attributes = [];
            $categoryId = $category->getId();
            if (strpos($category->getPath(), "1/{$this->miraklConfigHelper->getHierarchyRootCategoryId()}/") !== false) {
                $this->categoryModel->setMiraklCategoryProductsToMenuCategory($category);
            } else {
                if ($category->getData('exclude_from_menu')) {
                    if ($category->getData('include_in_menu')) {
                        $attributes['include_in_menu'] = 0;
                    }
                } else {
                    $childCategories = $category->getData('child_categories');
                    $this->categoryModel->copyMiraklProductIdsToMenuCategory($childCategories, $category);
                    if (!$category->getData('include_in_menu')) {
                        $attributes['include_in_menu'] = (bool) $childCategories;
                    }
                }
                if ($categoryId && $attributes) {
                    $this->updateAttributeHelper->updateCategoryAttributes([$categoryId], $attributes);
                }
            }
        } catch (\Exception $e) {
            echo $e->getMessage();
            die;
        } catch (\Error $e) {
            echo $e->getMessage();
            die;
        }

        return null;
    }
}
