<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Retailplace\Aghaseller\Block\Index;

use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\View\Element\Template\Context;
use Mirakl\Core\Model\ResourceModel\Shop\Collection;
use Mirakl\Core\Model\Shop;
use Retailplace\Aghaseller\Helper\Data;

class Index extends \Magento\Framework\View\Element\Template
{
    /**
     * @var PriceCurrencyInterface
     */
    protected $priceCurrency;

    /**
     * @var Data
     */
    protected $helper;

    /**
     * @var Collection
     */
    protected $shopCollection;

    /**
     * @param Context $context
     * @param PriceCurrencyInterface $priceCurrency
     * @param Data $helper
     * @param array $data
     */
    public function __construct(
        Context $context,
        PriceCurrencyInterface $priceCurrency,
        Data $helper,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->priceCurrency = $priceCurrency;
        $this->helper = $helper;
    }

    /**
     * @return $this
     */
    public function _prepareLayout()
    {
        parent::_prepareLayout();
        $this->pageConfig->getTitle()->set(__('AGHA Members'));
        $pagination = ($this->helper->getPageAllowedValues()) ? $this->helper->getPageAllowedValues() : "16,24,32,40,48,60";
        $paginationArray = explode(",", $pagination);
        $collection = $this->getCollection();
        if ($collection) {
            $pager = $this->getLayout()->createBlock(
                'Magento\Theme\Block\Html\Pager',
                'agha.seller.pager'
            )->setAvailableLimit($paginationArray)->setShowPerPage(true)->setCollection(
                $collection
            );
            $this->setChild('pager', $pager);
        }
        return $this;
    }

    /**
     * @return Collection
     */
    public function getCollection()
    {
        if ($this->shopCollection == null) {
            $page = ($this->_request->getParam('p')) ? $this->_request->getParam('p') : 1;
            $pageSize = ($this->helper->getPageDefaultValue()) ? $this->helper->getPageDefaultValue() : 16;
            $shopCollection = $this->helper->getShopCollection();
            $shopCollection->getSelect()->group('main_table.id');
            $shopCollection->setPageSize($pageSize);
            $shopCollection->setCurPage($page);
            $this->helper->addProductImages($shopCollection);
            $this->shopCollection = $shopCollection;
        }
        return $this->shopCollection;
    }

    /**
     * Retrieve seller shop collection
     *
     * @return Collection
     */
    public function getAghaTrustedShop()
    {
        $shopCollection = $this->helper->getShopCollection()->setPageSize(4);
        $this->helper->addProductImages($shopCollection);
        return $shopCollection;
    }

    /**
     * Check if Aghaseller module is enable
     * @return bool
     */
    public function getAghaSellerEnabled()
    {
        return $this->helper->isEnabled();
    }

    /**
     * format price with currency
     * @return string
     */
    public function getFormatedPrice($amount)
    {
        return $this->priceCurrency->convertAndFormat($amount);
    }

    /**
     * retrieve pagination for seller
     * @return string
     */
    public function getPagerHtml()
    {
        return $this->getChildHtml('pager');
    }

    /**
     * @return string
     */
    public function getMediaUrl()
    {
        return $this->_storeManager
            ->getStore()
            ->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA);
    }

    /**
     * @param Shop $shop
     * @return array
     */
    public function getShopImages($shop)
    {
        $allImages = [];
        $sellerImage = $shop->getImage();
        if ($sellerImage) {
            $allImages[] = $this->getMediaUrl() . $sellerImage;
        }
        $sellerProductImages = $shop->getData('product_images');
        if ($sellerProductImages && is_array($sellerProductImages)) {
            foreach ($sellerProductImages as $image) {
                $allImages[] = $image;
            }
        }
        return $allImages;
    }
}
