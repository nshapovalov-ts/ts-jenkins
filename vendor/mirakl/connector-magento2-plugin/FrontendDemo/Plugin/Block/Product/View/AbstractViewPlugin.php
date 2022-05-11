<?php
namespace Mirakl\FrontendDemo\Plugin\Block\Product\View;

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
     * @param   OfferHelper $offerHelper
     */
    public function __construct(OfferHelper $offerHelper)
    {
        $this->offerHelper = $offerHelper;
    }

    /**
     * @param   AbstractView    $subject
     * @param   \Closure        $proceed
     * @return  Product
     */
    public function aroundGetProduct(AbstractView $subject, \Closure $proceed)
    {
        /** @var Product $product */
        $product = $proceed();

        $operatorOffer = $this->offerHelper->getBestOperatorOffer($product);

        if (!$operatorOffer) {
            $offer = $this->offerHelper->getBestOffer($product);

            if ($offer) {
                $product->setData('main_offer', $offer);
            }
        }

        return $product;
    }
}
