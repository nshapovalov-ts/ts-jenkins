<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */
namespace Magefan\CmsDisplayRules\Model;

use Magefan\CmsDisplayRules\Model\BlockFactory;
use Magefan\CmsDisplayRules\Model\ResourceModel\Block as BlockResourceModel;
use Magefan\CmsDisplayRules\Model\ResourceModel\Block\CollectionFactory;
use Magento\Cms\Api\BlockRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Api\SearchResultsFactory;
use Magento\Cms\Api\Data\BlockInterface;
use Magento\Framework\DB\Adapter\ConnectionException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\ValidatorException;
use Magento\Framework\Exception\StateException;
use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magefan\CmsDisplayRules\Model\Validator;

/**
 * Class BlockRepository repository
 */
class BlockRepository implements BlockRepositoryInterface
{
    /**
     * @var BlockFactory
     */
    private $blockFactory;
    /**
     * @var BlockResourceModel
     */
    private $blockResourceModel;
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
     * BlockRepository constructor.
     * @param \Magefan\CmsDisplayRules\Model\BlockFactory $blockFactory
     * @param BlockResourceModel $blockResourceModel
     * @param CollectionFactory $collectionFactory
     * @param SearchResultsFactory $searchResultsFactory
     * @param CollectionProcessorInterface|null $collectionProcessor
     */
    public function __construct(
        BlockFactory $blockFactory,
        BlockResourceModel $blockResourceModel,
        CollectionFactory $collectionFactory,
        SearchResultsFactory $searchResultsFactory,
        CollectionProcessorInterface $collectionProcessor = null
    ) {
        $this->blockFactory = $blockFactory;
        $this->blockResourceModel = $blockResourceModel;
        $this->collectionFactory = $collectionFactory;
        $this->searchResultsFactory = $searchResultsFactory;
        $this->collectionProcessor = $collectionProcessor ?: \Magento\Framework\App\ObjectManager::getInstance()->get(
            \Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface::class
        );
    }

    /**
     * @param int $blockId
     * @return Block|BlockInterface
     * @throws NoSuchEntityException
     */
    public function getById($blockId)
    {
        $block = $this->blockFactory->create();
        $this->blockResourceModel->load($block, $blockId);
        if (!$block->getId()) {
            if (!$blockId) {
                throw new NoSuchEntityException(__('Requested item doesn\'t exist'));
            }
            $block->setId($blockId);
        }
        return $block;
    }

    /**
     * @param SearchCriteriaInterface $searchCriteria
     * @return \Magento\Cms\Api\Data\BlockSearchResultsInterface|\Magento\Framework\Api\searchResultsInterface
     */
    public function getList(SearchCriteriaInterface $searchCriteria)
    {
        /** @var \Magefan\Blog\Model\ResourceModel\Block\Collection $collection */
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
     * @param BlockInterface $block
     * @return bool|Block|BlockInterface
     * @throws CouldNotSaveException
     * @throws NoSuchEntityException
     * @throws \Magento\Framework\Exception\AlreadyExistsException
     */
    public function save(BlockInterface $block)
    {

        if ($block) {
            $blockRule = $this->getById($block->getId());
            try {
                $blockRule->setData('rule', $block->getData('rule'));
                $blockRule->setData('magefan_cms_display_rules', $block->getData('magefan_cms_display_rules'));
                $this->blockResourceModel->save($blockRule);
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
     * @param BlockInterface $block
     * @return bool
     * @throws CouldNotDeleteException
     * @throws NoSuchEntityException
     * @throws StateException
     */
    public function delete(BlockInterface $block)
    {
        if ($block) {
            $blockRule = $this->getById($block->getId());
            try {
                $this->blockResourceModel->delete($blockRule);
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
     * @param int $blockId
     * @return bool
     * @throws CouldNotDeleteException
     * @throws NoSuchEntityException
     * @throws StateException
     */
    public function deleteById($blockId)
    {
        return $this->delete($this->getById($blockId));
    }
}
