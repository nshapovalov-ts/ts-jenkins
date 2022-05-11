<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Vdcstore\ExtendedGtm\Rewrite\WeltPixel\GoogleTagManager\Model;

class Api extends \WeltPixel\GoogleTagManager\Model\Api
{
	const TRIGGER_CART_PAGE = 'WP - Cart Page';
	const TAG_CART_PAGE = 'WP - Cart Page';
    const TRIGGER_PRODUCT_PAGE = 'WP - Product Page';
    const TAG_PRODUCT_PAGE = 'WP - Product Page';
    const TRIGGER_REGISTRATION_PAGE = 'WP - Registration';
    const TAG_REGISTRATION_PAGE = 'WP - Registration';
    const TRIGGER_NEWSLETTER_PAGE = 'WP - Newsletter';
    const TAG_NEWSLETTER_PAGE = 'WP - Newsletter';

	/**
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\UrlInterface $urlBuilder
     * @param \Magento\Backend\Model\Session $backendSession
     * @param \Magento\Framework\App\Request\Http $request
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\UrlInterface $urlBuilder,
        \Magento\Backend\Model\Session $backendSession,
        \Magento\Framework\App\Request\Http $request
    )
    {
        parent::__construct($context,
				$registry,
				$urlBuilder,
				$backendSession,
				$request);
        $this->_urlBuilder = $urlBuilder;
        $this->_backendSession = $backendSession;
        $this->request = $request;
    }

    public function getTriggersList()
    {
    	$cartTrigger = $this->getCartPageTrigger();
        $productTrigger = $this->getProductPageTrigger();
        $registrationTrigger = $this->getRegistrationTrigger();
        $newsletterTrigger = $this->getNewsletterTrigger();
        return array_merge($this->_getTriggers(), $cartTrigger, $productTrigger, $registrationTrigger, $newsletterTrigger);
    }

    /**
     * @param boolean $ipAnonymization
     * @param boolean $displayAdvertising
     * @param array $triggersMapping
     * @return array
     */
    public function getTagsList($ipAnonymization, $displayAdvertising, $triggersMapping)
    {
    	$cartTrigger = $this->getCartPageTags($triggersMapping, $ipAnonymization, $displayAdvertising);
        $productTrigger = $this->getProductPageTags($triggersMapping, $ipAnonymization, $displayAdvertising);
        $registrationTrigger = $this->getRegistrationTags($triggersMapping, $ipAnonymization, $displayAdvertising);
        $newsletterTrigger = $this->getNewsletterTags($triggersMapping, $ipAnonymization, $displayAdvertising);
        return array_merge($this->_getTags($triggersMapping, $ipAnonymization, $displayAdvertising), $cartTrigger, $productTrigger, $registrationTrigger, $newsletterTrigger);
    }

    public function getCartPageTrigger()
    {
    	$triggers = array
        (
        	self::TRIGGER_CART_PAGE => array
            (
                'name' => self::TRIGGER_CART_PAGE,
                'type' => self::TYPE_TRIGGER_CUSTOM_EVENT,
                'customEventFilter' => array
                (
                    array
                    (
                        'type' => 'equals',
                        'parameter' => array
                        (
                            array
                            (
                                'type' => 'template',
                                'key' => 'arg0',
                                'value' => '{{_event}}'
                            ),
                            array
                            (
                                'type' => 'template',
                                'key' => 'arg1',
                                'value' => 'cart'
                            )
                        )
                    )
                )
            ),
        );
        return $triggers;
    }

    public function getProductPageTrigger()
    {
        $triggers = array
        (
            self::TRIGGER_PRODUCT_PAGE => array
            (
                'name' => self::TRIGGER_PRODUCT_PAGE,
                'type' => self::TYPE_TRIGGER_CUSTOM_EVENT,
                'customEventFilter' => array
                (
                    array
                    (
                        'type' => 'equals',
                        'parameter' => array
                        (
                            array
                            (
                                'type' => 'template',
                                'key' => 'arg0',
                                'value' => '{{_event}}'
                            ),
                            array
                            (
                                'type' => 'template',
                                'key' => 'arg1',
                                'value' => 'productView'
                            )
                        )
                    )
                )
            ),
        );
        return $triggers;
    }

    public function getRegistrationTrigger()
    {
        $triggers = array
        (
            self::TRIGGER_REGISTRATION_PAGE => array
            (
                'name' => self::TRIGGER_REGISTRATION_PAGE,
                'type' => self::TYPE_TRIGGER_CUSTOM_EVENT,
                'customEventFilter' => array
                (
                    array
                    (
                        'type' => 'equals',
                        'parameter' => array
                        (
                            array
                            (
                                'type' => 'template',
                                'key' => 'arg0',
                                'value' => '{{_event}}'
                            ),
                            array
                            (
                                'type' => 'template',
                                'key' => 'arg1',
                                'value' => 'accountSignup'
                            )
                        )
                    )
                )
            ),
        );
        return $triggers;
    }

    public function getNewsletterTrigger()
    {
        $triggers = array
        (
            self::TRIGGER_NEWSLETTER_PAGE => array
            (
                'name' => self::TRIGGER_NEWSLETTER_PAGE,
                'type' => self::TYPE_TRIGGER_CUSTOM_EVENT,
                'customEventFilter' => array
                (
                    array
                    (
                        'type' => 'equals',
                        'parameter' => array
                        (
                            array
                            (
                                'type' => 'template',
                                'key' => 'arg0',
                                'value' => '{{_event}}'
                            ),
                            array
                            (
                                'type' => 'template',
                                'key' => 'arg1',
                                'value' => 'emailSignup'
                            )
                        )
                    )
                )
            ),
        );
        return $triggers;
    }

