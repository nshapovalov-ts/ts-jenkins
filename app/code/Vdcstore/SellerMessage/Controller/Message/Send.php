<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Vdcstore\SellerMessage\Controller\Message;

use Exception;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Customer\Model\Customer;
use Magento\Customer\Model\Session;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Json\Helper\Data;
use Magento\Framework\View\Result\PageFactory;
use Psr\Log\LoggerInterface;
use Vdcstore\SellerMessage\Helper\Data as SellerMessageHelper;

class Send extends Action
{
    /**
     * @var PageFactory
     */
    protected $resultPageFactory;
    /**
     * @var Data
     */
    protected $jsonHelper;
    /**
     * @var Session
     */
    private $customer;
    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;
    /**
     * @var SellerMessageHelper
     */
    private $sellerMessageHelper;
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
     * @param SellerMessageHelper $sellerMessageHelper
     * @param Session $customer
     * @param ProductRepositoryInterface $productRepository
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        Data $jsonHelper,
        LoggerInterface $logger,
        SellerMessageHelper $sellerMessageHelper,
        Session $customer,
        ProductRepositoryInterface $productRepository
    ) {
        $this->resultPageFactory = $resultPageFactory;
        $this->sellerMessageHelper = $sellerMessageHelper;
        $this->jsonHelper = $jsonHelper;
        $this->logger = $logger;
        $this->customer = $customer;
        $this->productRepository = $productRepository;
        parent::__construct($context);
    }

    /**
     * Execute view action
     */
    public function execute()
    {
        try {
            $response = [];

            $customer = $this->customer->getCustomer();
            if (!$this->customer->isLoggedIn()) {
                return $this->jsonResponse(__('Available only for logged in users'));
            }

            $postData = $this->getRequest()->getParams();
            if (empty($postData['product_sku']) || empty($postData['message'])) {
                return $this->jsonResponse(__('Your Message has not been sent to %1', $this->getSellerName()));
            }

            $message = $postData['message'];
            $sellerName = $postData['seller_name'];

            $productSku = $postData['product_sku'];
            $product = $this->productRepository->get($productSku);

            $postData['product'] = $product;
            $postData['customer'] = $customer;

            $this->sellerMessageHelper->sendMessageToSeller($customer, $product, 'Offer information', $message);
            $this->sellerMessageHelper->sendEmail($postData);

            $layout = $this->_view->getLayout();
            /** @var \Vdcstore\SellerMessage\Block\Message\Show $block */
            $block = $layout->createBlock(\Vdcstore\SellerMessage\Block\Message\Show::class)
                ->setTemplate('Vdcstore_SellerMessage::message/success.phtml')
                ->setSellerName($sellerName)
                ->setSellerImage($postData['seller_image']);
            /** @var array $response */
            $response['content'] = $block->toHtml();
        } catch (LocalizedException $e) {
            $this->logger->critical($e);
            $response = $e->getMessage();
        } catch (Exception $e) {
            $this->logger->critical($e);
            $response = $e->getMessage();
        }

        return $this->jsonResponse($response);
    }

    /**
     * Create json response
     *
     * @param string $response
     * @return mixed
     */
    public function jsonResponse($response = '')
    {
        return $this->getResponse()->representJson(
            $this->jsonHelper->jsonEncode($response)
        );
    }

    /**
     * Get Seller Name
     *
     * @return string
     */
    public function getSellerName(): string
    {
        return "Seller";
    }

    /**
     * Get Customer
     *
     * @return Customer
     */
    public function getCustomer(): Customer
    {
        return $this->customer->getCustomer();
    }
}
