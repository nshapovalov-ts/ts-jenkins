<?php

/**
 * Retailplace_MiraklFrontendDemo
 *
 * @copyright   Copyright (c) 2021 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Alexander Korsun <aleksandr@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\MiraklFrontendDemo\Model;

use Exception;
use Magento\Customer\Model\Session;
use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Api\SearchResults;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Session\SessionManagerInterface;
use Magento\Framework\Stdlib\Cookie\CookieMetadataFactory;
use Magento\Framework\Stdlib\Cookie\CookieSizeLimitReachedException;
use Magento\Framework\Stdlib\Cookie\FailureToSendException;
use Magento\Framework\Stdlib\CookieManagerInterface;
use Magento\Framework\View\LayoutInterfaceFactory;
use Psr\Log\LoggerInterface;
use Retailplace\MiraklFrontendDemo\Api\Data\MessagesResponseInterface;
use Retailplace\MiraklFrontendDemo\Api\Data\MessagesResponseInterfaceFactory;
use Retailplace\MiraklFrontendDemo\Api\Data\MessagesStatsSearchResultsInterfaceFactory;
use Retailplace\MiraklFrontendDemo\Api\MessagesStatsRepositoryInterface;
use Retailplace\MiraklFrontendDemo\Model\ResourceModel\MessagesStats\Collection;
use Retailplace\MiraklFrontendDemo\Model\ResourceModel\MessagesStats\CollectionFactory;
use Mirakl\Connector\Model\Offer as OfferModel;
use Mirakl\Api\Helper\Message as MessageApi;
use Zend_Db_Expr;
use Mirakl\MMP\FrontOperator\Domain\Order as DomainOrder;
use Magento\Framework\Api\SortOrderBuilder;
use Magento\Framework\Api\Search\FilterGroupBuilder;

/**
 * MessagesStatsRepository Class
 */
class MessagesStatsRepository implements MessagesStatsRepositoryInterface
{
    /**
     * Name of Cookie
     */
    const COOKIE_NAME = '_nmc';

    /**
     * Cookie life time
     */
    const COOKIE_LIFE = 60;

    protected $modelFactory = null;

    protected $collectionFactory = null;
    /**
     * @var MessagesStatsSearchResultsInterfaceFactory
     */
    private $searchResultFactory;
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
     * @var LayoutInterfaceFactory
     */
    private $layout;
    /**
     * @var Session
     */
    private $customerSession;
    /**
     * @var LoggerInterface
     */
    private $logger;
    /**
     * @var CookieManagerInterface
     */
    private $cookieManager;
    /**
     * @var CookieMetadataFactory
     */
    private $cookieMetadataFactory;
    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfigInterface;
    /**
     * @var SessionManagerInterface
     */
    private $sessionManager;
    private $info;
    /**
     * @var MessageApi
     */
    private $messageApi;
    /**
     * @var SortOrderBuilder
     */
    private $sortOrderBuilder;
    /**
     * @var FilterGroupBuilder
     */
    private $filterGroupBuilder;

    /**
     * initialize
     *
     * @param MessagesStatsFactory $modelFactory
     * @param CollectionFactory $collectionFactory
     * @param MessagesStatsSearchResultsInterfaceFactory $searchResultInterfaceFactory
     * @param CollectionProcessorInterface $collectionProcessor
     * @param SortOrderBuilder $sortOrderBuilder
     * @param FilterBuilder $filterBuilder
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param MessagesResponseInterfaceFactory $messagesResponse
     * @param LayoutInterfaceFactory $layout
     * @param Session $customerSession
     * @param LoggerInterface $logger
     * @param ScopeConfigInterface $scopeConfigInterface
     * @param CookieManagerInterface $cookieManager
     * @param CookieMetadataFactory $cookieMetadataFactory
     * @param SessionManagerInterface $sessionManager
     * @param MessageApi $messageApi
     */
    public function __construct(
        MessagesStatsFactory $modelFactory,
        CollectionFactory $collectionFactory,
        MessagesStatsSearchResultsInterfaceFactory $searchResultInterfaceFactory,
        CollectionProcessorInterface $collectionProcessor,
        SortOrderBuilder $sortOrderBuilder,
        FilterBuilder $filterBuilder,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        FilterGroupBuilder $filterGroupBuilder,
        MessagesResponseInterfaceFactory $messagesResponse,
        LayoutInterfaceFactory $layout,
        Session $customerSession,
        LoggerInterface $logger,
        ScopeConfigInterface $scopeConfigInterface,
        CookieManagerInterface $cookieManager,
        CookieMetadataFactory $cookieMetadataFactory,
        SessionManagerInterface $sessionManager,
        MessageApi $messageApi
    ) {
        $this->modelFactory = $modelFactory;
        $this->collectionFactory = $collectionFactory;
        $this->searchResultFactory = $searchResultInterfaceFactory;
        $this->collectionProcessor = $collectionProcessor;
        $this->sortOrderBuilder = $sortOrderBuilder;
        $this->filterBuilder = $filterBuilder;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->messagesResponse = $messagesResponse;
        $this->customerSession = $customerSession;
        $this->layout = $layout;
        $this->logger = $logger;
        $this->scopeConfigInterface = $scopeConfigInterface;
        $this->cookieManager = $cookieManager;
        $this->cookieMetadataFactory = $cookieMetadataFactory;
        $this->sessionManager = $sessionManager;
        $this->messageApi = $messageApi;
        $this->filterGroupBuilder = $filterGroupBuilder;
    }