    public function getCartPageTags($triggers, $ipAnonymization, $displayAdvertising)
    {
        $tags = array
        (
            self::TAG_CART_PAGE => array
            (
                'name' => self::TAG_CART_PAGE,
                'firingTriggerId' => array
                (
                    $triggers[self::TRIGGER_CART_PAGE]
                ),
                'type' => self::TYPE_TAG_UA,
                'tagFiringOption' => 'oncePerEvent',
                'parameter' => array
                (
                    array
                    (
                        'type' => 'boolean',
                        'key' => 'nonInteraction',
                        'value' => "false"
                    ),
                    array
                    (
                        'type' => 'boolean',
                        'key' => 'useEcommerceDataLayer',
                        'value' => "true"
                    ),
                    array
                    (
                        'type' => 'template',
                        'key' => 'eventCategory',
                        'value' => 'Ecommerce'
                    ),
                    array
                    (
                        'type' => 'template',
                        'key' => 'trackType',
                        'value' => 'TRACK_EVENT'
                    ),
                    array
                    (
                        'type' => 'template',
                        'key' => 'eventAction',
                        'value' => 'Checkout'
                    ),
                    array
                    (
                        'type' => 'boolean',
                        'key' => 'enableEcommerce',
                        'value' => "true"
                    ),
                    array
                    (
                        'type' => 'boolean',
                        'key' => 'overrideGaSettings',
                        'value' => "true"
                    ),
                    array
                    (
                        'type' => 'boolean',
                        'key' => 'doubleClick',
                        'value' => $displayAdvertising
                    ),
                    array
                    (
                        'type' => 'template',
                        'key' => 'eventLabel',
                        'value' => '{{' . self::VARIABLE_EVENTLABEL . '}}'
                    ),
                    array
                    (
                        'type' => 'template',
                        'key' => 'trackingId',
                        'value' => '{{' . self::VARIABLE_UA_TRACKING . '}}'
                    )
                )
            )
        );

        return $tags;
    }

    public function getProductPageTags($triggers, $ipAnonymization, $displayAdvertising)
    {
        $tags = array
        (
            self::TAG_PRODUCT_PAGE => array
            (
                'name' => self::TAG_PRODUCT_PAGE,
                'firingTriggerId' => array
                (
                    $triggers[self::TRIGGER_PRODUCT_PAGE]
                ),
                'type' => self::TYPE_TAG_UA,
                'tagFiringOption' => 'oncePerEvent',
                'parameter' => array
                (
                    array
                    (
                        'type' => 'boolean',
                        'key' => 'nonInteraction',
                        'value' => "false"
                    ),
                    array
                    (
                        'type' => 'boolean',
                        'key' => 'useEcommerceDataLayer',
                        'value' => "true"
                    ),
                    array
                    (
                        'type' => 'template',
                        'key' => 'eventCategory',
                        'value' => 'Ecommerce'
                    ),
                    array
                    (
                        'type' => 'template',
                        'key' => 'trackType',
                        'value' => 'TRACK_EVENT'
                    ),
                    array
                    (
                        'type' => 'template',
                        'key' => 'eventAction',
                        'value' => 'Product'
                    ),
                    array
                    (
                        'type' => 'boolean',
                        'key' => 'enableEcommerce',
                        'value' => "true"
                    ),
                    array
                    (
                        'type' => 'boolean',
                        'key' => 'overrideGaSettings',
                        'value' => "true"
                    ),
                    array
                    (
                        'type' => 'boolean',
                        'key' => 'doubleClick',
                        'value' => $displayAdvertising
                    ),
                    array
                    (
                        'type' => 'template',
                        'key' => 'eventLabel',
                        'value' => '{{' . self::VARIABLE_EVENTLABEL . '}}'
                    ),
                    array
                    (
                        'type' => 'template',
                        'key' => 'trackingId',
                        'value' => '{{' . self::VARIABLE_UA_TRACKING . '}}'
                    )
                )
            )
        );

        return $tags;
    }

    public function getRegistrationTags($triggers, $ipAnonymization, $displayAdvertising)
    {
        $tags = array
        (
            self::TAG_REGISTRATION_PAGE => array
            (
                'name' => self::TAG_REGISTRATION_PAGE,
                'firingTriggerId' => array
                (
                    $triggers[self::TRIGGER_REGISTRATION_PAGE]
                ),
                'type' => self::TYPE_TAG_UA,
                'tagFiringOption' => 'oncePerEvent',
                'parameter' => array
                (
                    array
                    (
                        'type' => 'boolean',
                        'key' => 'nonInteraction',
                        'value' => "false"
                    ),
                    array
                    (
                        'type' => 'boolean',
                        'key' => 'useEcommerceDataLayer',
                        'value' => "true"
                    ),
                    array
                    (
                        'type' => 'template',
                        'key' => 'eventCategory',
                        'value' => 'Ecommerce'
                    ),
                    array
                    (
                        'type' => 'template',
                        'key' => 'trackType',
                        'value' => 'TRACK_EVENT'
                    ),
                    array
                    (
                        'type' => 'template',
                        'key' => 'eventAction',
                        'value' => 'Registration'
                    ),
                    array
                    (
                        'type' => 'boolean',
                        'key' => 'enableEcommerce',
                        'value' => "true"
                    ),
                    array
                    (
                        'type' => 'boolean',
                        'key' => 'overrideGaSettings',
                        'value' => "true"
                    ),
                    array
                    (
                        'type' => 'boolean',
                        'key' => 'doubleClick',
                        'value' => $displayAdvertising
                    ),
                    array
                    (
                        'type' => 'template',
                        'key' => 'eventLabel',
                        'value' => '{{' . self::VARIABLE_EVENTLABEL . '}}'
                    ),
                    array
                    (
                        'type' => 'template',
                        'key' => 'trackingId',
                        'value' => '{{' . self::VARIABLE_UA_TRACKING . '}}'
                    )
                )
            )
        );

        return $tags;
    }

