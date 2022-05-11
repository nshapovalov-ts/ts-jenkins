<?php

/**
 * Retailplace_AuPost
 *
 * @copyright   Copyright (c) 2021 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Dmitriy Fionov <dmitriy@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\AuPost\Setup\Patch\Data;

use Exception;
use Magento\Cms\Api\Data\PageInterface;
use Magento\Cms\Api\PageRepositoryInterface;
use Magento\Customer\Api\Data\GroupInterface;
use Magento\Customer\Api\GroupRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilderFactory;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Psr\Log\LoggerInterface;
use Retailplace\ChannelPricing\Model\GroupProcessor\AuPost;
use Sm\MegaMenu\Api\Data\MenuGroupInterface;
use Sm\MegaMenu\Api\Data\MenuGroupInterfaceFactory;
use Sm\MegaMenu\Api\Data\MenuItemsInterface;
use Sm\MegaMenu\Api\Data\MenuItemsInterfaceFactory;
use Sm\MegaMenu\Model\Config\Source\LinkTargets;
use Sm\MegaMenu\Model\Config\Source\ListNumCol;
use Sm\MegaMenu\Model\Config\Source\Type;
use Sm\MegaMenu\Model\ResourceModel\MenuItems as MenuItemsResourceModel;
use Sm\MegaMenu\Model\ResourceModel\MenuGroup as MenuGroupResourceModel;

/**
 * Class AddAuPostMenuItem
 */
class AddAuPostMenuItem implements DataPatchInterface
{
    /** @var string */
    public const MEGAMENU_ITEM_CUSTOMER_GROUP_ID = 'customer_group_id';
    public const MEGAMENU_HORIZONTAL_MENU_GROUP_TITLE = 'Mega Menu Horizontal';
    public const AU_POST_TOP_ITEM_CLASS = 'agha_dropdown_main_bg ';
    public const AU_POST_TOP_ITEM_TITLE = 'Australia Post';
    public const AU_POST_LANDING_ITEM_TITLE = 'Australia Post Marketplace';
    public const AU_POST_SELLERS_ITEM_TITLE = 'Wholesaler Directory';
    public const AU_POST_SELLERS_URL = 'au-post';

    /** @var \Magento\Customer\Api\GroupRepositoryInterface */
    private $groupRepository;

    /** @var \Magento\Framework\Api\SearchCriteriaBuilderFactory */
    private $searchCriteriaBuilderFactory;

    /** @var \Sm\MegaMenu\Model\ResourceModel\MenuItems */
    private $menuItemsResourceModel;

    /** @var \Sm\MegaMenu\Api\Data\MenuItemsInterfaceFactory */
    private $menuItemsFactory;

    /** @var \Sm\MegaMenu\Model\ResourceModel\MenuGroup */
    private $menuGroupResourceModel;

    /** @var \Sm\MegaMenu\Api\Data\MenuGroupInterfaceFactory */
    private $menuGroupFactory;

    /** @var \Magento\Cms\Api\PageRepositoryInterface */
    private $pageRepository;

    /** @var \Psr\Log\LoggerInterface */
    private $logger;

    /**
     * AddAuPostMenuItem constructor.
     *
     * @param \Magento\Customer\Api\GroupRepositoryInterface $groupRepository
     * @param \Magento\Framework\Api\SearchCriteriaBuilderFactory $searchCriteriaBuilderFactory
     * @param \Sm\MegaMenu\Model\ResourceModel\MenuItems $menuItemsResourceModel
     * @param \Sm\MegaMenu\Api\Data\MenuItemsInterfaceFactory $menuItemsFactory
     * @param \Sm\MegaMenu\Model\ResourceModel\MenuGroup $menuGroupResourceModel
     * @param \Sm\MegaMenu\Api\Data\MenuGroupInterfaceFactory $menuGroupFactory
     * @param \Magento\Cms\Api\PageRepositoryInterface $pageRepository
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        GroupRepositoryInterface $groupRepository,
        SearchCriteriaBuilderFactory $searchCriteriaBuilderFactory,
        MenuItemsResourceModel $menuItemsResourceModel,
        MenuItemsInterfaceFactory $menuItemsFactory,
        MenuGroupResourceModel $menuGroupResourceModel,
        MenuGroupInterfaceFactory $menuGroupFactory,
        PageRepositoryInterface $pageRepository,
        LoggerInterface $logger
    ) {
        $this->groupRepository = $groupRepository;
        $this->searchCriteriaBuilderFactory = $searchCriteriaBuilderFactory;
        $this->menuItemsResourceModel = $menuItemsResourceModel;
        $this->menuItemsFactory = $menuItemsFactory;
        $this->menuGroupResourceModel = $menuGroupResourceModel;
        $this->menuGroupFactory = $menuGroupFactory;
        $this->pageRepository = $pageRepository;
        $this->logger = $logger;
    }

    /**
     * Apply patch
     */
    public function apply()
    {
        $this->addMenuItems();
    }

