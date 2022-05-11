<?php

/**
 * Retailplace_MiraklSellerAdditionalField
 *
 * @copyright   Copyright (c) 2021 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Alexander Korsun <aleksandr@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\MiraklSellerAdditionalField\Plugin;

use Magento\Framework\App\Request\Http;
use Magento\Framework\Registry;

class Toolbar
{
    /**
     * @var Http
     */
    protected $request;
    /**
     * @var Catalog\Model\Config
     */
    protected $catalogModelConfig;
    /**
     * @var Registry
     */
    private $registry;

    /**
     * Toolbar constructor.
     * @param Http $request
     * @param Registry $registry
     * @param Catalog\Model\Config $catalogModelConfig
     */
    public function __construct(
        Http $request,
        Registry $registry,
        Catalog\Model\Config $catalogModelConfig
    ) {
        $this->request = $request;
        $this->registry = $registry;
        $this->catalogModelConfig = $catalogModelConfig;
    }

    /**
     * @param \Magento\Catalog\Block\Product\ProductList\Toolbar $subject
     * @param \Magento\Catalog\Block\Product\ProductList\Toolbar $result
     * @param $collection
     * @return \Magento\Catalog\Block\Product\ProductList\Toolbar
     * @throws \Zend_Db_Select_Exception
     */
    public function afterSetCollection(
        \Magento\Catalog\Block\Product\ProductList\Toolbar $subject,
        \Magento\Catalog\Block\Product\ProductList\Toolbar $result,
        $collection
    ) {
        $isPriceSortingDisabled = $this->catalogModelConfig->isPriceSortingDisabled();
        $currentOrder = $subject->getCurrentOrder();
        $_collection = $subject->getCollection();
        if ($currentOrder) {
            if (!$isPriceSortingDisabled && $currentOrder == 'high_to_low') {
                $_collection->setOrder('price', 'desc');
            } elseif (!$isPriceSortingDisabled && $currentOrder == 'low_to_high') {
                $_collection->setOrder('price', 'asc');
            } elseif ($currentOrder == 'newly_added') {
                $_collection->setOrder('created_at', 'desc');
            } elseif ($currentOrder == 'retail_margin') {
                $_collection->setOrder($currentOrder, 'desc');
            } elseif ($currentOrder == 'sort_score') {
                $_collection->setOrder($currentOrder, 'desc');
            }
        }
        $category = $this->registry->registry('current_category');
        if ($category) {
            $page = $this->request->getParam('p');
            if ($page == '') {
                $page = 1;
            }
            $_collection->getCurPage();
            $_collection->setCurPage($page);
        }
        return $result;
    }
}
