<?php

/**
 * Retailplace_MiraklFrontendDemo
 *
 * @copyright   Copyright (c) 2022 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Alexander Korsun <aleksandr@tradesquare.com.au>
 * @author      Ruslan Mukhatov <ruslan@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\MiraklFrontendDemo\Rewrite\Controller\Shop;

use Magento\Framework\App\Action\Context;
use Magento\Framework\Registry;
use Mirakl\Core\Model\ResourceModel\ShopFactory as ShopResourceFactory;
use Mirakl\Core\Model\Shop;
use Mirakl\Core\Model\ShopFactory;
use Retailplace\MiraklSellerAdditionalField\Helper\Data;
use Magento\Framework\Controller\ResultFactory;
use Retailplace\MiraklSellerAdditionalField\Model\SellerFilter;

class View extends \Mirakl\FrontendDemo\Controller\Shop\View
{
    /**
     * @var Data
     */
    protected $helper;

    /**
     * @var SellerFilter
     */
    private $sellerFilter;

    /**
     * @param Context $context
     * @param Registry $registry
     * @param ShopFactory $shopFactory
     * @param ShopResourceFactory $shopResourceFactory
     * @param Data $helper
     * @param SellerFilter $sellerFilter
     */
    public function __construct(
        Context $context,
        Registry $registry,
        ShopFactory $shopFactory,
        ShopResourceFactory $shopResourceFactory,
        Data $helper,
        SellerFilter $sellerFilter
    ) {
        parent::__construct($context, $registry, $shopFactory, $shopResourceFactory);
        $this->helper = $helper;
        $this->sellerFilter = $sellerFilter;
    }

    /**
     * {@inheritdoc}
     */
    public function execute()
    {
        $excludedShopIds = $this->helper->getShopIdsForExclusion();
        if (in_array($this->getRequest()->getParam('id'), $excludedShopIds)) {
            throw new \Magento\Framework\Exception\NotFoundException(__('Could not find shop.'));
        }

        /** @var Shop $shop */
        $shop = $this->shopFactory->create();
        $this->shopResourceFactory->create()->load($shop, $this->getRequest()->getParam('id'));
        if (!$shop->getId()) {
            throw new \Magento\Framework\Exception\NotFoundException(__('Could not find shop.'));
        }

        $this->coreRegistry->register('mirakl_shop', $shop);
        $this->sellerFilter->setFilteredShopOptionIds([$shop->getEavOptionId()]);

        /** @var \Magento\Framework\View\Result\Page $resultPage */
        $resultPage = $this->resultFactory->create(ResultFactory::TYPE_PAGE);

        return $resultPage;
    }
}
