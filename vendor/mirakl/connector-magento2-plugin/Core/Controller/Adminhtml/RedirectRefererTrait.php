<?php
namespace Mirakl\Core\Controller\Adminhtml;

use Magento\Framework\Controller\ResultFactory;

/**
 * @property \Magento\Framework\Controller\ResultFactory $resultFactory
 * @property \Magento\Framework\App\Response\RedirectInterface $_redirect
 */
trait RedirectRefererTrait
{
    /**
     * Redirect to referer
     *
     * @return  \Magento\Framework\Controller\ResultInterface
     */
    public function redirectReferer()
    {
        /** @var \Magento\Framework\Controller\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        $resultRedirect->setUrl($this->_redirect->getRefererUrl());

        return $resultRedirect;
    }
}