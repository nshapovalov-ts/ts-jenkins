<?php
namespace Mirakl\FrontendDemo\Block\Shop;

class ContactInfo extends View
{
    /**
     * {@inheritdoc}
     */
    protected function _setTabTitle()
    {
        $title = __('Contact Information');
        $this->setTitle($title);
    }
}