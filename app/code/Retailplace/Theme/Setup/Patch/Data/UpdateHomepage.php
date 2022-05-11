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
use Magento\Cms\Helper\Page;
use Magento\Customer\Api\Data\GroupInterface;
use Magento\Customer\Api\GroupRepositoryInterface;
use Magento\Customer\Model\GroupManagement;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SearchCriteriaBuilderFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Psr\Log\LoggerInterface;

/**
 * Class UpdateHomepage
 */
class UpdateHomepage implements DataPatchInterface
{
    /** @var PageRepositoryInterface */
    private $pageRepository;

    /** @var BlockRepositoryInterface */
    private $blockRepository;

    /** @var BlockFactory */
    protected $blockFactory;

    /** @var GroupRepositoryInterface */
    private $groupRepository;

    /** @var SearchCriteriaBuilderFactory */
    private $searchCriteriaBuilderFactory;

    /** @var ScopeConfigInterface */
    private $scopeConfig;

    /** @var LoggerInterface */
    private $logger;

    /**
     * UpdateHomepage constructor.
     *
     * @param PageRepositoryInterface $pageRepository
     * @param BlockRepositoryInterface $blockRepository
     * @param BlockFactory $blockFactory
     * @param GroupRepositoryInterface $groupRepository
     * @param SearchCriteriaBuilderFactory $searchCriteriaBuilderFactory
     * @param ScopeConfigInterface $scopeConfig
     * @param LoggerInterface $logger
     */
    public function __construct(
        PageRepositoryInterface $pageRepository,
        BlockRepositoryInterface $blockRepository,
        BlockFactory $blockFactory,
        GroupRepositoryInterface $groupRepository,
        SearchCriteriaBuilderFactory $searchCriteriaBuilderFactory,
        ScopeConfigInterface $scopeConfig,
        LoggerInterface $logger
    ) {
        $this->pageRepository = $pageRepository;
        $this->blockRepository = $blockRepository;
        $this->blockFactory = $blockFactory;
        $this->groupRepository = $groupRepository;
        $this->searchCriteriaBuilderFactory = $searchCriteriaBuilderFactory;
        $this->scopeConfig = $scopeConfig;
        $this->logger = $logger;
    }

    /**
     * Apply Patch
     */
    public function apply()
    {
        $this->updateBlocks();
        $this->updateHomepage();
    }

    /**
     * Change CMS block type used in the homepage
     */
    private function updateHomepage()
    {
        $pageId = $this->scopeConfig->getValue(Page::XML_PATH_HOME_PAGE);
        try {
            $page = $this->pageRepository->getById($pageId);

            $newPageContent = <<<EOD
<div class="home-style home-page-1">
    {{block class="Magento\\Cms\\Block\\Block" block_id="guest_user_home_page"}}
    {{block class="Magento\\Cms\\Block\\Block" block_id="login_user_homepage"}}
    {{block class="Magento\\Cms\\Block\\Block" block_id="nlna_user_home_page"}}
</div>
EOD;
            $page->setContent($newPageContent);
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
        $block = $this->getBlock('guest_user_home_page');
        if ($block) {
            $block->setData('magefan_cms_display_rules', [
                'group_id' => GroupManagement::NOT_LOGGED_IN_ID
            ]);
            try {
                $this->blockRepository->save($block);
            } catch (Exception $e) {
                $this->logger->error($e->getMessage());
            }
        }

        $block = $this->getBlock('login_user_homepage');
        if ($block) {
            $groupIds = [];
            $groupCodes = ['General', 'Retailers', 'AU_Post'];
            foreach ($groupCodes as $groupCode) {
                if ($groupId = $this->getGroupIdByCode($groupCode)) {
                    $groupIds[] = $groupId;
                }
            }

            if ($groupIds) {
                $block->setData('magefan_cms_display_rules', [
                    'group_id' => $groupIds
                ]);
                try {
                    $this->blockRepository->save($block);
                } catch (Exception $e) {
                    $this->logger->error($e->getMessage());
                }
            }
        }

        /** @var BlockInterface $block */
        $block = $this->blockFactory->create();
        $block->setIdentifier('nlna_user_home_page');
        $block->setTitle('NLNA User home page');
        $block->setIsActive(true);
        $groupCode = 'NLNA';
        if ($groupId = $this->getGroupIdByCode($groupCode)) {
            $block->setData('magefan_cms_display_rules', [
                'group_id' => $groupId
            ]);

            try {
                $this->blockRepository->save($block);
            } catch (Exception $e) {
                $this->logger->error($e->getMessage());
            }
        }
    }

    /**
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
     * Get Customer Group ID by Code
     *
     * @param string $groupCode
     * @return int|null
     */
    private function getGroupIdByCode(string $groupCode): ?int
    {
        $groupId = null;

        /** @var SearchCriteriaBuilder $searchCriteriaBuilder */
        $searchCriteriaBuilder = $this->searchCriteriaBuilderFactory->create();
        $searchCriteria = $searchCriteriaBuilder
            ->addFilter(GroupInterface::CODE, $groupCode, 'eq')
            ->create();

        try {
            $groups = $this->groupRepository->getList($searchCriteria);
            foreach ($groups->getItems() as $group) {
                $groupId = (int) $group->getId();
            }
        } catch (Exception $e) {
            $this->logger->error($e->getMessage());
        }

        return $groupId;
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
