<?php

/**
 * EYEMAGINE - The leading Magento Solution Partner
 *
 * HubSpot Integration with Magento
 *
 * @author    EYEMAGINE <magento@eyemaginetech.com>
 * @copyright Copyright (c) 2016 EYEMAGINE Technology, LLC (http://www.eyemaginetech.com)
 * @license   http://www.eyemaginetech.com/license
 */
namespace Eyemagine\HubSpot\Controller;

interface SyncInterface
{

    const ERROR_CODE_SECURE = 9001;

    const ERROR_CODE_ISSECURE = 9002;

    const ERROR_CODE_INVALID_STORECODE = 9003;

    const ERROR_CODE_INVALID_REQUEST = 9004;

    const ERROR_CODE_INVALID_CREDENTIALS = 9005;

    const ERROR_CODE_UNKNOWN_EXCEPTION = 9006;

    const ERROR_CODE_SYSTEM_CONFIG_DISABLED = 9020;

    const ERROR_CODE_UNSUPPORTED_SQL = 9500;

    const ERROR_CODE_UNSUPPORTED_FEATURE = 9600;

    const MAX_CUSTOMER_PERPAGE = 100;

    const MAX_SUBSCRIBER_PERPAGE = 100;

    const MAX_ORDER_PERPAGE = 50;

    const MAX_ASSOC_PRODUCT_LIMIT = 10;

    const IS_ABANDONED_IN_SECS = 3600;

    const LONG_DATE_FORMAT = 'l, F j, Y \a\t h:i A \U\T\C';

    const SORT_ORDER_ASC = \Magento\Framework\Data\Collection::SORT_ORDER_ASC;

    const SORT_ORDER_DESC = \Magento\Framework\Data\Collection::SORT_ORDER_DESC;
    
    const STATUS_ENABLED = \Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED;

    const EVENT_PRODUCT_VIEW = \Magento\Reports\Model\Event::EVENT_PRODUCT_VIEW;

    const EVENT_PRODUCT_COMPARE = \Magento\Reports\Model\Event::EVENT_PRODUCT_COMPARE;

    const EVENT_PRODUCT_TO_WISHLIST = \Magento\Reports\Model\Event::EVENT_PRODUCT_TO_WISHLIST;

    const SCOPE_STORE = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;

    const MODULE_NAME = 'Eyemagine_HubSpot';

    const IS_MULTISTORE = false;
}
