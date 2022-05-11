<?php
/**
 * Recent Order Notification
 * @author      Trinh Doan
 * @copyright   Copyright (c) 2017 Trinh Doan
 * @package     TD_SoldNotification
 */

namespace TD\SoldNotification\Block;
class Recentorder extends \Magento\Framework\View\Element\Template
{
    protected $scopeConfig;
    protected $request;
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\App\Request\Http $request
    )
    {
        $this->scopeConfig = $scopeConfig;
        $this->request = $request;
        parent::__construct($context);
    }

    public function getActive()
    {
        if ($this->checkExcludePage()) {
            return false;
        }
        return $this->scopeConfig->getValue('sold_notification/general/active', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }
    public function getBackgroundColor()
    {
        return $this->scopeConfig->getValue('sold_notification/design/background_color', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }

    public function getTextColor()
    {
        return $this->scopeConfig->getValue('sold_notification/design/text_color', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }

    public function getHighlightColor()
    {
        return $this->scopeConfig->getValue('sold_notification/design/highligh_color', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }

    public function getPosition()
    {
        return $this->scopeConfig->getValue('sold_notification/design/position', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }

    public function getTimeDisplay()
    {
        return $this->scopeConfig->getValue('sold_notification/time/time_display', \Magento\Store\Model\ScopeInterface::SCOPE_STORE)*1000;
    }

    public function getTimeDelay()
    {
        return $this->scopeConfig->getValue('sold_notification/time/time_delay', \Magento\Store\Model\ScopeInterface::SCOPE_STORE)*1000;
    }

    public function getEffect()
    {
        return $this->scopeConfig->getValue('sold_notification/design/effect', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }

    public function getDisableMobile()
    {
        return $this->scopeConfig->getValue('sold_notification/design/disable_mobile', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }

    public function getMaxWidth()
    {
        return $this->scopeConfig->getValue('sold_notification/design/max_width', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }

    public function checkExcludePage()
    {
        if ($this->scopeConfig->getValue('sold_notification/exclude_page/homepage', \Magento\Store\Model\ScopeInterface::SCOPE_STORE)
         && $this->request->getFullActionName() == 'cms_index_index') {
            return true;
        }
        if ($this->scopeConfig->getValue('sold_notification/exclude_page/checkout', \Magento\Store\Model\ScopeInterface::SCOPE_STORE)
         && $this->request->getFullActionName() == 'checkout_index_index') {
            return true;
        }
        if ($this->scopeConfig->getValue('sold_notification/exclude_page/shoppingcart', \Magento\Store\Model\ScopeInterface::SCOPE_STORE)
         && $this->request->getFullActionName() == 'checkout_cart_index') {
            return true;
        }
    }

    public function getCustomCss()
    {
        return $this->scopeConfig->getValue('sold_notification/design/custom_css', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }
}