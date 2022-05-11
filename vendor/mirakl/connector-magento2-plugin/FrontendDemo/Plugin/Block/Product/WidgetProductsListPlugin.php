<?php
namespace Mirakl\FrontendDemo\Plugin\Block\Product;

use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\CatalogWidget\Block\Product\ProductsList;
use Mirakl\FrontendDemo\Helper\Offer as OfferHelper;

class WidgetProductsListPlugin
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
     * @param  ProductsList $subject
     * @param  Collection   $result
     * @return Collection
     */
    public function afterCreateCollection(ProductsList $subject, Collection $result)
    {
        foreach ($result as $product) {
            $offer = $this->offerHelper->getBestOffer($product);

            if ($offer) {
                $product->setData('main_offer', $offer);
            }
        }

        return $result;
    }
}
