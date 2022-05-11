<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2020 Amasty (https://www.amasty.com)
 * @package Amasty_ShopbyBrand
 */


declare(strict_types=1);

namespace Amasty\ShopbyBrand\Model\Sitemap\ItemProvider;

use Amasty\ShopbyBase\Model\SitemapBuilder;
use Amasty\ShopbyBrand\Model\XmlSitemap;
use Magento\Framework\Url;

class Brand
{
    /**
     * @var Url
     */
    private $url;

    /**
     * @var SitemapBuilder
     */
    private $sitemapBuilder;

    /**
     * @var XmlSitemap
     */
    private $xmlSitemap;

    public function __construct(
        Url $url,
        SitemapBuilder $sitemapBuilder,
        XmlSitemap $xmlSitemap
    ) {
        $this->url = $url;
        $this->sitemapBuilder = $sitemapBuilder;
        $this->xmlSitemap = $xmlSitemap;
    }

    /**
     * @param int $storeId
     * @return array|\Magento\Sitemap\Model\SitemapItemInterface[]
     */
    public function getItems($storeId)
    {
        $result = $this->xmlSitemap->getBrandUrls($storeId, $this->url->getBaseUrl());

        return $this->sitemapBuilder->prepareItems($result, $storeId);
    }
}
