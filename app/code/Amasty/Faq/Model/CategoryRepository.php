<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2021 Amasty (https://www.amasty.com)
 * @package Amasty_Faq
 */


namespace Amasty\Faq\Model;

use Amasty\Faq\Api\CategoryRepositoryInterface;
use Amasty\Faq\Api\Data\CategoryInterface;
use Amasty\Faq\Model\ResourceModel\Category as CategoryResource;
use Amasty\Faq\Model\ResourceModel\Category\Collection;
use Amasty\Faq\Model\ResourceModel\Category\CollectionFactory;
use Magento\Framework\Api\Search\FilterGroup;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Api\SortOrder;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Ui\Api\Data\BookmarkSearchResultsInterfaceFactory;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class CategoryRepository implements CategoryRepositoryInterface
{
    /**
     * @var BookmarkSearchResultsInterfaceFactory
     */
    private $searchResultsFactory;

    /**
     * @var CategoryFactory
     */
    private $categoryFactory;

    /**
     * @var CategoryResource
     */
    private $categoryResource;

    /**
     * Model data storage
     *
     * @var array
     */
    private $categories;

    /**
     * @var CollectionFactory
     */
    private $categoryCollectionFactory;

    public function __construct(
        BookmarkSearchResultsInterfaceFactory $searchResultsFactory,
        CategoryFactory $categoryFactory,
        CategoryResource $categoryResource,
        CollectionFactory $categoryCollectionFactory
    ) {
        $this->searchResultsFactory = $searchResultsFactory;
        $this->categoryFactory = $categoryFactory;
        $this->categoryResource = $categoryResource;
        $this->categoryCollectionFactory = $categoryCollectionFactory;
    }

    /**
     * @inheritdoc
     */
    public function save(CategoryInterface $category)
    {
        try {
            if ($category->getCategoryId()) {
                $category = $this->getById($category->getCategoryId())->addData($category->getData());
            }
            $this->categoryResource->save($category);
            unset($this->categories[$category->getCategoryId()]);
        } catch (\Exception $e) {
            if ($category->getCategoryId()) {
                throw new CouldNotSaveException(
                    __(
                        'Unable to save category with ID %1. Error: %2',
                        [$category->getCategoryId(), $e->getMessage()]
                    )
                );
            }
            throw new CouldNotSaveException(__('Unable to save new category. Error: %1', $e->getMessage()));
        }

        return $category;
    }

    /**
     * @inheritdoc
     */
    public function getById($categoryId)
    {
        if (!isset($this->categories[$categoryId])) {
            /** @var \Amasty\Faq\Model\Category $category */
            $category = $this->categoryFactory->create();
            $this->categoryResource->load($category, $categoryId);
            if (!$category->getCategoryId()) {
                throw new NoSuchEntityException(__('Category with specified ID "%1" not found.', $categoryId));
            }
            $this->categories[$categoryId] = $category;
        }

        return $this->categories[$categoryId];
    }

    /**
     * @inheritdoc
     */
    public function delete(CategoryInterface $category)
    {
        try {
            $this->categoryResource->delete($category);
            unset($this->categories[$category->getCategoryId()]);
        } catch (\Exception $e) {
            if ($category->getCategoryId()) {
                throw new CouldNotDeleteException(
                    __(
                        'Unable to remove category with ID %1. Error: %2',
                        [$category->getCategoryId(), $e->getMessage()]
                    )
                );
            }
            throw new CouldNotDeleteException(__('Unable to remove category. Error: %1', $e->getMessage()));
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    public function deleteById($categoryId)
    {
        $categoryModel = $this->getById($categoryId);
        $this->delete($categoryModel);

        return true;
    }

    /**
     * @inheritdoc
     */
    public function getList(SearchCriteriaInterface $searchCriteria)
    {
        $searchResults = $this->searchResultsFactory->create();
        $searchResults->setSearchCriteria($searchCriteria);

        /** @var \Amasty\Faq\Model\ResourceModel\Category\Collection $categoryCollection */
        $categoryCollection = $this->categoryCollectionFactory->create();
        // Add filters from root filter group to the collection
        foreach ($searchCriteria->getFilterGroups() as $group) {
            $this->addFilterGroupToCollection($group, $categoryCollection);
        }
        $searchResults->setTotalCount($categoryCollection->getSize());
        $sortOrders = $searchCriteria->getSortOrders();
        if ($sortOrders) {
            $this->addOrderToCollection($sortOrders, $categoryCollection);
        }
        $categoryCollection->setCurPage($searchCriteria->getCurrentPage());
        $categoryCollection->setPageSize($searchCriteria->getPageSize());
        $categories = [];
        /** @var CategoryInterface $category */
        foreach ($categoryCollection->getItems() as $category) {
            $categories[] = $this->getById($category->getId());
        }
        $searchResults->setItems($categories);

        return $searchResults;
    }

    /**
     * Helper function that adds a FilterGroup to the collection.
     *
     * @param FilterGroup $filterGroup
     * @param Collection  $categoryCollection
     *
     * @return void
     */
    private function addFilterGroupToCollection(FilterGroup $filterGroup, Collection $categoryCollection)
    {
        foreach ($filterGroup->getFilters() as $filter) {
            $condition = $filter->getConditionType() ? $filter->getConditionType() : 'eq';
            $categoryCollection->addFieldToFilter($filter->getField(), [$condition => $filter->getValue()]);
        }
    }

    /**
     * Helper function that adds a SortOrder to the collection.
     *
     * @param SortOrder[] $sortOrders
     * @param Collection  $categoryCollection
     *
     * @return void
     */
    private function addOrderToCollection($sortOrders, Collection $categoryCollection)
    {
        /** @var SortOrder $sortOrder */
        foreach ($sortOrders as $sortOrder) {
            $field = $sortOrder->getField();
            $categoryCollection->addOrder(
                $field,
                ($sortOrder->getDirection() == SortOrder::SORT_DESC) ? 'DESC' : 'ASC'
            );
        }
    }
}
