<?php declare(strict_types=1);

namespace Retailplace\CustomerAccount\Block\Widget;

class Tradesquare extends AbstractWidgetOption
{
    /**
     * the attribute code
     */
    const ATTRIBUTE_CODE = 'tradesquare';

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