    /**
     * Get array of patches that have to be executed prior to this.
     *
     * @return string[]
     */
    public static function getDependencies(): array
    {
        return [
            AddAuPostLandingPage::class
        ];
    }

    /**
     * Get aliases (previous names) for the patch.
     *
     * @return string[]
     */
    public function getAliases(): array
    {
        return [];
    }

    /**
     * Add AU Post Menu Items
     */
    private function addMenuItems()
    {
        try {
            $topItemId = $this->addTopMenuItem();
            $this->addLandingMenuItem($topItemId);
            $this->addSellerMenuItem($topItemId);
        } catch (Exception $e) {
            $this->logger->error($e->getMessage());
        }
    }

    /**
     * Add Top level AU Post Menu Item
     *
     * @return int
     * @throws \Magento\Framework\Exception\AlreadyExistsException
     */
    private function addTopMenuItem(): int
    {
        /** @var \Sm\MegaMenu\Api\Data\MenuItemsInterface|\Sm\MegaMenu\Model\MenuItems $auPostMenuItem */
        $auPostMenuItem = $this->menuItemsFactory->create();
        $auPostMenuItem->setTitle(self::AU_POST_TOP_ITEM_TITLE);
        $auPostMenuItem->setCustomClass(self::AU_POST_TOP_ITEM_CLASS);
        $auPostMenuItem->setGroupId($this->getMenuGroupIdByTitle(self::MEGAMENU_HORIZONTAL_MENU_GROUP_TITLE));
        $auPostMenuItem->setParentId($this->getRootIdForGroup(self::MEGAMENU_HORIZONTAL_MENU_GROUP_TITLE));
        $auPostMenuItem->setType(Type::NORMAL);
        $auPostMenuItem->setDataType('');
        $auPostMenuItem->setDepth(1);
        $auPostMenuItem->setPriorities(8);
        $auPostMenuItem->setColsNb(ListNumCol::TWO);
        $auPostMenuItem->setData(
            self::MEGAMENU_ITEM_CUSTOMER_GROUP_ID,
            $this->getGroupIdByCode(AuPost::GROUP_CODE)
        );

        $this->menuItemsResourceModel->save($auPostMenuItem);

        return (int) $auPostMenuItem->getItemsId();
    }

    /**
     * Add AU Post Landing Menu Item
     *
     * @param int $parentId
     * @throws \Magento\Framework\Exception\AlreadyExistsException
     */
    private function addLandingMenuItem(int $parentId)
    {
        /** @var \Sm\MegaMenu\Api\Data\MenuItemsInterface|\Sm\MegaMenu\Model\MenuItems $auPostMenuItem */
        $auPostMenuItem = $this->menuItemsFactory->create();
        $auPostMenuItem->setTitle(self::AU_POST_LANDING_ITEM_TITLE);
        $auPostMenuItem->setGroupId($this->getMenuGroupIdByTitle(self::MEGAMENU_HORIZONTAL_MENU_GROUP_TITLE));
        $auPostMenuItem->setParentId($parentId);
        $auPostMenuItem->setType(Type::CMSPAGE);
        $auPostMenuItem->setDataType($this->getAuPostPageId());
        $auPostMenuItem->setDepth(2);
        $auPostMenuItem->setColsNb(ListNumCol::SIX);
        $auPostMenuItem->setTarget(LinkTargets::_SELF);
        $auPostMenuItem->setData(
            self::MEGAMENU_ITEM_CUSTOMER_GROUP_ID,
            $this->getGroupIdByCode(AuPost::GROUP_CODE)
        );

        $this->menuItemsResourceModel->save($auPostMenuItem);
    }

