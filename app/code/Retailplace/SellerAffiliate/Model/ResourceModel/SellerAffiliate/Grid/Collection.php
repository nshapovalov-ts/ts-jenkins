<?php

/**
 * Retailplace_SellerAffiliate
 *
 * @copyright   Copyright (c) 2022 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Nikolay Shapovalov <nikolay@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\SellerAffiliate\Model\ResourceModel\SellerAffiliate\Grid;

use Magento\Framework\View\Element\UiComponent\DataProvider\SearchResult;

/**
 * Class Collection implements Grid Collection for Seller Affiliate model
 * @SuppressWarnings(PHPMD.CamelCaseMethodName)
 */
class Collection extends SearchResult
{
    /**
     * @return void
     */
    protected function _renderFiltersBefore()
    {
        $this->getSelect()
            ->joinLeft(
                ['ce' => $this->getTable('customer_entity')],
                'main_table.customer_id = ce.entity_id',
                [
                    'email',
                    'firstname',
                    'lastname'
                ]
            ) ->joinLeft(
                ['ms' => $this->getTable('mirakl_shop')],
                'main_table.seller_id = ms.id',
                [
                    'shop_id' => 'id',
                    'shop_name' => 'name',
                ]
            );

        $this->getSelect()
            ->columns(new \Zend_Db_Expr("CONCAT(`ce`.`firstname`, ' ',`ce`.`lastname`) AS customer_name"));
        parent::_renderFiltersBefore();
    }
}
