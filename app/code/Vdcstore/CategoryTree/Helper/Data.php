<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Vdcstore\CategoryTree\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Store\Model\ScopeInterface;

class Data extends AbstractHelper
{
    const MIRAKL_ROOT_CATEGORY = 'category_tree/general/mirakle_root';
    const MENU_ROOT_CATEGORY = 'category_tree/general/menu_root';
    const HIDE_CATEGORY_LIMIT = 'hide_category/hide_category/hide_category';

    /**
     * @param \Magento\Framework\App\Helper\Context $context
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context
    ) {
        parent::__construct($context);
    }

    public function getConfigValue($field, $storeId = null)
    {
        return $this->scopeConfig->getValue(
            $field,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    public function getMenuRoot($field = self::MENU_ROOT_CATEGORY)
    {
        return $this->getConfigValue($field);
    }

    public function getMiraklRoot($field = self::MIRAKL_ROOT_CATEGORY)
    {
        return $this->getConfigValue($field);
    }

    public function getProductCountLimit($field = self::HIDE_CATEGORY_LIMIT)
    {
        return $this->getConfigValue($field);
    }
    public function isRemoveUnmappedProduct()
    {
        return $this->getConfigValue('category_tree/general/remove_unmapped_products');
    }
    public function isForceIndexingEnabled()
    {
        return $this->getConfigValue('category_tree/general/enable_force_indexing');
    }
}