    /**
     * Add AU Post Seller Page Menu Item
     *
     * @param int $parentId
     * @throws \Magento\Framework\Exception\AlreadyExistsException
     */
    private function addSellerMenuItem(int $parentId)
    {
        /** @var \Sm\MegaMenu\Api\Data\MenuItemsInterface|\Sm\MegaMenu\Model\MenuItems $auPostMenuItem */
        $auPostMenuItem = $this->menuItemsFactory->create();
        $auPostMenuItem->setTitle(self::AU_POST_SELLERS_ITEM_TITLE);
        $auPostMenuItem->setGroupId($this->getMenuGroupIdByTitle(self::MEGAMENU_HORIZONTAL_MENU_GROUP_TITLE));
        $auPostMenuItem->setParentId($parentId);
        $auPostMenuItem->setType(Type::PAGE_MODULE);
        $auPostMenuItem->setDataType(self::AU_POST_SELLERS_URL);
        $auPostMenuItem->setDepth(2);
        $auPostMenuItem->setColsNb(ListNumCol::SIX);
        $auPostMenuItem->setTarget(LinkTargets::_SELF);
        $auPostMenuItem->setData(
            self::MEGAMENU_ITEM_CUSTOMER_GROUP_ID,
            $this->getGroupIdByCode(AuPost::GROUP_CODE)
        );

        $this->menuItemsResourceModel->save($auPostMenuItem);
    }

    /**
     * Get CMS Page Landing ID
     *
     * @return int|null
     */
    private function getAuPostPageId(): ?int
    {
        $pageId = null;

        /** @var \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder */
        $searchCriteriaBuilder = $this->searchCriteriaBuilderFactory->create();
        $searchCriteria = $searchCriteriaBuilder
            ->addFilter(
                PageInterface::IDENTIFIER,
                AddAuPostLandingPage::AU_POST_PAGE_IDENTIFIER
            )
            ->create();
        try {
            $pages = $this->pageRepository->getList($searchCriteria);
            foreach ($pages->getItems() as $page) {
                $pageId = (int) $page->getId();
            }
        } catch (Exception $e) {
            $this->logger->error($e->getMessage());
        }

        return $pageId;
    }

    /**
     * Get Menu Group Root ID
     *
     * @param string $title
     * @return int
     */
    private function getRootIdForGroup(string $title): int
    {
        /** @var \Sm\MegaMenu\Api\Data\MenuItemsInterface $menuItem */
        $menuItem = $this->menuItemsFactory->create();
        $this->menuItemsResourceModel->load(
            $menuItem,
            'Root[' . $title . ']',
            MenuItemsInterface::TITLE
        );

        return (int)$menuItem->getItemsId();
    }

    /**
     * Get Menu Group ID by Title
     *
     * @param string $title
     * @return int
     */
    private function getMenuGroupIdByTitle(string $title): int
    {
        /** @var \Sm\MegaMenu\Api\Data\MenuGroupInterface $menuGroup */
        $menuGroup = $this->menuGroupFactory->create();
        $this->menuGroupResourceModel->load($menuGroup, $title, MenuGroupInterface::TITLE);

        return (int)$menuGroup->getGroupId();
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

        /** @var \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder */
        $searchCriteriaBuilder = $this->searchCriteriaBuilderFactory->create();
        $searchCriteria = $searchCriteriaBuilder
            ->addFilter(GroupInterface::CODE, $groupCode, 'eq')
            ->create();

        try {
            $groups = $this->groupRepository->getList($searchCriteria);
            foreach ($groups->getItems() as $group) {
                $groupId = (int)$group->getId();
            }
        } catch (Exception $e) {
            $this->logger->error($e->getMessage());
        }

        return $groupId;
    }
}
