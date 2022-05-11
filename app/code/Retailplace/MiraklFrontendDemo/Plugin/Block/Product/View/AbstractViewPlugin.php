<?php

/**
 * Retailplace_MiraklFrontendDemo
 *
 * @copyright   Copyright (c) 2022 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Ruslan Mukhatov <ruslan@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\MiraklFrontendDemo\Plugin\Block\Product\View;

use Magento\Catalog\Block\Product\View\AbstractView;
use Magento\Catalog\Model\Product;
use Mirakl\FrontendDemo\Helper\Offer as OfferHelper;

class AbstractViewPlugin
{
    /**
     * @var OfferHelper
     */
    protected $offerHelper;

    /**
     * @param OfferHelper $offerHelper
     */
    public function __construct(OfferHelper $offerHelper)
    {
        $this->offerHelper = $offerHelper;
    }

    /**
     * @param AbstractView $subject
     * @param Product $result
     * @return Product
     */
    public function afterGetProduct(AbstractView $subject, Product $result): Product
    {
        if ($result->getData('main_offer')) {
            return $result;
        }

        $offer = $this->offerHelper->getBestOffer($result);

        if ($offer) {
            $result->setData('main_offer', $offer);
        }

        return $result;
    }
}
