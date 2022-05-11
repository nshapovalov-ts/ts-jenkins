<?php

/**
 * Retailplace_MiraklSeller
 *
 * @copyright   Copyright (c) 2022 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Dmitriy Fionov <dmitriy@tradesquare.com.au>
 * @author      Natalia Sekulich <natalia@tradesquare.com.au>
 */

namespace Retailplace\MiraklSeller\Observer;

class LayoutLoadBefore implements \Magento\Framework\Event\ObserverInterface
{

    /**
     * @var \Magento\Framework\App\RequestInterface
     */
    private $_request;

    /**
     * LayoutLoadBefore constructor.
     * @param \Magento\Framework\App\RequestInterface $request
     */
    public function __construct(
        \Magento\Framework\App\RequestInterface $request
    ) {
        $this->_request = $request;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $sellerView = $this->_request->getParam('seller_view');
        if (!$sellerView) {
            return $this;
        }

        if ($sellerView) {
            $layout = $observer->getLayout();
            $addSellerViewHandlers = [
                'clearance_index_index',
                'madeinau_index_index',
                'sale_index_index',
                'au_post_index_index',
                'boutique_index_index',
                'seller-specials_index_index',
                'new-suppliers_index_index',
                'new-products_index_index',
            ];

            if ($this->getFullActionName() == "catalogsearch_result_index") {
                $layout->getUpdate()->addHandle('catalogsearch_result_index_seller');
            } elseif ($this->getFullActionName() == "catalog_category_view") {
                $layout->getUpdate()->addHandle('catalog_category_view_seller');
            } elseif (in_array($this->getFullActionName(), $addSellerViewHandlers)) {
                $layout->getUpdate()->addHandle($this->getFullActionName() . '_view_seller');
            }
        }

        return $this;
    }
    public function getFullActionName()
    {
        return $this->_request->getFullActionName();
    }
}
