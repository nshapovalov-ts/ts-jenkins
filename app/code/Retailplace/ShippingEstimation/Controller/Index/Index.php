<?php

/**
 * Retailplace_ShippingEstimation
 *
 * @copyright   Copyright (c) 2022 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Ruslan Mukhatov <ruslan@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\ShippingEstimation\Controller\Index;

class Index extends \Magento\Framework\App\Action\Action
{
    /** @var \Magento\Framework\View\Result\PageFactory */
    protected $resultPageFactory;

    /** @var \Magento\Framework\Json\Helper\Data */
    protected $jsonHelper;

    /** @var \Magento\Store\Model\StoreManagerInterface */
    protected $_storeManager;

    /** @var \Magento\Checkout\Model\Session */
    protected $session;

    /** @var \Magento\Framework\Pricing\PriceCurrencyInterface */
    protected $priceCurrency;

    /**
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Framework\View\Result\PageFactory $resultPageFactory
     * @param \Magento\Framework\Json\Helper\Data $jsonHelper
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Checkout\Model\Session $session
     * @param \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Magento\Framework\Json\Helper\Data $jsonHelper,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Checkout\Model\Session $session,
        \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency
    ) {
        $this->resultPageFactory = $resultPageFactory;
        $this->jsonHelper = $jsonHelper;
        $this->_storeManager = $storeManager;
        $this->_session = $session;
        $this->priceCurrency = $priceCurrency;
        parent::__construct($context);
    }

    /**
     * Execute view action
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $shopId = $this->getRequest()->getPost('shopid');
        $freeShippingAmount = $this->getRequest()->getPost('freeshipamount') ?? 0;
        $freeShipping = $this->getRequest()->getPost('freeshipping') ?? 0;
        $isSellerPage = $this->getRequest()->getPost('sellerpage') ?? false;
        $shopName = $this->getRequest()->getPost('shopname');
        $resultJson = $this->resultFactory->create(\Magento\Framework\Controller\ResultFactory::TYPE_JSON);
        $mediaUrl = $this->_storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA);
        $reached = 'wysiwyg/delivery_icon_new.png';
        $html = '';

        if ($freeShipping == 1) {
            $freeShipMessage = 'Free shipping for all the products of ' . $shopName . '';
            $html = '<div class="service-icon toolpick-ico" style="font-size: 30px;">
                        <img src="' . $mediaUrl . $reached . '" alt="Free Shipping Reached" />
                    </div>
                    <div class="service-info">
                        <h4>Free shipping
                        <div class="toolpick">
                            <div class="toolpick-ico">
                                <i>i</i>
                                <div class="toolpick-text">
                                    <p>' . $freeShipMessage . '</p>
                                </div>
                            </div>
                        </div></h4>
                    </div>';
        } else {
            $shippingCode = $this->getRequest()->getPost('shippingcode');
            if ($isSellerPage):
                $freeShipMessage = 'FREE shipping available for orders above ' . $this->getFormatedPrice($freeShippingAmount) . '. Please add more products to your cart and reach the min order amount.';
            else:
                $freeShipMessage = 'FREE shipping available for orders above ' . $this->getFormatedPrice($freeShippingAmount) . '. Visit the Supplier Showroom to add more products to your cart and reach the min order amount.';
            endif;
            $freeShipReachedMessage = 'Free ' . $shippingCode . ' shipping for all the orders above ' . $this->getFormatedPrice($freeShippingAmount) . ' of ' . $shopName . '';

            if (!$shopId) {
                return $resultJson;
            }

            $items = $this->_session->getQuote()->getAllItems();

            $itemCount = 0;
            $orderTotal = 0;
            foreach ($items as $item) {
                if ($item->getMiraklShopId() == $shopId) {
                    $orderTotal += $item->getBaseRowTotalInclTax();
                    $itemCount++;
                }
            }

            if ($orderTotal > 0) {
                $checkreached = $freeShippingAmount - $orderTotal;
                if ($freeShippingAmount) {
                    if ($checkreached > 0) {
                        $percentage = round(($orderTotal / $freeShippingAmount) * 100);
                        $html = '<div class="service-icon" style="font-size: 30px;">
                                    <div class="c100 p' . $percentage . ' big orange">
                                        <span>' . $percentage . '%</span>
                                        <div class="slice">
                                            <div class="bar"></div>
                                            <div class="fill"></div>
                                        </div>
                                    </div>
                                </div>
                                <div class="service-info">
                                    <h4>Free Shipping
                                        <div class="toolpick">
                                        <div class="toolpick-ico">
                                            <i>i</i>
                                            <div class="toolpick-text">
                                                <p>' . $freeShipMessage . '</p>
                                            </div>
                                        </div>
                                    </div></h4>
                                    <p>' . $this->getFormatedPrice($checkreached) . ' order amount left</p>
                                </div>';
                    } else {
                        $percentage = 100;
                        $html = '<div class="service-icon" style="font-size: 30px;">
                                    <div class="c100 p' . $percentage . ' big orange">
                                        <span>' . $percentage . '%</span>
                                        <div class="slice">
                                            <div class="bar"></div>
                                            <div class="fill"></div>
                                        </div>
                                    </div>
                                </div>
                                <div class="service-info">
                                    <h4>Free Shipping
                                        <div class="toolpick">
                                        <div class="toolpick-ico">
                                            <i>i</i>
                                            <div class="toolpick-text">
                                                <p>' . $freeShipReachedMessage . '</p>
                                            </div>
                                        </div>
                                    </div></h4>
                                    <p>Order amount reached</p>
                                </div>';
                    }
                }
            } else {
                $percentage = 0;
                $html = '<div class="service-icon" style="font-size: 30px;">
                            <div class="c100 p' . $percentage . ' big orange">
                                <span>' . $percentage . '%</span>
                                <div class="slice">
                                    <div class="bar"></div>
                                    <div class="fill"></div>
                                </div>
                            </div>
                        </div>
                        <div class="service-info">
                            <h4>Free Shipping
                                 <div class="toolpick">
                                <div class="toolpick-ico">
                                    <i>i</i>
                                    <div class="toolpick-text">
                                        <p>' . $freeShipMessage . '</p>
                                    </div>
                                </div>
                            </div></h4>
                            <p>Above ' . $this->getFormatedPrice($freeShippingAmount) . ' order amount</p>
                        </div>';
            }
        }
        $resultJson->setData($html);
        return $resultJson;
    }

    /**
     * @param string|int $amount
     * @return string
     */
    public function getFormatedPrice($amount): string
    {
        return $this->priceCurrency->convertAndFormat($amount);
    }
}