    public function getNewsletterTags($triggers, $ipAnonymization, $displayAdvertising)
    {
        $tags = array
        (
            self::TAG_NEWSLETTER_PAGE => array
            (
                'name' => self::TAG_NEWSLETTER_PAGE,
                'firingTriggerId' => array
                (
                    $triggers[self::TRIGGER_NEWSLETTER_PAGE]
                ),
                'type' => self::TYPE_TAG_UA,
                'tagFiringOption' => 'oncePerEvent',
                'parameter' => array
                (
                    array
                    (
                        'type' => 'boolean',
                        'key' => 'nonInteraction',
                        'value' => "false"
                    ),
                    array
                    (
                        'type' => 'boolean',
                        'key' => 'useEcommerceDataLayer',
                        'value' => "true"
                    ),
                    array
                    (
                        'type' => 'template',
                        'key' => 'eventCategory',
                        'value' => 'Ecommerce'
                    ),
                    array
                    (
                        'type' => 'template',
                        'key' => 'trackType',
                        'value' => 'TRACK_EVENT'
                    ),
                    array
                    (
                        'type' => 'template',
                        'key' => 'eventAction',
                        'value' => 'Newsletter'
                    ),
                    array
                    (
                        'type' => 'boolean',
                        'key' => 'enableEcommerce',
                        'value' => "true"
                    ),
                    array
                    (
                        'type' => 'boolean',
                        'key' => 'overrideGaSettings',
                        'value' => "true"
                    ),
                    array
                    (
                        'type' => 'boolean',
                        'key' => 'doubleClick',
                        'value' => $displayAdvertising
                    ),
                    array
                    (
                        'type' => 'template',
                        'key' => 'eventLabel',
                        'value' => '{{' . self::VARIABLE_EVENTLABEL . '}}'
                    ),
                    array
                    (
                        'type' => 'template',
                        'key' => 'trackingId',
                        'value' => '{{' . self::VARIABLE_UA_TRACKING . '}}'
                    )
                )
            )
        );

        return $tags;
    }

