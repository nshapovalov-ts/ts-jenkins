<?php
/**
 * Retailplace_MiraklSeller
 *
 * @copyright   Copyright (c) 2021 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Alexander Korsun <aleksandr@tradesquare.com.au>
 * @author      Dmitriy Fionov <dmitriy@tradesquare.com.au>
 */

namespace Retailplace\MiraklSeller\Plugin;

use Magento\Framework\DataObjectFactory as ObjectFactory;
use Magento\Sitemap\Helper\Data;
use Magento\Sitemap\Model\Sitemap as MagentoSitemap;
use Mirakl\Core\Model\ResourceModel\Shop\Collection as ShopCollection;
use Mirakl\Core\Model\ResourceModel\Shop\CollectionFactory as ShopCollectionFactory;
use Mirakl\Core\Model\Shop;

/**
 * Class Sitemap
 */
class Sitemap
{
    /**
     * @var string
     */
    const STATE = 'state';

    /**
     * @var array
     */
    const CUSTOM_PAGES = ['boutique', 'clearance', 'madeinau', 'sale', 'seller-specials'];

    /**
     * @var Data
     */
    private $helper;

    /**
     * @var ObjectFactory
     */
    private $dataObjectFactory;

    /**
     * @var ShopCollectionFactory
     */
    private $shopCollectionFactory;

    /**
     * @param Data $helper
     * @param ObjectFactory $dataObjectFactory
     * @param ShopCollectionFactory $shopCollectionFactory
     */
    public function __construct(
        Data $helper,
        ObjectFactory $dataObjectFactory,
        ShopCollectionFactory $shopCollectionFactory
    ) {
        $this->dataObjectFactory = $dataObjectFactory;
        $this->helper = $helper;
        $this->shopCollectionFactory = $shopCollectionFactory;
    }

    /**
     * Before Generate Xml
     *
     * @param MagentoSitemap $subject
     */
    public function beforeGenerateXml(MagentoSitemap $subject)
    {
        if (!method_exists($subject, 'addSitemapItem')) {
            return;
        }

        $storeId = $subject->getStoreId();
        $result = [];

        $date = date('Y-m-d H:i:s');
        foreach (self::CUSTOM_PAGES as $pageUrlKey) {
            $url = $pageUrlKey;
            $result[] = $this->dataObjectFactory->create()->setData(
                ['url' => $url, 'id' => $url, 'updated_at' => $date]
            );
        }

        foreach ($this->getAllShopCollection() as $shop) {
            $url = 'marketplace/shop/view/id/' . $shop->getId();
            $result[] = $this->dataObjectFactory->create()->setData(
                ['url' => $url, 'id' => $url, 'updated_at' => $date]
            );
        }

        $subject->addSitemapItem($this->dataObjectFactory->create()->setData([
            'changefreq' => $this->helper->getPageChangefreq($storeId),
            'priority'   => $this->helper->getPagePriority($storeId),
            'collection' => $result,
        ]));
    }

    /**
     * Get All Shop Collection
     *
     * @return ShopCollection
     */
    public function getAllShopCollection()
    {
        $shopCollection = $this->shopCollectionFactory->create();
        $shopCollection->addFieldToFilter(self::STATE, Shop::STATE_OPEN);
        return $shopCollection;
    }
}
