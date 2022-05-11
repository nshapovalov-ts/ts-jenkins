<?php

namespace Vdcstore\CategoryTree\Plugin;

/**
 * Class CategoryLinkManagement
 */
class CategoryLinkManagement
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
    public function beforeAssignProductToCategories(
        \Magento\Catalog\Model\CategoryLinkManagement $subject,
        $productSku,
        $categoryIds
    ) {
        if ($categoryIds) {
            $categoryIds = $this->categoryModel->getMenuCategoryFromMappedCategory($categoryIds);
        }
        return [$productSku,$categoryIds];
    }
}
