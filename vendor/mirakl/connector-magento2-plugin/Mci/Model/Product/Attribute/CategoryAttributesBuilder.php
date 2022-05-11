<?php
namespace Mirakl\Mci\Model\Product\Attribute;

use Magento\Catalog\Model\Category;
use Magento\Catalog\Model\CategoryFactory;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory as CategoryCollectionFactory;
use Magento\Catalog\Model\ResourceModel\CategoryFactory as CategoryResourceFactory;
use Magento\Store\Api\Data\StoreInterface;
use Mirakl\Mci\Helper\Config as MciConfig;
use Mirakl\Mci\Helper\Data as MciHelper;

class CategoryAttributesBuilder extends \ArrayObject
{
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
     * @var MciConfig
     */
    protected $mciConfig;

    /**
     * @var ProductAttributesFinder
     */
    protected $productAttributesFinder;

    /**
     * @param   CategoryFactory             $categoryFactory
     * @param   CategoryResourceFactory     $categoryResourceFactory
     * @param   CategoryCollectionFactory   $categoryCollectionFactory
     * @param   MciConfig                   $mciConfig
     * @param   ProductAttributesFinder     $productAttributesFinder
     */
    public function __construct(
        CategoryFactory $categoryFactory,
        CategoryResourceFactory $categoryResourceFactory,
        CategoryCollectionFactory $categoryCollectionFactory,
        MciConfig $mciConfig,
        ProductAttributesFinder $productAttributesFinder
    ) {
        $this->categoryFactory = $categoryFactory;
        $this->categoryResourceFactory = $categoryResourceFactory;
        $this->categoryCollectionFactory = $categoryCollectionFactory;
        $this->mciConfig = $mciConfig;
        $this->productAttributesFinder = $productAttributesFinder;
    }

    /**
     * @param   Category|null   $category
     * @return  $this
     */
    public function build($category = null)
    {
        $tree = $this->getTree($category);
        $this->buildAssoc($tree);
        $this->removeAncestors($tree);

        return $this;
    }

    /**
     * @param   array   $node
     */
    private function buildAssoc(array $node)
    {
        // Add exportable attributes
        $exportableCodes = [];
        foreach ($node['attributes'] as $attrCode) {
            if ($this->isAttributeExportable($attrCode)) {
                $exportableCodes[] = $attrCode;
            }
        }

        $this->offsetSet($node['code'], $exportableCodes);

        // Check children
        if (array_key_exists('children', $node) && !empty($node['children'])) {
            foreach ($node['children'] as $child) {
                $this->buildAssoc($child);
            }
        }
    }

    /**
     * @param   Category    $category
     * @return  array
     */
    protected function getCategoryAttributes(Category $category)
    {
        return $this->productAttributesFinder->findBySetId($category->getData(MciHelper::ATTRIBUTE_ATTR_SET));
    }

    /**
     * @return  int
     */
    protected function getRootCategoryId()
    {
        return $this->mciConfig->getHierarchyRootCategoryId();
    }

    /**
     * @return  StoreInterface
     */
    protected function getStore()
    {
        return $this->mciConfig->getCatalogIntegrationStore();
    }

    /**
     * @param   Category|null   $category
     * @return  array
     * @throws  \Exception
     */
    private function getTree($category = null)
    {
        $store = $this->getStore();
        $code = '';

        if (!$category) {
            $category = $this->categoryFactory->create()->setStoreId($store->getId());
            $this->categoryResourceFactory->create()->load($category, $this->getRootCategoryId());
        } else {
            $code = $category->getId();
        }

        $tree = [
            'code' => $code,
            'name' => $category->getName(),
            'attributes' => array_keys($this->getCategoryAttributes($category)),
            'children' => [],
        ];

        /** @var \Magento\Catalog\Model\ResourceModel\Category\Collection $children */
        $children = $this->categoryCollectionFactory->create();
        $children->setStoreId($store->getId())
            ->addAttributeToSelect('name')
            ->addAttributeToSelect(MciHelper::ATTRIBUTE_ATTR_SET)
            ->setOrder('position', \Magento\Framework\DB\Select::SQL_ASC)
            ->addFieldToFilter('parent_id', $category->getId());

        foreach ($children as $child) {
            /** @var Category $child */
            $tree['children'][] = $this->getTree($child);
        }

        return $tree;
    }

    /**
     * @param   string  $attrCode
     * @return  bool
     */
    protected function isAttributeExportable($attrCode)
    {
        $attribute = $this->productAttributesFinder->findByCode($attrCode);
        $allowedAttributes = $this->productAttributesFinder->getExportableAttributes();

        return $attribute && array_key_exists($attribute->getId(), $allowedAttributes);
    }

    /**
     * @param   array   $node
     * @param   array   $parentAttributes
     */
    private function removeAncestors(array $node, $parentAttributes = [])
    {
        $this->offsetSet($node['code'], array_diff($this->offsetGet($node['code']), $parentAttributes));
        $parentAttributes = array_merge($parentAttributes, $this->offsetGet($node['code']));

        if (array_key_exists('children', $node) && !empty($node['children'])) {
            foreach ($node['children'] as $child) {
                $this->removeAncestors($child, $parentAttributes);
            }
        }
    }
}