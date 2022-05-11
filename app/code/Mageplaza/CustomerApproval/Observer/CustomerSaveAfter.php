<?php
/**
 * Mageplaza
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Mageplaza.com license that is
 * available through the world-wide-web at this URL:
 * https://www.mageplaza.com/LICENSE.txt
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this extension to newer
 * version in the future.
 *
 * @category  Mageplaza
 * @package   Mageplaza_CustomerApproval
 * @copyright Copyright (c) Mageplaza (https://www.mageplaza.com/)
 * @license   https://www.mageplaza.com/LICENSE.txt
 */

namespace Mageplaza\CustomerApproval\Observer;

use Exception;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Mageplaza\CustomerApproval\Helper\ApprovalAction as ApprovalActionHelper;
use Mageplaza\CustomerApproval\Model\Config\Source\TypeAction;

/**
 * Class CustomerSaveAfter
 *
 * @package Mageplaza\CustomerApproval\Observer
 */
class CustomerSaveAfter implements ObserverInterface
{
    /**
     * @var ApprovalActionHelper
     */
    private $helperData;

    /**
     * CustomerSaveAfter constructor.
     *
     * @param ApprovalActionHelper $helperData
     */
    public function __construct(
        ApprovalActionHelper $helperData
    ) {
        $this->helperData = $helperData;
    }

    /**
     * @param Observer $observer
     *
     * @throws Exception
     */
    public function execute(Observer $observer)
    {
        $customer     = $observer->getEvent()->getCustomer();
        $autoApproval = $this->helperData->getAutoApproveConfig();
        $conditionallyApproved = false;
        if (!$this->helperData->isEnabledForWebsite($customer->getWebsiteId())) {
            return;
        }
        $customerId      = $customer->getId();
        $hasCustomerEdit = $this->helperData->hasCustomerEdit();
        // case create customer in adminhtml
        if (!$hasCustomerEdit && $customerId) {
            if ($email = $customer->getEmail()) {
                $abn = $customer->getCustomAttribute('abn');
                if ($abn) {
                    $abn = $abn->getValue();
                    $isValidAbn = $this->helperData->getRecordFromAbrNumber($abn);
//                    $isValidDomain =  $this->helperData->checkValidDomain($email);
                    if ($isValidAbn) {
                        $autoApproval = true;
//                        if (!$isValidDomain) {
//                            $conditionallyApproved = true;
//                        }
                    }
                }
            }
            if ($autoApproval) {
                $this->helperData->approvalByCustomerId($customerId, $conditionallyApproved, TypeAction::OTHER);
                $this->helperData->emailApprovalAction($customer, 'approve');
            } else {
                $actionRegister = false;
                $this->helperData->setApprovePendingById($customerId, $actionRegister);
                $this->helperData->emailNotifyAdmin($customer);
            }
        }
    }
}