    /**
     * get by id
     *
     * @param int $id
     * @return MessagesStats
     * @throws NoSuchEntityException
     */
    public function getById($id): MessagesStats
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
     * @param MessagesStats $subject
     * @return MessagesStats
     * @throws CouldNotSaveException
     */
    public function save(MessagesStats $subject)
    {
        try {
            $subject->save();
        } catch (Exception $exception) {
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
    public function getList(SearchCriteriaInterface $criteria): SearchResults
    {
        $collection = $this->collectionFactory->create();
        $this->collectionProcessor->process($criteria, $collection);
        $searchResults = $this->searchResultFactory->create();

        $searchResults->setSearchCriteria($criteria);
        $searchResults->setItems($collection->getItems());

        return $searchResults;
    }

    /**
     * Get Collection
     *
     * @return Collection
     */
    public function getCollection(): Collection
    {
        return $this->collectionFactory->create();
    }

    /**
     * Delete
     *
     * @param MessagesStats $subject
     * @return boolean
     * @throws CouldNotDeleteException
     */
    public function delete(MessagesStats $subject)
    {
        try {
            $subject->delete();
        } catch (Exception $exception) {
            throw new CouldNotDeleteException(__($exception->getMessage()));
        }
        return true;
    }

    /**
     * Delete by id
     *
     * @param int $id
     * @return boolean
     * @throws CouldNotDeleteException
     * @throws NoSuchEntityException
     */
    public function deleteById(int $id): bool
    {
        return $this->delete($this->getById($id));
    }

    /**
     * @param $customerId
     * @param $entityId
     * @param $type
     * @return mixed|void
     */
    public function getThreadByCustomerIdAndEntityId($customerId, $entityId, $type)
    {
        $thread = null;

        //load existing records
        // prepare filters
        $filters = [
            $this->filterBuilder->setField('customer_id')
                ->setConditionType('eq')
                ->setValue($customerId)
                ->create(),
            $this->filterBuilder->setField('entity_id')
                ->setConditionType('eq')
                ->setValue($entityId)
                ->create(),
            $this->filterBuilder->setField('type')
                ->setConditionType('eq')
                ->setValue($type)
                ->create(),
        ];

        $filterGroups = [];

        foreach ($filters as $filter) {
            $filterGroups[] = $this->filterGroupBuilder
                ->addFilter($filter)
                ->create();
        }

        $sortOrder = $this->sortOrderBuilder->setField('updated_at')->setDirection('DESC')->create();

        // create search criteria
        $searchCriteria = $this->searchCriteriaBuilder
            ->setFilterGroups($filterGroups)
            ->setSortOrders([$sortOrder])
            ->setPageSize(1)
            ->setCurrentPage(1)
            ->create();

        foreach ($this->getList($searchCriteria)->getItems() as $item) {
            $thread = $item->getThreadId();
            break;
        }

        return $thread;
    }

    /**
     * @param $threads
     * @return mixed|void
     * @throws CouldNotSaveException
     */
    public function updateThreads($threads)
    {
        $threadIds = [];
        $allThread = [];

        $customerId = $this->customerSession->getCustomerId();

        foreach ($threads->getCollection() as $thread) {
            $threadIds[] = $thread->getId();
            $allThread[$thread->getId()] = $thread;
        }

        //load existing records
        // prepare filters
        $filters = [
            $this->filterBuilder->setField('thread_id')
                ->setConditionType('in')
                ->setValue($threadIds)
                ->create()
        ];
        // create search criteria
        $searchCriteria = $this->searchCriteriaBuilder->addFilters($filters)->create();

        $existingThreadIds = [];

        foreach ($this->getList($searchCriteria)->getItems() as $item) {
            $threadId = $item->getThreadId();
            $existingThreadIds[$threadId] = $threadId;
            //update
            if (!empty($allThread[$threadId])) {
                $totalCount = $allThread[$threadId]->getMetadata()->getTotalCount();
                if ($item->getTotalCount() == $totalCount) {
                    unset($allThread[$threadId]);
                    continue;
                }

                $item->setTotalCount($totalCount);
                $item->unsetData('updated_at');
                $this->save($item);
                unset($allThread[$threadId]);
            }
        }

        //create new
        foreach ($allThread as $item) {
            if (array_key_exists($item->getId(), $existingThreadIds)) {
                continue;
            }
            $itemArray = $item->toArray();

            $type = 'EMPTY';
            $entityId = 'EMPTY';

            foreach ($itemArray['entities'] as $entity) {
                $type = $entity['type'];
                $entityId = $entity['id'];
            }

            $model = $this->modelFactory->create();
            $model->setCustomerId($customerId);
            $model->setThreadId($item->getId());
            $model->setEntityId($entityId);
            $model->setType($type);
            $model->setTotalCount($item->getMetadata()->getTotalCount());
            $this->save($model);
        }
    }

    /**
     * @param int $customerId
     * @return MessagesResponseInterface
     * @throws Exception
     */
    public function getNewMessagesCount(int $customerId): MessagesResponseInterface
    {
        $this->customerSession->setCustomerId((string) $customerId);

        $response = $this->messagesResponse->create();
        $newMessagesCount = 0;

        $threads = $this->messageApi->getThreads($customerId, null, null, 50);
        if (!empty($threads)) {
            $threadInfo = $this->getThreadInfo();
            foreach ($threadInfo as $item) {
                $newMessagesCount += $item['unread_messages'];
            }
        }

        $response->setNewMessagesCount($newMessagesCount);

        return $response;
    }

    /**
     * @param string $customerId
     * @param $model
     * @return void
     */
    public function getAllMySentMessages(string $customerId, $model)
    {
        $this->customerSession->setCustomerId($customerId);

        if (empty($model) || !$model->getId()) {
            $this->logger->error("getAllMySentMessages - model is empty");
            return;
        }

        $type = null;

        if ($model instanceof OfferModel) {
            $type = 'MMP_OFFER';
        } elseif ($model instanceof DomainOrder) {
            $type = 'MMP_ORDER';
        }

        if (empty($type)) {
            $this->logger->error("getAllMySentMessages - unknown type");
            return;
        }

        $this->messageApi->getThreads($customerId, $type, null, 10);
        $threadId = $this->getThreadByCustomerIdAndEntityId($customerId, $model->getId(), $type);

        if (!empty($threadId)) {
            //set to session flag
            $this->customerSession->setData('update_only_sent_client_messages', true);
            $this->messageApi->getThreadDetails($threadId, $customerId);
        }
    }

    /**
     * @return array
     * @throws Exception
     */
    public function getThreadInfo(): array
    {
        $info = [];

        $customerId = $this->customerSession->getCustomerId();

        if (empty($customerId)) {
            return $info;
        }

        try {
            // prepare filters
            $filters = [
                $this->filterBuilder->setField('main_table.customer_id')
                    ->setConditionType('in')
                    ->setValue($customerId)
                    ->create()
            ];
            // create search criteria
            $searchCriteria = $this->searchCriteriaBuilder->addFilters($filters)->create();

            $collection = $this->collectionFactory->create();
            $this->collectionProcessor->process($searchCriteria, $collection);

            $select = $collection->getSelect();

            $select->joinLeft(['rm' => $collection->getTable('retailplace_messages')], 'rm.thread_id = main_table.thread_id', []);

            $select->columns([
                'thread_id' => 'main_table.thread_id',
                'unread_messages' => new Zend_Db_Expr('main_table.total_count - COUNT(rm.id)'),
                'is_attachment' => new Zend_Db_Expr('IFNULL(MAX(rm.is_attachment), 0)'),
            ]);

            $select->group('main_table.thread_id');

            foreach ($collection as $item) {
                $info[$item['thread_id']] = $item;
            }
        } catch (Exception $exception) {
            $this->logger->error($exception->getMessage());
        }

        $this->info = $info;

        return $info;
    }

    /**
     * Update Message Counter - Update Cookie
     *
     * @throws Exception
     */
    public function updateMessageCounter()
    {
        $newMessagesCount = 0;
        $threadInfo = $this->getThreadInfo();
        foreach ($threadInfo as $item) {
            $newMessagesCount += $item['unread_messages'];
        }

        $metadata = $this->cookieMetadataFactory
            ->createPublicCookieMetadata()
            ->setDuration($this->getMessageNotificationInterval() * self::COOKIE_LIFE)
            ->setPath($this->sessionManager->getCookiePath())
            ->setDomain($this->sessionManager->getCookieDomain());

        try {
            $this->customerSession->setData('customer_new_message_counter', $newMessagesCount);
            $this->cookieManager->setPublicCookie(self::COOKIE_NAME, $newMessagesCount, $metadata);
        } catch (InputException | FailureToSendException | CookieSizeLimitReachedException $e) {
            $this->logger->error($e->getMessage());
        }
    }

    /**
     * @return int
     */
    public function getMessageNotificationInterval()
    {
        return 15; //todo load from config
    }
}
