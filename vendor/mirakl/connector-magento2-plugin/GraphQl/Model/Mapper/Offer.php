<?php
declare(strict_types=1);

namespace Mirakl\GraphQl\Model\Mapper;

use Magento\Catalog\Model\Product;
use Mirakl\Connector\Model\Offer as OfferModel;

class Offer
{
    /**
     * @param   Product     $product
     * @param   OfferModel  $offer
     * @return  array
     */
    public function toGraphQlArray(Product $product, OfferModel $offer)
    {
        $data = $offer->getData();
        if (count($additionalInfo = $offer->getAdditionalInfo())) {
            $data['additional_info'] = $additionalInfo;
            $data['additional_info']['json_string'] = $offer->getData('additional_info');
        }
        $data['model'] = $offer;
        $data['product_model'] = $product;

        return $data;
    }
}
