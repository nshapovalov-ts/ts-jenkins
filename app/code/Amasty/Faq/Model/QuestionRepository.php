<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2021 Amasty (https://www.amasty.com)
 * @package Amasty_Faq
 */


namespace Amasty\Faq\Model;

use Amasty\Faq\Api\Data\QuestionInterface;
use Amasty\Faq\Api\QuestionRepositoryInterface;
use Amasty\Faq\Model\OptionSource\Question\Status;
use Amasty\Faq\Model\ResourceModel\Question as QuestionResource;
use Amasty\Faq\Model\ResourceModel\Question\Collection;
use Amasty\Faq\Model\ResourceModel\Question\CollectionFactory;
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
class QuestionRepository implements QuestionRepositoryInterface
{
    /**
     * @var BookmarkSearchResultsInterfaceFactory
     */
    private $searchResultsFactory;

    /**
     * @var QuestionFactory
     */
    private $questionFactory;

    /**
     * @var QuestionResource
     */
    private $questionResource;

    /**
     * Model data storage
     *
     * @var array
     */
    private $questions;

    /**
     * @var CollectionFactory
     */
    private $questionCollectionFactory;

    public function __construct(
        BookmarkSearchResultsInterfaceFactory $searchResultsFactory,
        QuestionFactory $questionFactory,
        QuestionResource $questionResource,
        CollectionFactory $questionCollectionFactory
    ) {
        $this->searchResultsFactory = $searchResultsFactory;
        $this->questionFactory = $questionFactory;
        $this->questionResource = $questionResource;
        $this->questionCollectionFactory = $questionCollectionFactory;
    }

    /**
     * @inheritdoc
     */
    public function save(QuestionInterface $question)
    {
        try {
            $question = $this->prepareQuestionForSave($question);

            $this->questionResource->save($question);
            unset($this->questions[$question->getQuestionId()]);
        } catch (\Exception $e) {
            if ($question->getQuestionId()) {
                throw new CouldNotSaveException(
                    __(
                        'Unable to save question with ID %1. Error: %2',
                        [$question->getQuestionId(), $e->getMessage()]
                    )
                );
            }
            throw new CouldNotSaveException(__('Unable to save new question. Error: %1', $e->getMessage()));
        }

        return $question;
    }

    /**
     * @inheritdoc
     */
    public function getById($questionId)
    {
        if (!isset($this->questions[$questionId])) {
            /** @var \Amasty\Faq\Model\Question $question */
            $question = $this->questionFactory->create();
            $this->questionResource->load($question, $questionId);
            if (!$question->getQuestionId()) {
                throw new NoSuchEntityException(__('Question with specified ID "%1" not found.', $questionId));
            }
            $this->questions[$questionId] = $question;
        }

        return $this->questions[$questionId];
    }

    /**
     * @inheritdoc
     */
    public function delete(QuestionInterface $question)
    {
        try {
            $this->questionResource->delete($question);
            unset($this->questions[$question->getQuestionId()]);
        } catch (\Exception $e) {
            if ($question->getQuestionId()) {
                throw new CouldNotDeleteException(
                    __(
                        'Unable to remove question with ID %1. Error: %2',
                        [$question->getQuestionId(), $e->getMessage()]
                    )
                );
            }
            throw new CouldNotDeleteException(__('Unable to remove question. Error: %1', $e->getMessage()));
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    public function deleteById($questionId)
    {
        $questionModel = $this->getById($questionId);
        $this->delete($questionModel);

        return true;
    }

    /**
     * @inheritdoc
     */
    public function getList(SearchCriteriaInterface $searchCriteria)
    {
        $searchResults = $this->searchResultsFactory->create();
        $searchResults->setSearchCriteria($searchCriteria);

        /** @var \Amasty\Faq\Model\ResourceModel\Question\Collection $questionCollection */
        $questionCollection = $this->questionCollectionFactory->create();
        // Add filters from root filter group to the collection
        foreach ($searchCriteria->getFilterGroups() as $group) {
            $this->addFilterGroupToCollection($group, $questionCollection);
        }
        $searchResults->setTotalCount($questionCollection->getSize());
        $sortOrders = $searchCriteria->getSortOrders();
        if ($sortOrders) {
            $this->addOrderToCollection($sortOrders, $questionCollection);
        }
        $questionCollection->setCurPage($searchCriteria->getCurrentPage());
        $questionCollection->setPageSize($searchCriteria->getPageSize());
        $questions = [];
        /** @var QuestionInterface $question */
        foreach ($questionCollection->getItems() as $question) {
            $questions[] = $this->getById($question->getId());
        }
        $searchResults->setItems($questions);

        return $searchResults;
    }

    /**
     * Helper function that adds a FilterGroup to the collection.
     *
     * @param FilterGroup $filterGroup
     * @param Collection  $questionCollection
     *
     * @return void
     */
    private function addFilterGroupToCollection(FilterGroup $filterGroup, Collection $questionCollection)
    {
        foreach ($filterGroup->getFilters() as $filter) {
            $condition = $filter->getConditionType() ? $filter->getConditionType() : 'eq';
            $questionCollection->addFieldToFilter($filter->getField(), [$condition => $filter->getValue()]);
        }
    }

    /**
     * Helper function that adds a SortOrder to the collection.
     *
     * @param SortOrder[] $sortOrders
     * @param Collection  $questionCollection
     *
     * @return void
     */
    private function addOrderToCollection($sortOrders, Collection $questionCollection)
    {
        /** @var SortOrder $sortOrder */
        foreach ($sortOrders as $sortOrder) {
            $field = $sortOrder->getField();
            $questionCollection->addOrder(
                $field,
                ($sortOrder->getDirection() == SortOrder::SORT_DESC) ? 'DESC' : 'ASC'
            );
        }
    }

    /**
     * @param QuestionInterface $question
     *
     * @return QuestionInterface|mixed
     */
    private function prepareQuestionForSave(QuestionInterface $question)
    {
        if ($question->getQuestionId()) {
            $savedQuestion = $this->getById($question->getQuestionId());

            $changeStatus = $question->getAnswer()
                && !$savedQuestion->getAnswer()
                && $question->getStatus() == $savedQuestion->getStatus();

            $savedQuestion->addData($question->getData());

            if ($changeStatus) {
                $savedQuestion->setStatus(Status::STATUS_ANSWERED);
            }

            return $savedQuestion;
        }

        return $question;
    }
}
