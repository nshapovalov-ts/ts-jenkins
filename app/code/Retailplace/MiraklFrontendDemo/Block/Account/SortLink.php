<?php

/**
 * Retailplace_MiraklFrontendDemo
 *
 * @copyright   Copyright (c) 2021 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Alexander Korsun <aleksandr@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\MiraklFrontendDemo\Block\Account;

use Magento\Customer\Model\Session;
use Magento\Framework\App\DefaultPathInterface;
use Magento\Framework\Stdlib\CookieManagerInterface;
use Magento\Framework\View\Element\Template\Context;
use Retailplace\MiraklFrontendDemo\Model\MessagesStatsRepository;

/**
 * Class for sortable links.
 */
class SortLink extends \Magento\Customer\Block\Account\SortLink
{
    const CSS_CLASS = "cssClass";
    const COUNT_BOX = "countBox";

    /**
     * @var CookieManagerInterface
     */
    private $cookieManager;

    /**
     * @var Session
     */
    private $customerSession;

    /**
     * Constructor
     *
     * @param Context $context
     * @param DefaultPathInterface $defaultPath
     * @param Session $customerSession
     * @param CookieManagerInterface $cookieManager
     * @param array $data
     */
    public function __construct(
        Context $context,
        DefaultPathInterface $defaultPath,
        Session $customerSession,
        CookieManagerInterface $cookieManager,
        array $data = []
    ) {
        parent::__construct($context, $defaultPath, $data);
        $this->customerSession = $customerSession;
        $this->cookieManager = $cookieManager;
    }

    /**
     * Get Count New Messages
     *
     * @return int|mixed
     */
    public function getCountNewMessages()
    {
        $value = $this->customerSession->getData('customer_new_message_counter');
        if (!isset($value)) {
            $value = $this->cookieManager->getCookie(MessagesStatsRepository::COOKIE_NAME);
        }

        if (!empty($value)) {
            return $value;
        }

        return 0;
    }

    /**
     * @return array|mixed|null
     */
    public function getCssClass()
    {
        return $this->getData(self::CSS_CLASS);
    }

    /**
     * @return bool
     */
    public function isCountBox(): bool
    {
        return $this->getData(self::COUNT_BOX) === true;
    }

}
