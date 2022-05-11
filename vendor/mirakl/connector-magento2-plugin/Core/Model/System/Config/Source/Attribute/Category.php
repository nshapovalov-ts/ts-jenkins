<?php
namespace Mirakl\Core\Model\System\Config\Source\Attribute;

use Magento\Catalog\Model\Category as CategoryModel;
use Magento\Catalog\Model\CategoryFactory as CategoryFactory;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory as CategoryCollectionFactory;
use Magento\Framework\EntityManager\EntityManager;
use Magento\Framework\Profiler;

class Category
{
    /**
     * @var CategoryFactory
     */
    private $categoryFactory;

    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @var CategoryCollectionFactory
     */
    private $categoryCollectionFactory;

    /**
     * @var array
     */
    private $categoriesByParentIds = [];

    /**
     * @var array
     */
    private $requiredFields = [];

    /**
     * @var array
     */
    private $options;

    /**
     * @param   CategoryFactory             $categoryFactory
     * @param   CategoryCollectionFactory   $categoryCollectionFactory
     * @param   EntityManager               $entityManager
     * @param   array                       $requiredFields
     */
    public function __construct(
        CategoryFactory $categoryFactory,
        CategoryCollectionFactory $categoryCollectionFactory,
        EntityManager $entityManager,
        $requiredFields = ['parent_id', 'level', 'entity_id', 'name']
    ) {
        $this->categoryFactory = $categoryFactory;
        $this->categoryCollectionFactory = $categoryCollectionFactory;
        $this->entityManager = $entityManager;
        $this->requiredFields = $requiredFields;
    }

    /**
     * Get list of all available shops
     *
     * @return  array
     */
    public function getAllOptions()
    {
        if (null === $this->options) {
            $collection = $this->categoryCollectionFactory->create()
                ->addAttributeToSelect('name')
                ->addAttributeToSelect('all_children')
                ->addFieldToFilter('level', ['gt' => 0])
                ->setOrder('position', \Magento\Framework\Data\Collection::SORT_ORDER_ASC);

            /** @var array $category */
            foreach ($collection->toArray($this->requiredFields) as $category) {
                $this->categoriesByParentIds[$category['parent_id']][] = $category;
            }

            Profiler::start(__METHOD__);
            $this->options = $this->getCategoryOptions();
            Profiler::stop(__METHOD__);
        }

        return $this->options;
    }

    /**
     * @param   int $parentId
     * @return  array
     */
    protected function getCategoriesByParentId($parentId)
    {
        return isset($this->categoriesByParentIds[$parentId]) ? $this->categoriesByParentIds[$parentId] : [];
    }

    /**
     * Builds category options
     *
     * @param   array $category
     * @param   bool  $addEmpty
     * @return  array
     * @throws  \Exception
     */
    protected function getCategoryOptions($category = null, $addEmpty = true)
    {
        $options = [];

        if ($addEmpty) {
            $options[] = [
                'label' => __('-- Please Select a Category --'),
                'value' => '',
            ];
        }

        if (null === $category) {
            $category = $this->categoryFactory->create();
            $this->entityManager->load($category, CategoryModel::TREE_ROOT_ID);
            $category = $category->toArray($this->requiredFields);
        }

        if ($category['level'] > 0) {
            $label = trim(str_repeat('--', $category['level'] - 1) . ' ' . $category['name']);
            $options[] = [
                'value' => $category['entity_id'],
                'label' => sprintf('%s [%d]', $label, $category['entity_id']),
            ];
        }

        $children = $this->getCategoriesByParentId($category['entity_id']);
        foreach ($children as $child) {
            $options = array_merge($options, $this->getCategoryOptions($child, false));
        }

        return $options;
    }
}