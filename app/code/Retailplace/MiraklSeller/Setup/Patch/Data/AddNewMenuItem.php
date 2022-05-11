<?php
declare(strict_types=1);
/**
 * Retailplace_MiraklSeller
 *
 * @copyright   Copyright (c) 2022 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Natalia Sekulich <natalia@tradesquare.com.au>
 */

namespace Retailplace\MiraklSeller\Setup\Patch\Data;

use Sm\MegaMenu\Api\Data\MenuGroupInterface;
use Sm\MegaMenu\Model\Config\Source\ListNumCol;
use Sm\MegaMenu\Model\Config\Source\LinkTargets;
use Sm\MegaMenu\Model\MenuItems as MenuItemsModel;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Psr\Log\LoggerInterface;
use Sm\MegaMenu\Api\Data\MenuItemsInterface;
use Sm\MegaMenu\Api\Data\MenuItemsInterfaceFactory;
use Sm\MegaMenu\Model\Config\Source\Type;
use Sm\MegaMenu\Model\ResourceModel\MenuItems;
use Magento\Framework\Exception\AlreadyExistsException;
use Sm\MegaMenu\Model\ResourceModel\MenuItems as MenuItemsResourceModel;
use Sm\MegaMenu\Model\ResourceModel\MenuGroup as MenuGroupResourceModel;
use \Magento\Framework\Api\SearchCriteriaBuilderFactory;
use \Sm\MegaMenu\Api\Data\MenuGroupInterfaceFactory;

/**
 * Class AddNewMenuItem
 */
class AddNewMenuItem implements DataPatchInterface
{
    /** @var string */
    public const NEW_TOP_ITEM_TITLE = 'New';
    public const NEW_PRODUCTS_TOP_ITEM_TITLE = 'New Products';
    public const NEW_SUPPLIERS_TOP_ITEM_TITLE = 'New Suppliers';
    public const NEW_PRODUCTS_TOP_ITEM_URL = 'new-products';
    public const NEW_SUPPLIERS_TOP_ITEM_URL = 'new-suppliers?seller_view=1';
    public const MEGAMENU_HORIZONTAL_MENU_GROUP_TITLE = 'Mega Menu Horizontal';
    public const MEGAMENU_ITEM_CUSTOMER_GROUP_ID = 'customer_group_id';

    /** @var MenuItemsResourceModel */
    private $menuItemsResourceModel;

    /** @var MenuItemsInterfaceFactory */
    private $menuItemsFactory;

    /** @var \Psr\Log\LoggerInterface */
    private $logger;

    /** @var MenuGroupResourceModel */
    private $menuGroupResourceModel;

    /** @var MenuGroupInterfaceFactory */
    private $menuGroupFactory;

    /**
     * @param MenuItemsResourceModel $menuItemsResourceModel
     * @param MenuItemsInterfaceFactory $menuItemsFactory
     * @param MenuGroupResourceModel $menuGroupResourceModel
     * @param MenuGroupInterfaceFactory $menuGroupFactory
     * @param LoggerInterface $logger
     */
    public function __construct(
        MenuItemsResourceModel    $menuItemsResourceModel,
        MenuItemsInterfaceFactory $menuItemsFactory,
        MenuGroupResourceModel    $menuGroupResourceModel,
        MenuGroupInterfaceFactory $menuGroupFactory,
        LoggerInterface           $logger
    ) {
        $this->menuItemsResourceModel = $menuItemsResourceModel;
        $this->menuItemsFactory = $menuItemsFactory;
        $this->logger = $logger;
        $this->menuGroupResourceModel = $menuGroupResourceModel;
        $this->menuGroupFactory = $menuGroupFactory;
    }

