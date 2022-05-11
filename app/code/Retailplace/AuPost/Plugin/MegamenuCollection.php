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
use Retailplace\AuPost\Setup\Patch\Data\AddAuPostMenuItem;

/**
 * Class MegamenuCollection
 */
class MegamenuCollection
{
    /** @var \Magento\Customer\Model\Session */
    private $customerSession;

    /** @var \Psr\Log\LoggerInterface */
    private $logger;

    /**
     * MegamenuCollection constructor.
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
     * Add current session Customer Group Id to the Menu Items Collection on Frontend
     *
     * @param \Sm\MegaMenu\Model\ResourceModel\MenuItems\Collection $subject
     */
    public function beforeGetItemsByLv(\Sm\MegaMenu\Model\ResourceModel\MenuItems\Collection $subject)
    {
        try {
            $customerGroupId = $this->customerSession->getCustomerGroupId();
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
            $customerGroupId = 0;
        }

        $subject->addFieldToFilter(AddAuPostMenuItem::MEGAMENU_ITEM_CUSTOMER_GROUP_ID, [
            ['null' => true],
            ['eq' => 0],
            ['eq' => $customerGroupId],
        ]);
    }
}
