<?php
declare(strict_types=1);

namespace Mirakl\GraphQl\Model\Mapper;

use Mirakl\Core\Model\Shop as ShopModel;

class Shop
{
    /**
     * @param  ShopModel  $shop
     * @return array
     */
    public function toGraphQlArray(ShopModel $shop)
    {
        $data = $shop->getData();
        $additionalInfo = $shop->getAdditionalInfo();
        if (!$additionalInfo->isEmpty()) {
            $data['additional_info'] = $additionalInfo->getData();
            $data['additional_info']['json_string'] = json_encode($additionalInfo->getData());
        }
        $data['model'] = $shop;

        return $data;
    }
}
