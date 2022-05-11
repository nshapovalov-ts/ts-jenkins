<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Retailplace\Aghaseller\Block\Index;

use Magento\Framework\View\Element\Template\Context;
use Mirakl\Core\Model\ResourceModel\Shop\Collection;
use Mirakl\Core\Model\Shop;
use Retailplace\Aghaseller\Helper\Data;

class Filter extends \Magento\Framework\View\Element\Template
{
    /**
     * @var Data
     */
    protected $helper;

    /**
     * @param Context $context
     * @param Data $helper
     * @param array $data
     */
    public function __construct(
        Context $context,
        Data $helper,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->helper = $helper;
    }

    /**
     * Retrieve seller name collection
     *
     * @return Collection
     */
    public function getShopsForNameFilter()
    {
        $shopCollection = $this->helper->getAllShopCollection();
        $this->helper->applyFilterByMinimum($shopCollection);
        return $shopCollection;
    }

    /**
     * Retrieve Seller Minimum order array
     * @return array
     */
    public function getMinimumOrderValues()
    {
        $shopCollection = $this->helper->getAllShopCollection();
        $this->helper->applyFilterByName($shopCollection);

        $noMinimum = [];
        $hundredMinimum = [];
        $twoHundredMinimum = [];
        $threeHundredMinimum = [];
        $moreHundredMinimum = [];

        /** @var Shop $_shop */
        foreach ($shopCollection as $_shop) {
            $shopId = $_shop->getId();
            $minimumOrder = $_shop->getData('min-order-amount');
            if ($minimumOrder == 0) {
                $noMinimum[] = $shopId;
            }

            if ($minimumOrder <= 100 && $minimumOrder != 0) {
                $hundredMinimum[] = $shopId;
            }

            if ($minimumOrder <= 200 && $minimumOrder != 0) {
                $twoHundredMinimum[] = $shopId;
            }

            if ($minimumOrder <= 300 && $minimumOrder != 0) {
                $threeHundredMinimum[] = $shopId;
            }

            if ($minimumOrder > 300) {
                $moreHundredMinimum[] = $shopId;
            }
        }
        return [
            "nominimum"    => $noMinimum,
            "belowhundred" => $hundredMinimum,
            "belowtwo"     => $twoHundredMinimum,
            "belowthree"   => $threeHundredMinimum,
            "morethree"    => $moreHundredMinimum
        ];
    }

    /**
     * Retrieve Seller Name Request Array
     * @return array
     */
    public function getNameRequest()
    {
        $shopIds = $this->_request->getParam('shopname');
        return $shopIds ? explode(",", $shopIds) : [];
    }

    /**
     * Retrieve Minimum Order amount Request parameter
     * @return string
     */
    public function getMinimumRequest()
    {
        return $this->_request->getParam('minimum');
    }

    /**
     * Retrieve Seller Name Filter URL
     * @return string
     */
    public function getNameFilterUrl()
    {
        $currentUrl = $this->_urlBuilder->getCurrentUrl();
        $minimum = $this->_request->getParam('minimum');
        $filterUrl = $this->getUrl('aghaseller');
        if ($minimum) {
            $key = "shopname";
            $filterUrl = preg_replace('~(\?|&)' . $key . '=[^&]*~', '$1', $currentUrl);
            $filterUrl = str_replace("&", "", $filterUrl);
            $filterUrl = $filterUrl . "&";
        } else {
            $filterUrl = $filterUrl . "?";
        }
        return $filterUrl;
    }

    /**
     * Retrieve Minimum Order Filter URL
     * @return string
     */
    public function getMinimumFilterUrl()
    {
        $currentUrl = $this->_urlBuilder->getCurrentUrl();
        $name = $this->_request->getParam('shopname');
        $filterUrl = $this->getUrl('aghaseller');
        if ($name) {
            $key = "minimum";
            $filterUrl = preg_replace('~(\?|&)' . $key . '=[^&]*~', '$1', $currentUrl);
            $filterUrl = str_replace("&", "", $filterUrl);
            $filterUrl = $filterUrl . "&";
        } else {
            $filterUrl = $filterUrl . "?";
        }
        return $filterUrl;
    }

    /**
     * Retrieve Seller Name Filter Clear URL
     * @return string
     */
    public function getNameClearUrl()
    {
        $currentUrl = $this->_urlBuilder->getCurrentUrl();
        $key = "shopname";
        $nameClearUrl = preg_replace('~(\?|&)' . $key . '=[^&]*~', '$1', $currentUrl);
        $nameClearUrl = str_replace("&", "", $nameClearUrl);
        return $nameClearUrl;
    }
}
