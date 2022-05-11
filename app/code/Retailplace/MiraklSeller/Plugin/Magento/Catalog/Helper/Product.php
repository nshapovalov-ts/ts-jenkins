<?php

/**
 * Retailplace_MiraklSeller
 *
 * @copyright   Copyright (c) 2022 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Ruslan Mukhatov <ruslan@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\MiraklSeller\Plugin\Magento\Catalog\Helper;

use Magento\Catalog\Model\Product as ProductModel;
use Mirakl\Connector\Helper\Offer as ConnectorOfferHelper;
use Mirakl\Connector\Model\Offer as OfferModel;
use Mirakl\FrontendDemo\Helper\Offer as OfferHelper;
use Retailplace\MiraklSellerAdditionalField\Model\SellerFilter;

/**
 * Class Product
 */
class Product
{
    /**
     * @var OfferHelper
     */
    private $offerHelper;

    /**
     * @var ConnectorOfferHelper
     */
    private $connectorOfferHelper;

    /**
     * @var SellerFilter
     */
    private $sellerFilter;

    /**
     * @param OfferHelper $offerHelper
     * @param ConnectorOfferHelper $connectorOfferHelper
     * @param SellerFilter $sellerFilter
     */
    public function __construct(
        OfferHelper $offerHelper,
        ConnectorOfferHelper $connectorOfferHelper,
        SellerFilter $sellerFilter
    ) {
        $this->offerHelper = $offerHelper;
        $this->connectorOfferHelper = $connectorOfferHelper;
        $this->sellerFilter = $sellerFilter;
    }

    /**
     * @param \Magento\Catalog\Helper\Product $subject
     * @param bool|ProductModel $result
     *
     * @return bool|ProductModel
     */
    public function afterInitProduct(
        \Magento\Catalog\Helper\Product $subject,
        $result
    ) {
        if (!($result instanceof ProductModel)) {
            return $result;
        }

        $offer = $this->offerHelper->getBestOffer($result);
        if (!($offer instanceof OfferModel)) {
            return $result;
        }

        $result->setData('main_offer', $offer);
        $shop = $offer->getShop();
        if ($shop->getId()) {
            $result->setData('shop', $shop);
            $this->sellerFilter->setFilteredShopOptionIds([$shop->getEavOptionId()]);
            $this->connectorOfferHelper->clearOfferCache($result);
        }

        return $result;
    }
}
