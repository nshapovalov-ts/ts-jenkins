<?php
namespace Mirakl\FrontendDemo\Block\Shop;

class ReturnPolicy extends View
{
    /**
     * {@inheritdoc}
     */
    protected function _setTabTitle()
    {
        $title = __('Return Policy');
        $this->setTitle($title);
    }
}