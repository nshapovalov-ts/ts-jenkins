<?php

/**
 * Retailplace_MiraklOrder
 *
 * @copyright   Copyright (c) 2022 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Nikolay Shapovalov <nikolay@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\MiraklOrder\Controller\Adminhtml\Order\Files;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultInterface;
use Retailplace\MiraklOrder\Model\MiraklOrderInfo;
use Psr\Log\LoggerInterface;

/**
 * Class GetShippingInvoice return file with shipping invoice in zip archive
 */
class GetShippingInvoice extends Action implements HttpGetActionInterface
{
    /** @var MiraklOrderInfo */
    private $miraklOrderInfo;

    /** @var LoggerInterface */
    private $logger;

    /**
     * @param Context $context
     * @param LoggerInterface $logger
     * @param MiraklOrderInfo $miraklOrderInfo
     */
    public function __construct(
        Context $context,
        LoggerInterface $logger,
        MiraklOrderInfo $miraklOrderInfo
    ) {
        $this->miraklOrderInfo = $miraklOrderInfo;
        $this->logger = $logger;
        parent::__construct($context);
    }

    /**
     * @return void
     */
    public function execute()
    {
        try {
            $orderId = $this->getRequest()->getParam(MiraklOrderInfo::ORDER_ID_PARAM_NAME);
            $this->miraklOrderInfo->downloadShippingInvoice($orderId);
        } catch (\Exception $exception) {
            $this->logger->error($exception->getMessage());
            if ($exception->getCode() == 404) {
                $this->messageManager->addErrorMessage(__('There are no any shipping invoice files for this order'));
            } else {
                $this->messageManager->addErrorMessage($exception->getMessage());
            }
            $this->_redirect($this->_redirect->getRefererUrl());
        }
    }
}