    /**
     * Add menu "New" and its subcategories
     */
    public function apply()
    {
        try {
            $newRootItemId = $this->addTopMenuItem();
            $this->addNewItemSubcategories(
                $newRootItemId,
                self::NEW_PRODUCTS_TOP_ITEM_TITLE,
                self::NEW_PRODUCTS_TOP_ITEM_URL
            );
            $this->addNewItemSubcategories(
                $newRootItemId,
                self::NEW_SUPPLIERS_TOP_ITEM_TITLE,
                self::NEW_SUPPLIERS_TOP_ITEM_URL
            );
        } catch (AlreadyExistsException $e) {
            $this->logger->error($e->getMessage());
        }
    }

    /**
     * Add Top level "NEW" Menu Item
     *
     * @return int
     * @throws AlreadyExistsException
     */
    private function addTopMenuItem(): int
    {
        /** @var MenuItemsInterface|MenuItemsModel $newMenuItem */
        $newMenuItem = $this->menuItemsFactory->create();
        $newMenuItem->setTitle(self::NEW_TOP_ITEM_TITLE);
        $newMenuItem->setGroupId($this->getMenuGroupIdByTitle(self::MEGAMENU_HORIZONTAL_MENU_GROUP_TITLE));
        $newMenuItem->setParentId($this->getRootId());
        $newMenuItem->setType(Type::PAGE_MODULE);
        $newMenuItem->setCustomClass('agha_dropdown_main_bg ');
        $newMenuItem->setDataType('');
        $newMenuItem->setDepth(1);
        $newMenuItem->setPriorities(7);
        $newMenuItem->setColsNb(ListNumCol::TWO);

        $this->menuItemsResourceModel->save($newMenuItem);

        return (int)$newMenuItem->getItemsId();
    }

    /**
     * Get Menu Group ID by Title
     *
     * @param string $title
     *
     * @return int
     */
    private function getMenuGroupIdByTitle(string $title): int
    {
        /** @var MenuGroupInterface $menuGroup */
        $menuGroup = $this->menuGroupFactory->create();
        $this->menuGroupResourceModel->load($menuGroup, $title, MenuGroupInterface::TITLE);

        return (int)$menuGroup->getGroupId();
    }

    /**
     * Get Menu Group Root ID
     *
     * @return int
     */
    private function getRootId(): int
    {
        /** @var MenuItemsInterface $menuItem */
        $menuItem = $this->menuItemsFactory->create();
        $this->menuItemsResourceModel->load(
            $menuItem,
            'Root[' . self::MEGAMENU_HORIZONTAL_MENU_GROUP_TITLE . ']',
            MenuItemsInterface::TITLE
        );

        return (int)$menuItem->getItemsId();
    }

    /**
     * Add "New" menu subcategory item
     *
     * @param int $parentId
     * @param string $title
     * @param string $url
     *
     * @throws AlreadyExistsException
     */
    private function addNewItemSubcategories(int $parentId, string $title, string $url)
    {
        /** @var MenuItemsInterface|MenuItemsModel $newMenuItem */
        $newMenuItem = $this->menuItemsFactory->create();
        $newMenuItem->setTitle($title);
        $newMenuItem->setGroupId($this->getMenuGroupIdByTitle(self::MEGAMENU_HORIZONTAL_MENU_GROUP_TITLE));
        $newMenuItem->setParentId($parentId);
        $newMenuItem->setType(Type::PAGE_MODULE);
        $newMenuItem->setDataType($url);
        $newMenuItem->setOrderItem($parentId);
        $newMenuItem->setDepth(2);
        $newMenuItem->setColsNb(ListNumCol::SIX);
        $newMenuItem->setTarget(LinkTargets::_SELF);
        $newMenuItem->setData(
            self::MEGAMENU_ITEM_CUSTOMER_GROUP_ID,
            0
        );

        $this->menuItemsResourceModel->save($newMenuItem);
    }

    /**
     * Get array of patches that have to be executed prior to this.
     *
     * @return string[]
     */
    public static function getDependencies(): array
    {
        return [];
    }

    /**
     * Get aliases
     *
     * @return string[]
     */
    public function getAliases(): array
    {
        return [];
    }
}