    /**
     * Return list of tags for api creation
     * @param array $triggers
     * @param bool $ipAnonymization
     * @param bool $displayAdvertising
     * @return array
     */
    protected function _getTags($triggers, $ipAnonymization, $displayAdvertising)
    {
        $tags = array
        (
            self::TAG_PRODUCT_EVENT_CLICK => array
            (
                'name' => self::TAG_PRODUCT_EVENT_CLICK,
                'firingTriggerId' => array
                (
                    $triggers[self::TRIGGER_PRODUCT_CLICK]
                ),
                'type' => self::TYPE_TAG_UA,
                'tagFiringOption' => 'oncePerEvent',
                'parameter' => array
                (
                    array
                    (
                        'type' => 'boolean',
                        'key' => 'nonInteraction',
                        'value' => "false"
                    ),
                    array
                    (
                        'type' => 'boolean',
                        'key' => 'useEcommerceDataLayer',
                        'value' => "true"
                    ),
                    array
                    (
                        'type' => 'boolean',
                        'key' => 'doubleClick',
                        'value' => $displayAdvertising
                    ),
                    array
                    (
                        'type' => 'boolean',
                        'key' => 'setTrackerName',
                        'value' => "false"
                    ),
                    array
                    (
                        'type' => 'boolean',
                        'key' => 'useDebugVersion',
                        'value' => "false"
                    ),
                    array
                    (
                        'type' => 'template',
                        'key' => 'eventCategory',
                        'value' => 'Ecommerce'
                    ),
                    array
                    (
                        'type' => 'template',
                        'key' => 'trackType',
                        'value' => 'TRACK_EVENT'
                    ),
                    array
                    (
                        'type' => 'boolean',
                        'key' => 'enableLinkId',
                        'value' => "false"
                    ),
                    array
                    (
                        'type' => 'template',
                        'key' => 'eventAction',
                        'value' => 'Product Click'
                    ),
                    array
                    (
                        'type' => 'boolean',
                        'key' => 'enableEcommerce',
                        'value' => "true"
                    ),
                    array
                    (
                        'type' => 'boolean',
                        'key' => 'overrideGaSettings',
                        'value' => "true"
                    ),
                    array
                    (
                        'type' => 'template',
                        'key' => 'eventLabel',
                        'value' => '{{' . self::VARIABLE_EVENTLABEL . '}}'
                    ),
                    array
                    (
                        'type' => 'template',
                        'key' => 'trackingId',
                        'value' => '{{' . self::VARIABLE_UA_TRACKING . '}}'
                    )
                )
            ),
            self::TAG_PRODUCT_EVENT_ADD_TO_CART => array
            (
                'name' => self::TAG_PRODUCT_EVENT_ADD_TO_CART,
                'firingTriggerId' => array
                (
                    $triggers[self::TRIGGER_ADD_TO_CART]
                ),
                'type' => self::TYPE_TAG_UA,
                'tagFiringOption' => 'oncePerEvent',
                'parameter' => array
                (
                    array
                    (
                        'type' => 'boolean',
                        'key' => 'nonInteraction',
                        'value' => "false"
                    ),
                    array
                    (
                        'type' => 'boolean',
                        'key' => 'useEcommerceDataLayer',
                        'value' => "true"
                    ),
                    array
                    (
                        'type' => 'boolean',
                        'key' => 'doubleClick',
                        'value' => $displayAdvertising
                    ),
                    array
                    (
                        'type' => 'boolean',
                        'key' => 'setTrackerName',
                        'value' => "false"
                    ),
                    array
                    (
                        'type' => 'boolean',
                        'key' => 'useDebugVersion',
                        'value' => "false"
                    ),
                    array
                    (
                        'type' => 'template',
                        'key' => 'eventCategory',
                        'value' => 'Ecommerce'
                    ),
                    array
                    (
                        'type' => 'template',
                        'key' => 'trackType',
                        'value' => 'TRACK_EVENT'
                    ),
                    array
                    (
                        'type' => 'boolean',
                        'key' => 'enableLinkId',
                        'value' => "false"
                    ),
                    array
                    (
                        'type' => 'template',
                        'key' => 'eventAction',
                        'value' => 'Add to Cart'
                    ),
                    array
                    (
                        'type' => 'boolean',
                        'key' => 'enableEcommerce',
                        'value' => "true"
                    ),
                    array
                    (
                        'type' => 'boolean',
                        'key' => 'overrideGaSettings',
                        'value' => "true"
                    ),
                    array
                    (
                        'type' => 'template',
                        'key' => 'evenValue',
                        'value' => '{{' . self::VARIABLE_EVENTVALUE . '}}'
                    ),
                    array
                    (
                        'type' => 'template',
                        'key' => 'eventLabel',
                        'value' => '{{' . self::VARIABLE_EVENTLABEL . '}}'
                    ),
                    array
                    (
                        'type' => 'template',
                        'key' => 'trackingId',
                        'value' => '{{' . self::VARIABLE_UA_TRACKING . '}}'
                    )
                )
            ),
            self::TAG_PRODUCT_EVENT_REMOVE_FROM_CART => array
            (
                'name' => self::TAG_PRODUCT_EVENT_REMOVE_FROM_CART,
                'firingTriggerId' => array
                (
                    $triggers[self::TRIGGER_REMOVE_FROM_CART]
                ),
                'type' => self::TYPE_TAG_UA,
                'tagFiringOption' => 'oncePerEvent',
                'parameter' => array
                (
                    array
                    (
                        'type' => 'boolean',
                        'key' => 'nonInteraction',
                        'value' => "false"
                    ),
                    array
                    (
                        'type' => 'boolean',
                        'key' => 'useEcommerceDataLayer',
                        'value' => "true"
                    ),
                    array
                    (
                        'type' => 'boolean',
                        'key' => 'doubleClick',
                        'value' => $displayAdvertising
                    ),
                    array
                    (
                        'type' => 'boolean',
                        'key' => 'setTrackerName',
                        'value' => "false"
                    ),
                    array
                    (
                        'type' => 'boolean',
                        'key' => 'useDebugVersion',
                        'value' => "false"
                    ),
                    array
                    (
                        'type' => 'template',
                        'key' => 'eventCategory',
                        'value' => 'Ecommerce'
                    ),
                    array
                    (
                        'type' => 'template',
                        'key' => 'trackType',
                        'value' => 'TRACK_EVENT'
                    ),
                    array
                    (
                        'type' => 'boolean',
                        'key' => 'enableLinkId',
                        'value' => "false"
                    ),
                    array
                    (
                        'type' => 'template',
                        'key' => 'eventAction',
                        'value' => 'Remove from Cart'
                    ),
                    array
                    (
                        'type' => 'boolean',
                        'key' => 'enableEcommerce',
                        'value' => "true"
                    ),
                    array
                    (
                        'type' => 'boolean',
                        'key' => 'enableLinkId',
                        'value' => "false"
                    ),
                    array
                    (
                        'type' => 'boolean',
                        'key' => 'overrideGaSettings',
                        'value' => "true"
                    ),
                    array
                    (
                        'type' => 'template',
                        'key' => 'evenValue',
                        'value' => '{{' . self::VARIABLE_EVENTVALUE . '}}'
                    ),
                    array
                    (
                        'type' => 'template',
                        'key' => 'eventLabel',
                        'value' => '{{' . self::VARIABLE_EVENTLABEL . '}}'
                    ),
                    array
                    (
                        'type' => 'template',
                        'key' => 'trackingId',
                        'value' => '{{' . self::VARIABLE_UA_TRACKING . '}}'
                    )
                )
            ),
            self::TAG_PRODUCT_EVENT_PRODUCT_IMPRESSIONS => array
            (
                'name' => self::TAG_PRODUCT_EVENT_PRODUCT_IMPRESSIONS,
                'firingTriggerId' => array
                (
                    $triggers[self::TRIGGER_EVENT_IMPRESSION]
                ),
                'type' => self::TYPE_TAG_UA,
                'tagFiringOption' => 'oncePerEvent',
                'parameter' => array
                (
                    array
                    (
                        'type' => 'boolean',
                        'key' => 'nonInteraction',
                        'value' => "true"
                    ),
                    array
                    (
                        'type' => 'boolean',
                        'key' => 'useEcommerceDataLayer',
                        'value' => "true"
                    ),
                    array
                    (
                        'type' => 'boolean',
                        'key' => 'doubleClick',
                        'value' => $displayAdvertising
                    ),
                    array
                    (
                        'type' => 'boolean',
                        'key' => 'setTrackerName',
                        'value' => "false"
                    ),
                    array
                    (
                        'type' => 'boolean',
                        'key' => 'useDebugVersion',
                        'value' => "false"
                    ),
                    array
                    (
                        'type' => 'template',
                        'key' => 'eventCategory',
                        'value' => 'Ecommerce'
                    ),
                    array
                    (
                        'type' => 'template',
                        'key' => 'trackType',
                        'value' => 'TRACK_EVENT'
                    ),
                    array
                    (
                        'type' => 'boolean',
                        'key' => 'enableLinkId',
                        'value' => "false"
                    ),
                    array
                    (
                        'type' => 'template',
                        'key' => 'eventAction',
                        'value' => 'Impression'
                    ),
                    array
                    (
                        'type' => 'boolean',
                        'key' => 'enableEcommerce',
                        'value' => "true"
                    ),
                    array
                    (
                        'type' => 'boolean',
                        'key' => 'overrideGaSettings',
                        'value' => "true"
                    ),
                    array
                    (
                        'type' => 'template',
                        'key' => 'eventLabel',
                        'value' => '{{' . self::VARIABLE_EVENTLABEL . '}}'
                    ),
                    array
                    (
                        'type' => 'template',
                        'key' => 'trackingId',
                        'value' => '{{' . self::VARIABLE_UA_TRACKING . '}}'
                    )
                )
            ),
            self::TAG_GOOGLE_ANALYTICS => array
            (
                'name' => self::TAG_GOOGLE_ANALYTICS,
                'firingTriggerId' => array
                (
                    $triggers[self::TRIGGER_ALL_PAGES]
                ),
                'tagFiringOption' => 'oncePerLoad',
                'type' => self::TYPE_TAG_UA,
                'parameter' => array
                (
                    array
                    (
                        'type' => 'boolean',
                        'key' => 'useEcommerceDataLayer',
                        'value' => "true"
                    ),
                    array
                    (
                        'type' => 'boolean',
                        'key' => 'doubleClick',
                        'value' => $displayAdvertising
                    ),
                    array
                    (
                        'type' => 'boolean',
                        'key' => 'setTrackerName',
                        'value' => "false"
                    ),
                    array
                    (
                        'type' => 'boolean',
                        'key' => 'useDebugVersion',
                        'value' => "false"
                    ),
                    array
                    (
                        'type' => 'boolean',
                        'key' => 'useHashAutoLink',
                        'value' => "false"
                    ),
                    array
                    (
                        'type' => 'template',
                        'key' => 'trackType',
                        'value' => 'TRACK_PAGEVIEW'
                    ),
                    array(
                        'type' => 'boolean',
                        'key' => 'decorateFormsAutoLink',
                        'value' => "false"
                    ),
                    array(
                        'type' => 'boolean',
                        'key' => 'enableLinkId',
                        'value' => "false"
                    ),
                    array(
                        'type' => 'boolean',
                        'key' => 'enableEcommerce',
                        'value' => "true"
                    ),
                    array
                    (
                        'type' => 'boolean',
                        'key' => 'overrideGaSettings',
                        'value' => "true"
                    ),
                    array
                    (
                        'type' => 'template',
                        'key' => 'trackingId',
                        'value' => '{{' . self::VARIABLE_UA_TRACKING . '}}'
                    ),
                    array
                    (
                        'type' => 'list',
                        'key' => 'fieldsToSet',
                        'list' => array
                        (
                            array
                            (
                                'type' => 'map',
                                'map' => array
                                (
                                    array
                                    (
                                        'type' => 'template',
                                        'key' => 'fieldName',
                                        'value' => 'anonymizeIp'
                                    ),
                                    array
                                    (
                                        'type' => 'template',
                                        'key' => 'value',
                                        'value' => $ipAnonymization
                                    )
                                )
                            )
                        )
                    )
                )
            ),
            self::TAG_CHECKOUT_STEP_OPTION => array
            (
                'name' => self::TAG_CHECKOUT_STEP_OPTION,
                'firingTriggerId' => array
                (
                    $triggers[self::TRIGGER_CHECKOUT_OPTION]
                ),
                'type' => self::TYPE_TAG_UA,
                'tagFiringOption' => 'oncePerEvent',
                'parameter' => array
                (
                    array
                    (
                        'type' => 'boolean',
                        'key' => 'nonInteraction',
                        'value' => "false"
                    ),
                    array
                    (
                        'type' => 'boolean',
                        'key' => 'useEcommerceDataLayer',
                        'value' => "true"
                    ),
                    array
                    (
                        'type' => 'template',
                        'key' => 'eventCategory',
                        'value' => 'Ecommerce'
                    ),
                    array
                    (
                        'type' => 'template',
                        'key' => 'trackType',
                        'value' => 'TRACK_EVENT'
                    ),
                    array
                    (
                        'type' => 'template',
                        'key' => 'eventAction',
                        'value' => 'Checkout Option'
                    ),
                    array
                    (
                        'type' => 'boolean',
                        'key' => 'enableEcommerce',
                        'value' => "true"
                    ),
                    array
                    (
                        'type' => 'boolean',
                        'key' => 'overrideGaSettings',
                        'value' => "true"
                    ),
                    array
                    (
                        'type' => 'boolean',
                        'key' => 'doubleClick',
                        'value' => $displayAdvertising
                    ),
                    array
                    (
                        'type' => 'template',
                        'key' => 'eventLabel',
                        'value' => '{{' . self::VARIABLE_EVENTLABEL . '}}'
                    ),
                    array
                    (
                        'type' => 'template',
                        'key' => 'trackingId',
                        'value' => '{{' . self::VARIABLE_UA_TRACKING . '}}'
                    )
                )
            ),
            self::TAG_CHECKOUT_STEP => array
            (
                'name' => self::TAG_CHECKOUT_STEP,
                'firingTriggerId' => array
                (
                    $triggers[self::TRIGGER_CHECKOUT_STEPS]
                ),
                'type' => self::TYPE_TAG_UA,
                'tagFiringOption' => 'oncePerEvent',
                'parameter' => array
                (
                    array
                    (
                        'type' => 'boolean',
                        'key' => 'nonInteraction',
                        'value' => "false"
                    ),
                    array
                    (
                        'type' => 'boolean',
                        'key' => 'useEcommerceDataLayer',
                        'value' => "true"
                    ),
                    array
                    (
                        'type' => 'template',
                        'key' => 'eventCategory',
                        'value' => 'Ecommerce'
                    ),
                    array
                    (
                        'type' => 'template',
                        'key' => 'trackType',
                        'value' => 'TRACK_EVENT'
                    ),
                    array
                    (
                        'type' => 'template',
                        'key' => 'eventAction',
                        'value' => 'Checkout'
                    ),
                    array
                    (
                        'type' => 'boolean',
                        'key' => 'enableEcommerce',
                        'value' => "true"
                    ),
                    array
                    (
                        'type' => 'boolean',
                        'key' => 'overrideGaSettings',
                        'value' => "true"
                    ),
                    array
                    (
                        'type' => 'boolean',
                        'key' => 'doubleClick',
                        'value' => $displayAdvertising
                    ),
                    array
                    (
                        'type' => 'template',
                        'key' => 'eventLabel',
                        'value' => '{{' . self::VARIABLE_EVENTLABEL . '}}'
                    ),
                    array
                    (
                        'type' => 'template',
                        'key' => 'trackingId',
                        'value' => '{{' . self::VARIABLE_UA_TRACKING . '}}'
                    )
                )
            ),
            self::TAG_PROMOTION_IMPRESSION => array
            (
                'name' => self::TAG_PROMOTION_IMPRESSION,
                'firingTriggerId' => array
                (
                    $triggers[self::TRIGGER_PROMOTION_VIEW]
                ),
                'type' => self::TYPE_TAG_UA,
                'tagFiringOption' => 'oncePerEvent',
                'parameter' => array
                (
                    array
                    (
                        'type' => 'boolean',
                        'key' => 'nonInteraction',
                        'value' => "true"
                    ),
                    array
                    (
                        'type' => 'boolean',
                        'key' => 'useEcommerceDataLayer',
                        'value' => "true"
                    ),
                    array
                    (
                        'type' => 'template',
                        'key' => 'eventCategory',
                        'value' => 'Promotion'
                    ),
                    array
                    (
                        'type' => 'template',
                        'key' => 'trackType',
                        'value' => 'TRACK_EVENT'
                    ),
                    array
                    (
                        'type' => 'template',
                        'key' => 'eventAction',
                        'value' => 'Promotion View'
                    ),
                    array
                    (
                        'type' => 'boolean',
                        'key' => 'enableEcommerce',
                        'value' => "true"
                    ),
                    array
                    (
                        'type' => 'boolean',
                        'key' => 'overrideGaSettings',
                        'value' => "true"
                    ),
                    array
                    (
                        'type' => 'boolean',
                        'key' => 'doubleClick',
                        'value' => $displayAdvertising
                    ),
                    array
                    (
                        'type' => 'template',
                        'key' => 'eventLabel',
                        'value' => '{{' . self::VARIABLE_EVENTLABEL . '}}'
                    ),
                    array
                    (
                        'type' => 'template',
                        'key' => 'trackingId',
                        'value' => '{{' . self::VARIABLE_UA_TRACKING . '}}'
                    )
                )
            ),
            self::TAG_PROMOTION_CLICK => array
            (
                'name' => self::TAG_PROMOTION_CLICK,
                'firingTriggerId' => array
                (
                    $triggers[self::TRIGGER_PROMOTION_CLICK]
                ),
                'type' => self::TYPE_TAG_UA,
                'tagFiringOption' => 'oncePerEvent',
                'parameter' => array
                (
                    array
                    (
                        'type' => 'boolean',
                        'key' => 'nonInteraction',
                        'value' => "false"
                    ),
                    array
                    (
                        'type' => 'boolean',
                        'key' => 'useEcommerceDataLayer',
                        'value' => "true"
                    ),
                    array
                    (
                        'type' => 'template',
                        'key' => 'eventCategory',
                        'value' => 'Ecommerce'
                    ),
                    array
                    (
                        'type' => 'template',
                        'key' => 'trackType',
                        'value' => 'TRACK_EVENT'
                    ),
                    array
                    (
                        'type' => 'template',
                        'key' => 'eventAction',
                        'value' => 'Promotion Click'
                    ),
                    array
                    (
                        'type' => 'boolean',
                        'key' => 'enableEcommerce',
                        'value' => "true"
                    ),
                    array
                    (
                        'type' => 'boolean',
                        'key' => 'overrideGaSettings',
                        'value' => "true"
                    ),
                    array
                    (
                        'type' => 'boolean',
                        'key' => 'doubleClick',
                        'value' => $displayAdvertising
                    ),
                    array
                    (
                        'type' => 'template',
                        'key' => 'eventLabel',
                        'value' => '{{' . self::VARIABLE_EVENTLABEL . '}}'
                    ),
                    array
                    (
                        'type' => 'template',
                        'key' => 'trackingId',
                        'value' => '{{' . self::VARIABLE_UA_TRACKING . '}}'
                    )
                )
            ),
            self::TAG_PRODUCT_EVENT_ADD_TO_WISHLIST => array
            (
                'name' => self::TAG_PRODUCT_EVENT_ADD_TO_WISHLIST,
                'firingTriggerId' => array
                (
                    $triggers[self::TRIGGER_ADD_TO_WISHLIST]
                ),
                'type' => self::TYPE_TAG_UA,
                'tagFiringOption' => 'oncePerEvent',
                'parameter' => array
                (
                    array
                    (
                        'type' => 'boolean',
                        'key' => 'nonInteraction',
                        'value' => "false"
                    ),
                    array
                    (
                        'type' => 'boolean',
                        'key' => 'useEcommerceDataLayer',
                        'value' => "true"
                    ),
                    array
                    (
                        'type' => 'template',
                        'key' => 'eventCategory',
                        'value' => 'Ecommerce'
                    ),
                    array
                    (
                        'type' => 'template',
                        'key' => 'trackType',
                        'value' => 'TRACK_EVENT'
                    ),
                    array
                    (
                        'type' => 'template',
                        'key' => 'eventAction',
                        'value' => 'Wishlist'
                    ),
                    array
                    (
                        'type' => 'boolean',
                        'key' => 'enableEcommerce',
                        'value' => "true"
                    ),
                    array
                    (
                        'type' => 'boolean',
                        'key' => 'overrideGaSettings',
                        'value' => "true"
                    ),
                    array
                    (
                        'type' => 'boolean',
                        'key' => 'doubleClick',
                        'value' => $displayAdvertising
                    ),
                    array
                    (
                        'type' => 'template',
                        'key' => 'eventLabel',
                        'value' => '{{' . self::VARIABLE_EVENTLABEL . '}}'
                    ),
                    array
                    (
                        'type' => 'template',
                        'key' => 'trackingId',
                        'value' => '{{' . self::VARIABLE_UA_TRACKING . '}}'
                    )
                )
            ),
            self::TAG_PRODUCT_EVENT_ADD_TO_COMPARE => array
            (
                'name' => self::TAG_PRODUCT_EVENT_ADD_TO_COMPARE,
                'firingTriggerId' => array
                (
                    $triggers[self::TRIGGER_ADD_TO_COMPARE]
                ),
                'type' => self::TYPE_TAG_UA,
                'tagFiringOption' => 'oncePerEvent',
                'parameter' => array
                (
                    array
                    (
                        'type' => 'boolean',
                        'key' => 'nonInteraction',
                        'value' => "false"
                    ),
                    array
                    (
                        'type' => 'boolean',
                        'key' => 'useEcommerceDataLayer',
                        'value' => "true"
                    ),
                    array
                    (
                        'type' => 'template',
                        'key' => 'eventCategory',
                        'value' => 'Ecommerce'
                    ),
                    array
                    (
                        'type' => 'template',
                        'key' => 'trackType',
                        'value' => 'TRACK_EVENT'
                    ),
                    array
                    (
                        'type' => 'template',
                        'key' => 'eventAction',
                        'value' => 'Compare'
                    ),
                    array
                    (
                        'type' => 'boolean',
                        'key' => 'enableEcommerce',
                        'value' => "true"
                    ),
                    array
                    (
                        'type' => 'boolean',
                        'key' => 'overrideGaSettings',
                        'value' => "true"
                    ),
                    array
                    (
                        'type' => 'boolean',
                        'key' => 'doubleClick',
                        'value' => $displayAdvertising
                    ),
                    array
                    (
                        'type' => 'template',
                        'key' => 'eventLabel',
                        'value' => '{{' . self::VARIABLE_EVENTLABEL . '}}'
                    ),
                    array
                    (
                        'type' => 'template',
                        'key' => 'trackingId',
                        'value' => '{{' . self::VARIABLE_UA_TRACKING . '}}'
                    )
                )
            )
        );

        return $tags;
    }

