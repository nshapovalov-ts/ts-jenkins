<?php

/**
 * Retailplace_EmailConfirmation
 *
 * @copyright   Copyright (c) 2022 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Dmitriy Fionov <dmitriy@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\EmailConfirmation\Controller\Account;

use Magento\Customer\Controller\Account\Confirm as MagentoAccountConfirm;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Exception\StateException;

/**
 * Class Confirm
 */
class Confirm extends MagentoAccountConfirm
{
    /** @var \Magento\Framework\Stdlib\Cookie\CookieMetadataFactory */
    private $cookieMetadataFactory;

    /** @var \Magento\Framework\Stdlib\Cookie\PhpCookieManager */
    private $cookieMetadataManager;

    /**
     * Confirm customer account by id and confirmation key
     *
     * @return \Magento\Framework\Controller\Result\Redirect
     */
    public function execute()
    {
        /** @var \Magento\Framework\Controller\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);

        if ($this->session->isLoggedIn()) {
            $resultRedirect->setPath('*/*/');
            return $resultRedirect;
        }

        $customerId = $this->getRequest()->getParam('id', false);
        $key = $this->getRequest()->getParam('key', false);
        if (empty($customerId) || empty($key)) {
            $this->messageManager->addErrorMessage(__('Bad request.'));
            $url = $this->urlModel->getUrl('*/*/index', ['_secure' => true]);
            return $resultRedirect->setUrl($this->_redirect->error($url));
        }

        try {
            // log in and send greeting email
            $customerEmail = $this->customerRepository->getById($customerId)->getEmail();
            $customer = $this->customerAccountManagement->activate($customerEmail, $key);
            $this->session->setCustomerDataAsLoggedIn($customer);
            if ($this->getCookieManager()->getCookie('mage-cache-sessid')) {
                $metadata = $this->getCookieMetadataFactory()->createCookieMetadata();
                $metadata->setPath('/');
                $this->getCookieManager()->deleteCookie('mage-cache-sessid', $metadata);
            }
            $resultRedirect->setUrl($this->getSuccessRedirect());

            return $resultRedirect;
        } catch (StateException $e) {
            $this->messageManager->addException($e, __('This confirmation key is invalid or has expired.'));
        } catch (\Exception $e) {
            $this->messageManager->addException($e, __('There was an error confirming the account'));
        }

        $url = $this->urlModel->getUrl('*/*/index', ['_secure' => true]);

        return $resultRedirect->setUrl($this->_redirect->error($url));
    }

    /**
     * Retrieve cookie manager
     *
     * @deprecated 101.0.0
     * @return \Magento\Framework\Stdlib\Cookie\PhpCookieManager
     */
    private function getCookieManager()
    {
        if (!$this->cookieMetadataManager) {
            $this->cookieMetadataManager = \Magento\Framework\App\ObjectManager::getInstance()->get(
                \Magento\Framework\Stdlib\Cookie\PhpCookieManager::class
            );
        }
        return $this->cookieMetadataManager;
    }

    /**
     * Retrieve cookie metadata factory
     *
     * @deprecated 101.0.0
     * @return \Magento\Framework\Stdlib\Cookie\CookieMetadataFactory
     */
    private function getCookieMetadataFactory()
    {
        if (!$this->cookieMetadataFactory) {
            $this->cookieMetadataFactory = \Magento\Framework\App\ObjectManager::getInstance()->get(
                \Magento\Framework\Stdlib\Cookie\CookieMetadataFactory::class
            );
        }
        return $this->cookieMetadataFactory;
    }
}
