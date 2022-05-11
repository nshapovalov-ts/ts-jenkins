<?php

/**
 * Retailplace_MiraklPromotion
 *
 * @copyright   Copyright (c) 2021 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Dmitriy Fionov <dmitriy@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\MiraklPromotion\Setup\Patch\Data;

use Magento\Framework\Setup\Patch\DataPatchInterface;
use Psr\Log\LoggerInterface;
use Sm\MegaMenu\Api\Data\MenuItemsInterface;
use Sm\MegaMenu\Api\Data\MenuItemsInterfaceFactory;
use Sm\MegaMenu\Model\Config\Source\Type;
use Sm\MegaMenu\Model\ResourceModel\MenuItems;

/**
 * Class SellerSpecialMenuItem
 */
class SellerSpecialMenuItem implements DataPatchInterface
{
    /** @var string */
    public const SELLER_SPECIAL_TOP_ITEM_TITLE = 'Seller Specials';
    public const SELLER_SPECIAL_URL = 'seller-specials';
    public const MENU_ITEM_TITLE_TO_REPLACE = 'Price Guarantee';

    /** @var \Sm\MegaMenu\Model\ResourceModel\MenuItems */
    private $menuItemsResourceModel;

    /** @var \Sm\MegaMenu\Api\Data\MenuItemsInterfaceFactory */
    private $menuItemsFactory;

    /** @var \Psr\Log\LoggerInterface */
    private $logger;

    /**
     * SellerSpecialMenuItem Constructor
     *
     * @param \Sm\MegaMenu\Model\ResourceModel\MenuItems $menuItemsResourceModel
     * @param \Sm\MegaMenu\Api\Data\MenuItemsInterfaceFactory $menuItemsFactory
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        MenuItems $menuItemsResourceModel,
        MenuItemsInterfaceFactory $menuItemsFactory,
        LoggerInterface $logger
    ) {
        $this->menuItemsResourceModel = $menuItemsResourceModel;
        $this->menuItemsFactory = $menuItemsFactory;
        $this->logger = $logger;
    }

    /**
     * Run code inside patch
     */
    public function apply()
    {
        /** @var \Sm\MegaMenu\Api\Data\MenuItemsInterface $menuItem */
        $menuItem = $this->menuItemsFactory->create();
        $this->menuItemsResourceModel->load(
            $menuItem,
            self::MENU_ITEM_TITLE_TO_REPLACE,
            MenuItemsInterface::TITLE
        );
        $menuItem->setTitle(self::SELLER_SPECIAL_TOP_ITEM_TITLE);
        $menuItem->setDataType(self::SELLER_SPECIAL_URL);
        $menuItem->setType(Type::PAGE_MODULE);

        try {
            $this->menuItemsResourceModel->save($menuItem);
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
        }
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
     * Get aliases (previous names) for the patch.
     *
     * @return string[]
     */
    public function getAliases(): array
    {
        return [];
    }
}
