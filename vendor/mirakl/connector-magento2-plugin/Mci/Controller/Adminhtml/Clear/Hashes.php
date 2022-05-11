<?php
namespace Mirakl\Mci\Controller\Adminhtml\Clear;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;
use Mirakl\Mci\Helper\Hash as HashHelper;
use Psr\Log\LoggerInterface;

class Hashes extends Action
{
    /**
     * @var HashHelper
     */
    private $hashHelper;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param   Context         $context
     * @param   HashHelper      $hashHelper
     * @param   LoggerInterface $logger
     */
    public function __construct(
        Context $context,
        HashHelper $hashHelper,
        LoggerInterface $logger
    ) {
        parent::__construct($context);
        $this->hashHelper = $hashHelper;
        $this->logger = $logger;
    }

    /**
     * Clear all data hashes of previously imported products
     */
    public function execute()
    {
        try {
            $this->hashHelper->clearHashes();
            $this->messageManager->addSuccessMessage(
                __('Hashes have been cleared successfully.')
            );
        } catch (\Exception $e) {
            $this->logger->critical($e->getMessage());
            $this->messageManager->addErrorMessage(
                __('An error occurred while clearing hashes (%1).', $e->getMessage())
            );
        }

        /** @var \Magento\Framework\Controller\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        $resultRedirect->setUrl($this->_redirect->getRefererUrl());

        return $resultRedirect;
    }
}