<?php

namespace Vdcstore\CategoryTree\Plugin\Import;

/**
 * Class CategoryLinkManagement
 */
class Category
{
    /**
     * @var \Vdcstore\CategoryTree\Model\Category
     */
    private $categoryModel;

    public function __construct(
        \Vdcstore\CategoryTree\Model\Category $categoryModel
    ) {
        $this->categoryModel = $categoryModel;
    }
    public function beforeAddCategoryToProduct(
        \Mirakl\Mci\Helper\Product\Import\Category $subject,
        $product,
        $category
    ) {
        $categoryIds = $product->getCategoryIds();
        $categoryIds[] = $category->getId();
        if ($categoryIds) {
            $categoryIds = $this->categoryModel->getMenuCategoryFromMappedCategory($categoryIds);
        }
        $product->setCategoryIds(array_unique($categoryIds));
        return [$product,$category];
    }
}
