<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */
namespace Magefan\CmsDisplayRules\Model;

use Magefan\CmsDisplayRules\Model\PageFactory;
use Magefan\CmsDisplayRules\Model\ResourceModel\Page as PageResourceModel;
use Magefan\CmsDisplayRules\Model\ResourceModel\Page\CollectionFactory;
use Magento\Cms\Api\PageRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Api\SearchResultsFactory;
use Magento\Cms\Api\Data\PageInterface;
use Magento\Framework\DB\Adapter\ConnectionException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\ValidatorException;
use Magento\Framework\Exception\StateException;
use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Cms\Model\PageRepository as CmsPageRepository;

/**
 * Class PageRepository repository
 */
class PageRepository implements PageRepositoryInterface
{

    /**
     * @var PageFactory
     */
    private $pageFactory;
    /**
     * @var PageResourceModel
     */
    private $pageResourceModel;
    /**
     * @var CollectionFactory
     */
    private $collectionFactory;
    /**
     * @var SearchResultsFactory
     */
    private $searchResultsFactory;
    /**
     * @var CollectionProcessorInterface
     */
    private $collectionProcessor;

    /**
     * @var CmsPageRepository
     */
    private $cmsPageRepository;

    /**
     * PageRepository constructor.
     * @param \Magefan\CmsDisplayRules\Model\PageFactory $pageFactory
     * @param PageResourceModel $pageResourceModel
     * @param CollectionFactory $collectionFactory
     * @param SearchResultsFactory $searchResultsFactory
     * @param \Magefan\CmsDisplayRules\Model\Validator $validator
     * @param CmsPageRepository $cmsPageRepository
     * @param CollectionProcessorInterface|null $collectionProcessor
     */
    public function __construct(
        PageFactory $pageFactory,
        PageResourceModel $pageResourceModel,
        CollectionFactory $collectionFactory,
        SearchResultsFactory $searchResultsFactory,
        CmsPageRepository $cmsPageRepository,
        CollectionProcessorInterface $collectionProcessor = null
    ) {
        $this->pageFactory = $pageFactory;
        $this->pageResourceModel = $pageResourceModel;
        $this->collectionFactory = $collectionFactory;
        $this->searchResultsFactory = $searchResultsFactory;
        $this->cmsPageRepository = $cmsPageRepository;
        $this->collectionProcessor = $collectionProcessor ?: \Magento\Framework\App\ObjectManager::getInstance()->get(
            \Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface::class
        );
    }

    /**
     * @param int $pageId
     * @return Page|PageInterface
     * @throws NoSuchEntityException
     */
    public function getById($pageId)
    {
        $page = $this->pageFactory->create();
        $this->pageResourceModel->load($page, $pageId);
        if (!$page->getId()) {
            if (!$pageId) {
                throw new NoSuchEntityException(__('Requested item doesn\'t exist'));
            }
            $page->setId($pageId);
        }
        return $page;
    }

    /**
     * @param SearchCriteriaInterface $searchCriteria
     * @return \Magento\Cms\Api\Data\PageSearchResultsInterface|\Magento\Framework\Api\searchResultsInterface
     */
    public function getList(SearchCriteriaInterface $searchCriteria)
    {
        /** @var \Magefan\CmsDispplayRules\Model\ResourceModel\Page\Collection $collection */
        $collection = $this->collectionFactory->create();
        $this->collectionProcessor->process($searchCriteria, $collection);
        /** @var \Magento\Framework\Api\searchResultsInterface $searchResult */
        $searchResult = $this->searchResultsFactory->create();
        $searchResult->setSearchCriteria($searchCriteria);
        $searchResult->setTotalCount($collection->getSize());
        $searchResult->setItems($collection->getData());
        return $searchResult;
    }

    /**
     * @param PageInterface $page
     * @return bool|PageInterface
     * @throws CouldNotSaveException
     * @throws NoSuchEntityException
     * @throws \Magento\Framework\Exception\AlreadyExistsException
     */
    public function save(PageInterface $page)
    {
        if ($page) {
            $pageRule = $this->getById($page->getId());
            try {
                $pageRule->setData('rule', $page->getData('rule'));
                $pageRule->setData('magefan_cms_display_rules', $page->getData('magefan_cms_display_rules'));
                $this->pageResourceModel->save($pageRule);
            } catch (ConnectionException $exception) {
                throw new CouldNotSaveException(
                    __('Database connection error'),
                    $exception,
                    $exception->getCode()
                );
            } catch (CouldNotSaveException $e) {
                throw new CouldNotSaveException(__('Unable to save item'), $e);
            } catch (ValidatorException $e) {
                throw new CouldNotSaveException(__($e->getMessage()));
            }
        }
        return false;
    }

    /**
     * @param PageInterface $page
     * @return bool
     * @throws CouldNotDeleteException
     * @throws NoSuchEntityException
     * @throws StateException
     */
    public function delete(PageInterface $page)
    {
        if ($page) {
            $pageRule = $this->getById($page->getId());
            try {
                $this->pageResourceModel->delete($pageRule);
            } catch (ValidatorException $e) {
                throw new CouldNotDeleteException(__($e->getMessage()));
            } catch (\Exception $e) {
                throw new StateException(
                    __('Unable to remove item')
                );
            }
            return true;
        }
    }

    /**
     * @param int $pageId
     * @return bool
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function deleteById($pageId)
    {
        return $this->delete($this->getById($pageId));
    }
}
