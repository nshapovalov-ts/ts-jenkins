<?php
namespace Mirakl\FrontendDemo\Helper;

class Config extends \Mirakl\Connector\Helper\Config
{
    const XML_PATH_SHIPPING_FEES_CACHE_LIFETIME = 'mirakl_frontend/general/shipping_fees_cache_lifetime';

    /** @deprecated */
    const XML_PATH_AUTO_REMOVE_OFFERS           = 'mirakl_frontend/shopping_cart/auto_remove_offers';
    /** @deprecated */
    const XML_PATH_AUTO_UPDATE_OFFERS           = 'mirakl_frontend/shopping_cart/auto_update_offers';

    const XML_PATH_OFFER_NEW_STATE              = 'mirakl_frontend/offer/new_state';

    const XML_PATH_CHOICEBOX_ENABLE             = 'mirakl_frontend/choicebox/enable';
    const XML_PATH_CHOICEBOX_ELEMENTS           = 'mirakl_frontend/choicebox/elements';

    /**
     * @return  int
     */
    public function getNewOfferStateId()
    {
        return $this->getValue(self::XML_PATH_OFFER_NEW_STATE);
    }

    /**
     * Should we update Mirakl offers automatically when processing shopping cart?
     *
     * @param   mixed   $store
     * @return  bool
     * @deprecated
     * @see \Mirakl\Connector\Helper\Config::isAutoUpdateOffers()
     */
    public function isAutoUpdateOffers($store = null)
    {
        $value = $this->getRawValue(static::XML_PATH_AUTO_UPDATE_OFFERS);

        return false !== $value ? (bool) $value : parent::isAutoUpdateOffers($store);
    }

    /**
     * @return  bool
     */
    public function isChoiceBoxEnabled()
    {
        return $this->getFlag(self::XML_PATH_CHOICEBOX_ENABLE);
    }

    /**
     * @return  int
     */
    public function getNbChoiceBoxElements()
    {
        return $this->getValue(self::XML_PATH_CHOICEBOX_ELEMENTS);
    }

    /**
     * @return  int
     */
    public function getShippingFeesCacheLifetime()
    {
        return (int) $this->getValue(self::XML_PATH_SHIPPING_FEES_CACHE_LIFETIME);
    }

    /**
     * Indicates if SH02 API calls should be cached or not (only for AJAX requests)
     *
     * @return  bool
     */
    public function isShippingFeesCacheEnabled()
    {
        return $this->_request->isAjax() && $this->getShippingFeesCacheLifetime() > 0;
    }
}
