<?php

/**
 * Retailplace_MiraklFrontendDemo
 *
 * @copyright   Copyright (c) 2021 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Alexander Korsun <aleksandr@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\MiraklFrontendDemo\Model;

use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Api\SearchResults;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Mirakl\MMP\Common\Domain\Message\Thread\ThreadDetails;
use Retailplace\MiraklFrontendDemo\Api\Data\MessagesSearchResultsInterfaceFactory;
use Retailplace\MiraklFrontendDemo\Api\MessagesRepositoryInterface;
use Retailplace\MiraklFrontendDemo\Model\ResourceModel\Messages\CollectionFactory;
use Magento\Customer\Model\Session;

/**
 * MessagesRepository Class
 */
class MessagesRepository implements MessagesRepositoryInterface
{
    protected $modelFactory = null;

    protected $collectionFactory = null;
    /**
     * @var MessagesSearchResultsInterfaceFactory
     */
    private $searchResultFactory;
    /**
     * @var CollectionProcessorInterface
     */
    private $collectionProcessor;
    /**
     * @var FilterBuilder
     */
    private $filterBuilder;
    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;
    /**
     * @var MessagesResponseFactory
     */
    private $messagesResponse;
    /**
     * @var Session
     */
    private $customerSession;

    /**
     * initialize
     *
     * @param MessagesFactory $modelFactory
     * @param CollectionFactory $collectionFactory
     * @param MessagesSearchResultsInterfaceFactory $searchResultInterfaceFactory
     * @param CollectionProcessorInterface $collectionProcessor
     * @param FilterBuilder $filterBuilder
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param Session $customerSession
     */
    public function __construct(
        MessagesFactory $modelFactory,
        CollectionFactory $collectionFactory,
        MessagesSearchResultsInterfaceFactory $searchResultInterfaceFactory,
        CollectionProcessorInterface $collectionProcessor,
        FilterBuilder $filterBuilder,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        Session $customerSession
    ) {
        $this->modelFactory = $modelFactory;
        $this->collectionFactory = $collectionFactory;
        $this->searchResultFactory = $searchResultInterfaceFactory;
        $this->collectionProcessor = $collectionProcessor;
        $this->filterBuilder = $filterBuilder;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->customerSession = $customerSession;
    }

    /**
     * get by id
     *
     * @param int $id
     * @return Messages
     * @throws NoSuchEntityException
     */
    public function getById($id)
    {
        $model = $this->modelFactory->create()->load($id);
        if (!$model->getId()) {
            throw new NoSuchEntityException(__('The CMS block with the "%1" ID doesn\'t exist.', $id));
        }
        return $model;
    }

    /**
     * get by id
     *
     * @param Messages $subject
     * @return Messages
     * @throws CouldNotSaveException
     */
    public function save(Messages $subject)
    {
        try {
            $subject->save();
        } catch (\Exception $exception) {
            throw new CouldNotSaveException(__($exception->getMessage()));
        }
        return $subject;
    }

    /**
     * get list
     *
     * @param SearchCriteriaInterface $criteria
     * @return SearchResults
     */
    public function getList(SearchCriteriaInterface $criteria)
    {
        $collection = $this->collectionFactory->create();
        $this->collectionProcessor->process($criteria, $collection);
        $searchResults = $this->searchResultFactory->create();

        $searchResults->setSearchCriteria($criteria);
        $searchResults->setItems($collection->getItems());

        return $searchResults;
    }

    /**
     * delete
     *
     * @param Messages $subject
     * @return boolean
     * @throws CouldNotDeleteException
     */
    public function delete(Messages $subject)
    {
        try {
            $subject->delete();
        } catch (\Exception $exception) {
            throw new CouldNotDeleteException(__($exception->getMessage()));
        }
        return true;
    }

    /**
     * delete by id
     *
     * @param int $id
     * @return boolean
     * @throws CouldNotDeleteException
     * @throws NoSuchEntityException
     */
    public function deleteById($id)
    {
        return $this->delete($this->getById($id));
    }

    /**
     * @param ThreadDetails $thread
     * @return mixed|void
     * @throws CouldNotSaveException
     */
    public function updateMessages($thread)
    {
        $messagesIds = [];
        $allMessages = [];

        foreach ($thread->getMessages() as $message) {
            $messagesIds[] = $message->getId();
            $allMessages[$message->getId()] = $message;
        }

        //load existing records
        // prepare filters
        $filters = [
            $this->filterBuilder->setField('message_id')
                ->setConditionType('in')
                ->setValue($messagesIds)
                ->create()
        ];
        // create search criteria
        $searchCriteria = $this->searchCriteriaBuilder->addFilters($filters)->create();

        $existingMessageIds = [];

        foreach ($this->getList($searchCriteria)->getItems() as $item) {
            $messageId = $item->getMessageId();
            $existingMessageIds[$messageId] = $messageId;
        }

        $flag = $this->customerSession->getData('update_only_sent_client_messages');
        if ($flag) {
            $this->customerSession->unsetData('update_only_sent_client_messages');
        }

        foreach ($allMessages as $item) {
            if (array_key_exists($item->getId(), $existingMessageIds)) {
                continue;
            }

            $type = $item->getFrom()->getType();

            if ($flag && $type != "CUSTOMER_USER") {
                continue;
            }

            //create new
            $model = $this->modelFactory->create();
            $model->setMessageId($item->getId());
            $model->setThreadId($thread->getId());
            $attachments = $item->getAttachments();
            $model->setIsAttachment((int)!empty($attachments));
            $model->setType($type);
            $this->save($model);
        }
    }
}