    /**
     * Return list of triggers for api creation
     * @return array
     */
    protected function _getTriggers()
    {
        $triggers = array
        (
            self::TRIGGER_PRODUCT_CLICK => array
            (
                'name' => self::TRIGGER_PRODUCT_CLICK,
                'type' => self::TYPE_TRIGGER_CUSTOM_EVENT,
                'customEventFilter' => array
                (
                    array
                    (
                        'type' => 'equals',
                        'parameter' => array
                        (
                            array
                            (
                                'type' => 'template',
                                'key' => 'arg0',
                                'value' => '{{_event}}'
                            ),
                            array
                            (
                                'type' => 'template',
                                'key' => 'arg1',
                                'value' => 'productClick'
                            )
                        )
                    )
                )
            ),
            self::TRIGGER_GTM_DOM => array
            (
                'name' => self::TRIGGER_GTM_DOM,
                'type' => self::TYPE_TRIGGER_DOMREADY
            ),
            self::TRIGGER_ADD_TO_CART => array
            (
                'name' => self::TRIGGER_ADD_TO_CART,
                'type' => self::TYPE_TRIGGER_CUSTOM_EVENT,
                'customEventFilter' => array
                (
                    array
                    (
                        'type' => 'equals',
                        'parameter' => array
                        (
                            array
                            (
                                'type' => 'template',
                                'key' => 'arg0',
                                'value' => '{{_event}}'
                            ),
                            array
                            (
                                'type' => 'template',
                                'key' => 'arg1',
                                'value' => 'addToCart'
                            )
                        )
                    )
                )
            ),
            self::TRIGGER_REMOVE_FROM_CART => array
            (
                'name' => self::TRIGGER_REMOVE_FROM_CART,
                'type' => self::TYPE_TRIGGER_CUSTOM_EVENT,
                'customEventFilter' => array
                (
                    array
                    (
                        'type' => 'equals',
                        'parameter' => array
                        (
                            array
                            (
                                'type' => 'template',
                                'key' => 'arg0',
                                'value' => '{{_event}}'
                            ),
                            array
                            (
                                'type' => 'template',
                                'key' => 'arg1',
                                'value' => 'removeFromCart'
                            )
                        )
                    )
                )
            ),
            self::TRIGGER_ALL_PAGES => array
            (
                'name' => self::TRIGGER_ALL_PAGES,
                'type' => self::TYPE_TRIGGER_PAGEVIEW
            ),
            self::TRIGGER_EVENT_IMPRESSION => array
            (
                'name' => self::TRIGGER_EVENT_IMPRESSION,
                'type' => self::TYPE_TRIGGER_CUSTOM_EVENT,
                'customEventFilter' => array
                (
                    array
                    (
                        'type' => 'equals',
                        'parameter' => array
                        (
                            array
                            (
                                'type' => 'template',
                                'key' => 'arg0',
                                'value' => '{{_event}}'
                            ),
                            array
                            (
                                'type' => 'template',
                                'key' => 'arg1',
                                'value' => 'impression'
                            )
                        )
                    )
                )
            ),
            self::TRIGGER_PROMOTION_CLICK => array
            (
                'name' => self::TRIGGER_PROMOTION_CLICK,
                'type' => self::TYPE_TRIGGER_CUSTOM_EVENT,
                'customEventFilter' => array
                (
                    array
                    (
                        'type' => 'equals',
                        'parameter' => array
                        (
                            array
                            (
                                'type' => 'template',
                                'key' => 'arg0',
                                'value' => '{{_event}}'
                            ),
                            array
                            (
                                'type' => 'template',
                                'key' => 'arg1',
                                'value' => 'promotionClick'
                            )
                        )
                    )
                )
            ),
            self::TRIGGER_CHECKOUT_OPTION => array
            (
                'name' => self::TRIGGER_CHECKOUT_OPTION,
                'type' => self::TYPE_TRIGGER_CUSTOM_EVENT,
                'customEventFilter' => array
                (
                    array
                    (
                        'type' => 'equals',
                        'parameter' => array
                        (
                            array
                            (
                                'type' => 'template',
                                'key' => 'arg0',
                                'value' => '{{_event}}'
                            ),
                            array
                            (
                                'type' => 'template',
                                'key' => 'arg1',
                                'value' => 'checkoutOption'
                            )
                        )
                    )
                )
            ),
            self::TRIGGER_CHECKOUT_STEPS => array
            (
                'name' => self::TRIGGER_CHECKOUT_STEPS,
                'type' => self::TYPE_TRIGGER_CUSTOM_EVENT,
                'customEventFilter' => array
                (
                    array
                    (
                        'type' => 'equals',
                        'parameter' => array
                        (
                            array
                            (
                                'type' => 'template',
                                'key' => 'arg0',
                                'value' => '{{_event}}'
                            ),
                            array
                            (
                                'type' => 'template',
                                'key' => 'arg1',
                                'value' => 'checkout'
                            )
                        )
                    )
                )
            ),
            self::TRIGGER_PROMOTION_VIEW => array
            (
                'name' => self::TRIGGER_PROMOTION_VIEW,
                'type' => self::TYPE_TRIGGER_CUSTOM_EVENT,
                'customEventFilter' => array
                (
                    array
                    (
                        'type' => 'equals',
                        'parameter' => array
                        (
                            array
                            (
                                'type' => 'template',
                                'key' => 'arg0',
                                'value' => '{{_event}}'
                            ),
                            array
                            (
                                'type' => 'template',
                                'key' => 'arg1',
                                'value' => 'promotionView'
                            )
                        )
                    )
                )
            ),
            self::TRIGGER_ADD_TO_WISHLIST => array
            (
                'name' => self::TRIGGER_ADD_TO_WISHLIST,
                'type' => self::TYPE_TRIGGER_CUSTOM_EVENT,
                'customEventFilter' => array
                (
                    array
                    (
                        'type' => 'equals',
                        'parameter' => array
                        (
                            array
                            (
                                'type' => 'template',
                                'key' => 'arg0',
                                'value' => '{{_event}}'
                            ),
                            array
                            (
                                'type' => 'template',
                                'key' => 'arg1',
                                'value' => 'addToWishlist'
                            )
                        )
                    )
                )
            ),
            self::TRIGGER_ADD_TO_COMPARE => array
            (
                'name' => self::TRIGGER_ADD_TO_COMPARE,
                'type' => self::TYPE_TRIGGER_CUSTOM_EVENT,
                'customEventFilter' => array
                (
                    array
                    (
                        'type' => 'equals',
                        'parameter' => array
                        (
                            array
                            (
                                'type' => 'template',
                                'key' => 'arg0',
                                'value' => '{{_event}}'
                            ),
                            array
                            (
                                'type' => 'template',
                                'key' => 'arg1',
                                'value' => 'addToCompare'
                            )
                        )
                    )
                )
            )
        );
        return $triggers;
    }
}
