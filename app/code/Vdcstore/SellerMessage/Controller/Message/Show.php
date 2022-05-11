<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Vdcstore\SellerMessage\Controller\Message;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Json\Helper\Data;
use Magento\Framework\View\Result\PageFactory;
use Psr\Log\LoggerInterface;

class Show extends Action
{

    protected $resultPageFactory;
    protected $jsonHelper;
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * Constructor
     *
     * @param Context $context
     * @param PageFactory $resultPageFactory
     * @param Data $jsonHelper
     * @param LoggerInterface $logger
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        Data $jsonHelper,
        LoggerInterface $logger
    ) {
        $this->resultPageFactory = $resultPageFactory;
        $this->jsonHelper = $jsonHelper;
        $this->logger = $logger;
        parent::__construct($context);
    }

    /**
     * Execute view action
     *
     * @return ResultInterface
     */
    public function execute()
    {
        try {
            $productSku = $this->getRequest()->getParam('product_sku');
            $layout = $this->_view->getLayout();
            /** @var \Vdcstore\SellerMessage\Block\Message\Show $block */
            $block = $layout->createBlock(\Vdcstore\SellerMessage\Block\Message\Show::class)
            ->setTemplate('Vdcstore_SellerMessage::message/show.phtml')
            ->setProductSku($productSku);
            $response = [
                'content' => $block->toHtml(),
            ];
            return $this->jsonResponse($response);
        } catch (LocalizedException $e) {
            return $this->jsonResponse($e->getMessage());
        } catch (\Exception $e) {
            $this->logger->critical($e);
            return $this->jsonResponse($e->getMessage());
        }
    }

    /**
     * Create json response
     * @param string $response
     * @return mixed
     */
    public function jsonResponse($response = '')
    {
        return $this->getResponse()->representJson(
            $this->jsonHelper->jsonEncode($response)
        );
    }
}

