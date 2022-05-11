<?php

/**
 * Retailplace_Theme
 *
 * @copyright   Copyright (c) 2021 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Ruslan Mukhatov <ruslan@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\Theme\Setup\Patch\Data;

use Exception;
use Magento\Cms\Api\BlockRepositoryInterface;
use Magento\Cms\Api\Data\BlockInterface;
use Magento\Cms\Api\PageRepositoryInterface;
use Magento\Cms\Model\BlockFactory;
use Magento\Customer\Api\GroupRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SearchCriteriaBuilderFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Psr\Log\LoggerInterface;

/**
 * Class UpdateHomepage
 */
class UpdateCmsPage implements DataPatchInterface
{
    /**
     * @type array
     */
    const pages = [];

    /**
     * @type array
     */
    const blocks = [
        'hubspot' => [
            ['pattern' => '<img[^>]*?src=["\'].*?callback_icon_new.png.*?["\'].*?\/>', 'replacement' => '']
        ],
        'top-promotion-line' => [
            ['pattern' => '<img[^>]*?src=["\'].*?c_info_1.png.*?["\'].*?\/>', 'replacement' => '<div class="top-promotion-line-icon"></div>'],
        ],
        'product-details-id-1-block-1' => [
            ['pattern' => '<img[^>]*?src=["\'].*?price_guarantee_icon_1.png.*?["\'].*?\/>', 'replacement' => '<div class="price_guarantee_icon"></div>'],
            ['pattern' => '<img[^>]*?src=["\'].*?zip_logo_img.png.*?["\'].*?\/>', 'replacement' => '<div class="zip_logo_icon"></div>'],
            ['pattern' => 'top-promotion-line-icon', 'replacement' => 'price_guarantee_icon']
        ],
    ];

    /** @var PageRepositoryInterface */
    private $pageRepository;

    /** @var BlockRepositoryInterface */
    private $blockRepository;

    /** @var BlockFactory */
    protected $blockFactory;

    /** @var SearchCriteriaBuilderFactory */
    private $searchCriteriaBuilderFactory;

    /** @var LoggerInterface */
    private $logger;

    /**
     * UpdateHomepage constructor.
     *
     * @param PageRepositoryInterface $pageRepository
     * @param BlockRepositoryInterface $blockRepository
     * @param BlockFactory $blockFactory
     * @param SearchCriteriaBuilderFactory $searchCriteriaBuilderFactory
     * @param LoggerInterface $logger
     */
    public function __construct(
        PageRepositoryInterface $pageRepository,
        BlockRepositoryInterface $blockRepository,
        BlockFactory $blockFactory,
        SearchCriteriaBuilderFactory $searchCriteriaBuilderFactory,
        LoggerInterface $logger
    ) {
        $this->pageRepository = $pageRepository;
        $this->blockRepository = $blockRepository;
        $this->blockFactory = $blockFactory;
        $this->searchCriteriaBuilderFactory = $searchCriteriaBuilderFactory;
        $this->logger = $logger;
    }

    /**
     * Apply Patch
     */
    public function apply()
    {
        $this->updatePages();
        $this->updateBlocks();
    }

    /**
     * Update Pages
     */
    private function updatePages()
    {
        foreach (self::pages as $pageId => $conditions) {
            $this->updatePage($pageId, $conditions);
        }
    }

    /**
     * Change CMS block type used in the pages
     */
    private function updatePage($pageId, $conditions)
    {
        try {
            $page = $this->pageRepository->getById($pageId);
            if (empty($page)) {
                return;
            }
            $content = $page->getContent();
            if (empty($content)) {
                return;
            }
            foreach ($conditions as $condition) {
                $con = preg_replace('/' . $condition['pattern'] . '/is', $condition['replacement'], $content);
                if (empty($con)) {
                    continue;
                }
                $content = $con;
            }

            $page->setContent($content);
            $this->pageRepository->save($page);
        } catch (Exception $e) {
            $this->logger->error($e->getMessage());
        }
    }

    /**
     * Update CMS block visibility per customer group
     */
    private function updateBlocks()
    {
        foreach (self::blocks as $blockId => $conditions) {
            $this->updateBlock($blockId, $conditions);
        }
    }

    /**
     * Update Block
     *
     * @param $blockId
     * @param $conditions
     */
    private function updateBlock($blockId, $conditions)
    {
        try {
            $block = $this->getBlock($blockId);
            if (empty($block)) {
                return;
            }
            $content = $block->getContent();
            if (empty($content)) {
                return;
            }
            foreach ($conditions as $condition) {
                $con = preg_replace('/' . $condition['pattern'] . '/is', $condition['replacement'], $content);
                if (empty($con)) {
                    continue;
                }
                $content = $con;
            }

            $block->setContent($content);
            $this->blockRepository->save($block);
        } catch (Exception $e) {
            $this->logger->error($e->getMessage());
        }
    }

    /**
     * Get Block
     *
     * @param string $identifier
     * @return BlockInterface|null
     */
    private function getBlock(string $identifier): ?BlockInterface
    {
        /** @var SearchCriteriaBuilder $searchCriteriaBuilder */
        $searchCriteriaBuilder = $this->searchCriteriaBuilderFactory->create();
        $searchCriteria = $searchCriteriaBuilder
            ->addFilter(BlockInterface::IDENTIFIER, $identifier)
            ->create();
        try {
            $blocks = $this->blockRepository->getList($searchCriteria);
            foreach ($blocks->getItems() as $block) {
                return $block;
            }
        } catch (Exception $e) {
            $this->logger->error($e->getMessage());
        }
        return null;
    }

    /**
     * Get array of patches that have to be executed prior to this
     *
     * @return string[]
     */
    public static function getDependencies(): array
    {
        return [];
    }

    /**
     * Get aliases (previous names) for the patch
     *
     * @return string[]
     */
    public function getAliases(): array
    {
        return [];
    }
}
