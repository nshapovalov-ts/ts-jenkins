<?php

/**
 * Retailplace_AuPost
 *
 * @copyright   Copyright (c) 2021 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Dmitriy Fionov <dmitriy@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\AuPost\Plugin;

use Magento\Customer\Model\Session as CustomerSession;
use Psr\Log\LoggerInterface;
use Sm\MegaMenu\Block\MegaMenu\View;

/**
 * Class MegamenuCache
 */
class MegamenuCache
{
    /** @var \Magento\Customer\Model\Session */
    private $customerSession;

    /** @var \Psr\Log\LoggerInterface */
    private $logger;

    /**
     * MegamenuCache constructor.
     *
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        CustomerSession $customerSession,
        LoggerInterface $logger
    ) {
        $this->customerSession = $customerSession;
        $this->logger = $logger;
    }

    /**
     * Add Customer Group Id to the config array since it used within cache key
     *
     * @param \Sm\MegaMenu\Block\MegaMenu\View $subject
     * @param string|array|int|null $result
     * @return string|array|int|null
     */
    public function after_getConfig(View $subject, $result)
    {
        if (is_array($result)) {
            try {
                $customerGroupId = $this->customerSession->getCustomerGroupId();
            } catch (\Exception $e) {
                $this->logger->error($e->getMessage());
                $customerGroupId = 0;
            }

            $result['customer_group_id'] = $customerGroupId;
        }

        return $result;
    }
}
