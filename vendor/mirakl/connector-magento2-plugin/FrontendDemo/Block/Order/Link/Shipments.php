<?php
namespace Mirakl\FrontendDemo\Block\Order\Link;

class Shipments extends \Mirakl\FrontendDemo\Block\Order\Link
{
    /**
     * @return  bool
     */
    public function isEnableMultiShipments()
    {
        return $this->connectorConfig->isEnableMultiShipments();
    }

    /**
     * {@inheritdoc}
     */
    protected function _toHtml()
    {
        if (!$this->isEnableMultiShipments()) {
            return '';
        }

        return parent::_toHtml();
    }
}
