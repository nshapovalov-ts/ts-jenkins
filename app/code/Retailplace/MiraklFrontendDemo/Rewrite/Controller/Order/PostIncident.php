<?php

/**
 * Retailplace_MiraklFrontendDemo
 *
 * @copyright   Copyright (c) 2021 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Alexander Korsun <aleksandr@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\MiraklFrontendDemo\Rewrite\Controller\Order;

use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\Action;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Data\Form\FormKey\Validator as FormKeyValidator;
use Magento\Framework\Registry;
use Magento\Framework\Session\Generic as GenericSession;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Result\PageFactory;
use Magento\Sales\Controller\AbstractController\OrderLoaderInterface;
use Magento\Sales\Model\Order;
use Mirakl\Api\Helper\Order as OrderApi;
use Mirakl\Connector\Helper\Order as OrderHelper;
use Mirakl\MMP\Common\Domain\Order\Message\CreateOrderMessageFactory;
use Mirakl\MMP\FrontOperator\Domain\Order as DomainOrder;
use Psr\Log\LoggerInterface;
use Retailplace\MiraklFrontendDemo\Api\MessagesRepositoryInterface;
use Retailplace\MiraklFrontendDemo\Api\MessagesStatsRepositoryInterface;

class PostIncident extends \Mirakl\FrontendDemo\Controller\Order\PostIncident
{

    /**
     * @var Registry
     */
    protected $registry;

    /**
     * @var GenericSession
     */
    protected $session;

    /**
     * @var CustomerSession
     */
    protected $customerSession;

    /**
     * @var FormKeyValidator
     */
    protected $formKeyValidator;

    /**
     * @var RedirectFactory
     */
    protected $redirectFactory;

    /**
     * @var UrlInterface
     */
    protected $url;

    /**
     * @var OrderHelper
     */
    protected $orderHelper;

    /**
     * @var OrderApi
     */
    protected $orderApi;

    /**
     * @var LoggerInterface
     */
    protected $logger;
    /**
     * @var MessagesStatsRepositoryInterface
     */
    private $messagesStatsRepository;
    /**
     * @var MessagesRepositoryInterface
     */
    private $messagesRepository;
    /**
     * @var CreateOrderMessageFactory
     */
    private $createOrderMessage;

    /**
     * @param Action\Context $context
     * @param OrderLoaderInterface $orderLoader
     * @param PageFactory $resultPageFactory
     * @param Registry $registry
     * @param CustomerSession $customerSession
     * @param GenericSession $session
     * @param FormKeyValidator $formKeyValidator
     * @param OrderHelper $orderHelper
     * @param OrderApi $orderApi
     * @param LoggerInterface $logger
     * @param CreateOrderMessageFactory $createOrderMessage
     * @param MessagesStatsRepositoryInterface $messagesStatsRepository
     * @param MessagesRepositoryInterface $messagesRepository
     */
    public function __construct(
        Action\Context $context,
        OrderLoaderInterface $orderLoader,
        PageFactory $resultPageFactory,
        Registry $registry,
        CustomerSession $customerSession,
        GenericSession $session,
        FormKeyValidator $formKeyValidator,
        OrderHelper $orderHelper,
        OrderApi $orderApi,
        LoggerInterface $logger,
        CreateOrderMessageFactory $createOrderMessage,
        MessagesStatsRepositoryInterface $messagesStatsRepository,
        MessagesRepositoryInterface $messagesRepository
    ) {
        parent::__construct(
            $context,
            $orderLoader,
            $resultPageFactory,
            $registry,
            $customerSession,
            $session,
            $formKeyValidator,
            $orderHelper,
            $orderApi,
            $logger
        );

        $this->createOrderMessage = $createOrderMessage;
        $this->messagesStatsRepository = $messagesStatsRepository;
        $this->messagesRepository = $messagesRepository;
    }

    /**
     * Submit incident action
     *
     * @return  ResultInterface
     */
    public function execute()
    {
        /** @var Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        if (!$this->formKeyValidator->validate($this->getRequest())) {
            $resultRedirect->setUrl($this->_redirect->getRefererUrl());
            return $resultRedirect;
        }

        $result = $this->initOrders();
        if ($result !== true) {
            return $result;
        }

        /** @var Order $order */
        $order = $this->registry->registry('current_order');
        /** @var DomainOrder $miraklOrder */
        $miraklOrder = $this->registry->registry('mirakl_order');

        $type = $this->_request->getParam('type');
        if (!in_array($type, ['close', 'open'])) {
            $resultRedirect->setUrl($this->_redirect->getRefererUrl());
            return $resultRedirect;
        }

        $data = $this->getRequest()->getPostValue();
        if (!empty($data)) {
            $orderLineId = $data['order_line'];
            $reason = $data['reason'];
            $subject = $data['subject'] ?? "";
            $body = $data['comment'] ?? "";
            try {
                if ($type == 'open') {
                    $this->orderApi->openIncident($miraklOrder, $orderLineId, $reason);
                    $this->messageManager->addSuccessMessage(
                        __('Incident has been successfully created.')
                    );
                    if ($body && $subject) {
                        $customer = $this->customerSession->getCustomerDataObject();
                        $message = $this->createOrderMessage->create();
                        $message
                            ->setCustomerId($order->getCustomerId() ?: $customer->getId())
                            ->setCustomerFirstname($order->getCustomerFirstname() ?: $customer->getFirstname())
                            ->setCustomerLastname($order->getCustomerLastname() ?: $customer->getLastname())
                            ->setCustomerEmail($order->getCustomerEmail() ?: $customer->getEmail())
                            ->setToShop(true)
                            ->setSubject($subject)
                            ->setBody($body);

                        $this->orderApi->createOrderMessage($miraklOrder, $message);
                        $this->messagesStatsRepository->getAllMySentMessages($customer->getId(), $miraklOrder);
                    }
                } else {
                    $this->orderApi->closeIncident($miraklOrder, $orderLineId, $reason);
                    $this->messageManager->addSuccessMessage(
                        __('Incident has been successfully closed.')
                    );
                }
            } catch (\Exception $e) {
                $this->logger->warning($e->getMessage());
                $this->messageManager->addErrorMessage(
                    $type == 'open'
                        ? __('An error occurred while opening an incident.')
                        : __('An error occurred while closing incident.')
                );
            }
        }

        $resultRedirect->setUrl($this->url->getUrl('marketplace/order/view', [
            'order_id'  => $order->getId(),
            'remote_id' => $miraklOrder->getId(),
        ]));

        return $resultRedirect;
    }
}
