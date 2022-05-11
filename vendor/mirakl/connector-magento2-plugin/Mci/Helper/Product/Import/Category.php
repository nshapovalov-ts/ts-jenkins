<?php
namespace Mirakl\Mci\Helper\Product\Import;

use Magento\Catalog\Model\Category as CategoryModel;
use Magento\Catalog\Model\CategoryFactory as CategoryFactory;
use Magento\Catalog\Model\Product as ProductModel;
use Magento\Catalog\Model\ResourceModel\Category\Collection as CategoryCollection;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory as CategoryCollectionFactory;
use Magento\Catalog\Model\ResourceModel\CategoryFactory as CategoryResourceFactory;
use Magento\Eav\Model\Entity\Attribute\Set as AttributeSet;
use Magento\Eav\Model\ResourceModel\Entity\Attribute\Set\Collection as AttributeSetCollection;
use Magento\Eav\Model\ResourceModel\Entity\Attribute\Set\CollectionFactory as AttributeSetCollectionFactory;
use Magento\Framework\Exception\NotFoundException;
use Mirakl\Mci\Helper\Config;

class Category
{
    /**
     * @var Config
     */
    protected $config;

    /**
     * @var CategoryFactory
     */
    protected $categoryFactory;

    /**
     * @var CategoryResourceFactory
     */
    protected $categoryResourceFactory;

    /**
     * @var CategoryCollectionFactory
     */
    protected $categoryCollectionFactory;

    /**
     * @var AttributeSetCollectionFactory
     */
    protected $attrSetCollectionFactory;

    /**
     * @var CategoryCollection
     */
    private $categories;

    /**
     * @var AttributeSetCollection
     */
    private $attributeSets;

    /**
     * @param   Config                          $config
     * @param   CategoryFactory                 $categoryFactory
     * @param   CategoryResourceFactory         $categoryResourceFactory
     * @param   CategoryCollectionFactory       $categoryCollectionFactory
     * @param   AttributeSetCollectionFactory   $attrSetCollectionFactory
     */
    public function __construct(
        Config $config,
        CategoryFactory $categoryFactory,
        CategoryResourceFactory $categoryResourceFactory,
        CategoryCollectionFactory $categoryCollectionFactory,
        AttributeSetCollectionFactory $attrSetCollectionFactory
    ) {
        $this->config                    = $config;
        $this->categoryFactory           = $categoryFactory;
        $this->categoryResourceFactory   = $categoryResourceFactory;
        $this->categoryCollectionFactory = $categoryCollectionFactory;
        $this->attrSetCollectionFactory  = $attrSetCollectionFactory;
    }

    /**
     * Adds specified category to product
     *
     * @param   ProductModel    $product
     * @param   CategoryModel   $category
     * @return  ProductModel
     */
    public function addCategoryToProduct(ProductModel $product, CategoryModel $category)
    {
        $categoryIds = $product->getCategoryIds();
        $categoryIds[] = $category->getId();
        $product->setCategoryIds(array_unique($categoryIds));

        return $product;
    }

    /**
     * @return  AttributeSetCollection
     */
    public function getAttributeSets()
    {
        if (null === $this->attributeSets) {
            $this->attributeSets = $this->attrSetCollectionFactory->create();
        }

        return $this->attributeSets;
    }

    /**
     * @return  CategoryCollection
     * @throws  \Exception
     */
    public function getCategories()
    {
        if (null === $this->categories) {
            $rootId = $this->config->getHierarchyRootCategoryId();

            /** @var CategoryModel $root */
            $root = $this->categoryFactory->create();
            $this->categoryResourceFactory->create()->load($root, $rootId);

            /** @var CategoryCollection $collection */
            $this->categories = $this->categoryCollectionFactory->create();

            $this->categories->joinUrlRewrite()
                ->addAttributeToSelect('*')
                ->addFieldToFilter([
                    ['attribute' => 'path', 'like' => $root->getPath() . '/%'],
                    ['attribute' => 'path', 'eq' => $root->getPath()],
                ]);
        }

        return $this->categories;
    }

    /**
     * Tries to retrieve attribute set associated to specified category
     *
     * @param   CategoryModel   $category
     * @return  AttributeSet
     * @throws  NotFoundException
     */
    public function getCategoryAttributeSet(CategoryModel $category)
    {
        // Retrieve attribute set associated to the category
        $attrSetId = $category->getData(\Mirakl\Mci\Helper\Data::ATTRIBUTE_ATTR_SET);
        if (!$attrSetId) {
            /** @var CategoryModel $parent */
            $parent = $this->getCategories()->getItemById($category->getParentId());
            while ($parent && !$attrSetId) {
                // Try to find attribute set id in parent categories
                $attrSetId = $parent->getData(\Mirakl\Mci\Helper\Data::ATTRIBUTE_ATTR_SET);
                $parent = $this->getCategories()->getItemById($parent->getParentId());
            }

            if (!$attrSetId) {
                throw new NotFoundException(__('Could not find attribute set for category "%1"', $category->getId()));
            }
        }

        /** @var AttributeSet $attrSet */
        $attrSet = $this->getAttributeSets()->getItemById($attrSetId);
        if (!$attrSet) {
            throw new NotFoundException(__('Could not find attribute set with id "%1"', $attrSetId));
        }

        return $attrSet;
    }

    /**
     * Tries to retrieve category by given id
     *
     * @param   int $categoryId
     * @return  CategoryModel
     * @throws  NotFoundException
     */
    public function getCategoryById($categoryId)
    {
        /** @var CategoryModel $category */
        $category = $this->getCategories()->getItemById($categoryId);
        if (!$category) {
            throw new NotFoundException(__('Could not find category with id "%1"', $categoryId));
        }

        return $category;
    }
}
