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

namespace Mageplaza\CustomerApproval\Plugin;

use Magento\Customer\Controller\Account\CreatePost;
use Magento\Customer\Model\ResourceModel\Customer\CollectionFactory as CusCollectFactory;
use Magento\Customer\Model\Session;
use Magento\Framework\App\Response\RedirectInterface;
use Magento\Framework\App\ResponseFactory;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\Stdlib\Cookie\FailureToSendException;
use Mageplaza\CustomerApproval\Helper\ApprovalAction as ApprovalActionHelper;
use Mageplaza\CustomerApproval\Model\Config\Source\TypeAction;
use Magento\Framework\Message\MessageInterface;

/**
 * Class CustomerCreatePost
 *
 * @package Mageplaza\CustomerApproval\Plugin
 */
class CustomerCreatePost
{
    /**
     * @var ManagerInterface
     */
    protected $messageManager;

    /**
     * @var RedirectFactory
     */
    protected $resultRedirectFactory;

    /**
     * @var RedirectInterface
     */
    protected $_redirect;

    /**
     * @var Session
     */
    protected $_customerSession;

    /**
     * @var ResponseFactory
     */
    private $_response;

    /**
     * @var CusCollectFactory
     */
    protected $_cusCollectFactory;

    /**
     * @var ResultFactory
     */
    private $resultFactory;

    /**
     * @var \Magento\Framework\Event\ManagerInterface
     */
    protected $_eventManager;

    /**
     * @var ApprovalActionHelper
     */
    protected $approvalActionHelper;

    /**
     * CustomerCreatePost constructor.
     *
     * @param ApprovalActionHelper $approvalActionHelper
     * @param ManagerInterface $messageManager
     * @param RedirectFactory $resultRedirectFactory
     * @param RedirectInterface $redirect
     * @param Session $customerSession
     * @param ResponseFactory $responseFactory
     * @param CusCollectFactory $cusCollectFactory
     * @param \Magento\Framework\Event\ManagerInterface $eventManager
     * @param ResultFactory $resultFactory
     */
    public function __construct(
        ApprovalActionHelper $approvalActionHelper,
        ManagerInterface $messageManager,
        RedirectFactory $resultRedirectFactory,
        RedirectInterface $redirect,
        Session $customerSession,
        ResponseFactory $responseFactory,
        CusCollectFactory $cusCollectFactory,
        \Magento\Framework\Event\ManagerInterface $eventManager,
        ResultFactory $resultFactory
    ) {
        $this->approvalActionHelper = $approvalActionHelper;
        $this->messageManager        = $messageManager;
        $this->resultRedirectFactory = $resultRedirectFactory;
        $this->_redirect             = $redirect;
        $this->_customerSession      = $customerSession;
        $this->_response             = $responseFactory;
        $this->_cusCollectFactory    = $cusCollectFactory;
        $this->resultFactory = $resultFactory;
        $this->_eventManager = $eventManager;
    }

    /**
     * @param CreatePost $createPost
     * @param $result
     *
     * @return mixed
     * @throws InputException
     * @throws LocalizedException
     * @throws NoSuchEntityException
     * @throws FailureToSendException
     */
    public function afterExecute(CreatePost $createPost, $result)
    {
        if (!$this->approvalActionHelper->isEnabled()) {
            return $result;
        }

        $customerId = null;
        $request    = $createPost->getRequest();
        $emailPost  = $request->getParam('email');
        if ($emailPost) {
            $cusCollectFactory = $this->_cusCollectFactory->create();
            $customerFilter    = $cusCollectFactory->addFieldToFilter('email', $emailPost)->setPageSize(1)->getFirstItem();
            $customerId        = $customerFilter->getId();
        }

        if ($customerId) {
            $customer = $this->approvalActionHelper->getCustomerById($customerId);
            $autoApproval = $this->approvalActionHelper->getAutoApproveConfig();
            $conditionallyApproved = false;
            $email = $customer->getEmail();
            if ($email) {
                $abn = $customer->getCustomAttribute('abn');
                if ($abn) {
                    $abn = $abn->getValue();
                    $isValidAbn = $this->approvalActionHelper->getRecordFromAbrNumber($abn);
                    $isValidDomain =  $this->approvalActionHelper->checkValidDomain($email);
                    if ($isValidAbn) {
                        $autoApproval = true;
                        if (!$isValidDomain) {
                            $conditionallyApproved = true;
                        }
                    }
                }
            }

            if ($autoApproval) {
                // case allow auto approve
                if ($_customer = $this->approvalActionHelper->approvalByCustomerId($customerId, $conditionallyApproved, TypeAction::OTHER)) {
                    $customer = $_customer;
                }
                // send email approve to customer
                $this->approvalActionHelper->emailApprovalAction($customer, 'approve');
                if ($conditionallyApproved) {
                    //Notify a message to customer
                    $this->messageManager->addMessage(
                        $this->messageManager->createMessage(MessageInterface::TYPE_SUCCESS)
                            ->setText(__('Congratulations, you have been conditionally approved, we will contact you soon to finalise the approval process.'))
                            ->setData(['view_type' => 'registering_approved'])
                    );
                }
            } else {
                // case not allow auto approve
                $actionRegister = false;
                if ($_customer = $this->approvalActionHelper->setApprovePendingById($customerId, $actionRegister)) {
                    $customer = $_customer;
                }

                $this->messageManager->addMessage(
                    $this->messageManager->createMessage(MessageInterface::TYPE_NOTICE)
                        ->setText(__($this->approvalActionHelper->getMessageAfterRegister()))
                        ->setData(['view_type' => 'registering_requires_approval'])
                );

                // send email notify to admin
                //$this->approvalActionHelper->emailNotifyAdmin($customer);
                // send email notify to customer
                //$this->approvalActionHelper->emailApprovalAction($customer, 'success');

                // force logout customer
                //$this->_customerSession->logout()
                //    ->setBeforeAuthUrl($this->_redirect->getRefererUrl())
                //    ->setLastCustomerId($customerId);

                // processCookieLogout
                //$this->approvalActionHelper->processCookieLogout();
                //return $result;
                $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
                $url = $this->approvalActionHelper->getUrl('customer/account/edit', []);
                $resultRedirect->setUrl($url);
                return $resultRedirect;
            }

            $this->_eventManager->dispatch(
                'customer_register_success_after_logout',
                ['account_controller' => $createPost, 'customer' => $customer]
            );

            // force redirect
            //$url = $this->helperData->getUrl('customer/account/login', ['_secure' => true]);
            $url = $_REQUEST['referrer_url'] ?? $this->approvalActionHelper->getBaseUrl();
            if (strpos($url, 'customer/account') !== false) {
                $url = $this->approvalActionHelper->getBaseUrl();
            }
            $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
            $resultRedirect->setUrl($url);
            return $resultRedirect;
        }

        return $result;
    }
}
