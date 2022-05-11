<?php declare(strict_types=1);

namespace Retailplace\CustomerAccount\Block\Widget;

class SellGoodsOffline extends AbstractWidgetOption
{
    /**
     * the attribute code
     */
    const ATTRIBUTE_CODE = 'sell_goods_offline';

    /**
     * @return void
     */
    public function _construct()
    {
        parent::_construct();
        $this->setAttributeCode(self::ATTRIBUTE_CODE);
        // default template location
        $this->setTemplate('Retailplace_CustomerAccount::widget/preferences/selectimg.phtml');
    }
}
