<?php
namespace Mirakl\FrontendDemo\Controller\Order;

use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\Action;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Data\Form\FormKey\Validator as FormKeyValidator;
use Magento\Framework\Registry;
use Magento\Framework\Session\Generic as GenericSession;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Result\PageFactory;
use Magento\Sales\Controller\AbstractController\OrderLoaderInterface;
use Magento\Sales\Controller\OrderInterface;
use Mirakl\Api\Helper\Order as OrderApi;
use Mirakl\Connector\Helper\Order as OrderHelper;
use Psr\Log\LoggerInterface;

abstract class AbstractOrder extends \Magento\Sales\Controller\AbstractController\View implements OrderInterface
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
     * @param   Action\Context          $context
     * @param   OrderLoaderInterface    $orderLoader
     * @param   PageFactory             $resultPageFactory
     * @param   Registry                $registry
     * @param 	CustomerSession         $customerSession
     * @param 	GenericSession          $session
     * @param 	FormKeyValidator        $formKeyValidator
     * @param   OrderHelper             $orderHelper
     * @param   OrderApi                $orderApi
     * @param   LoggerInterface         $logger
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
        LoggerInterface $logger
    ) {
        $this->registry = $registry;
        $this->customerSession = $customerSession;
        $this->session = $session;
        $this->formKeyValidator = $formKeyValidator;
        $this->redirectFactory = $context->getResultRedirectFactory();
        $this->url = $context->getUrl();
        $this->orderHelper = $orderHelper;
        $this->orderApi = $orderApi;
        $this->logger = $logger;
        parent::__construct($context, $orderLoader, $resultPageFactory);
    }

    /**
     * Try to load remote order by remote_id and register it
     *
     * @param   string|null  $remoteId
     * @return  bool|ResultInterface
     */
    protected function loadMiraklOrder($remoteId = null)
    {
        /** @var \Magento\Sales\Model\Order $order */
        $order = $this->registry->registry('current_order');

        if (null === $remoteId) {
            $remoteId = $this->_request->getParam('remote_id');
        }

        if ($order && $remoteId) {
            try {
                $miraklOrder = $this->orderHelper->getMiraklOrderById($order->getIncrementId(), $remoteId);
                if ($miraklOrder) {
                    $this->registry->register('mirakl_order', $miraklOrder);
                    $order->setMiraklOrderId($miraklOrder->getId());

                    return true;
                }
            } catch (\Exception $e) {
                $this->logger->warning($e->getMessage());
                $this->messageManager->addErrorMessage(
                    __('An error occurred. Please try again later.')
                );
            }
        }

        $resultRedirect = $this->redirectFactory->create();

        return $resultRedirect->setUrl($this->url->getUrl('sales/order/history'));
    }

    /**
     * Initialize order and remote order in session
     *
     * @return  bool|ResultInterface
     */
    protected function initOrders()
    {
        $result = $this->orderLoader->load($this->_request);
        if ($result instanceof \Magento\Framework\Controller\Result\Redirect) {
            $result->setUrl($this->url->getUrl('sales/order/history'));
        }
        if ($result instanceof ResultInterface) {
            return $result;
        }

        return $this->loadMiraklOrder();
    }

    /**
     * Order view page
     *
     * @return  ResultInterface
     */
    public function execute()
    {
        $result = $this->initOrders();
        if ($result !== true) {
            return $result;
        }

        /** @var \Magento\Framework\View\Result\Page $resultPage */
        $resultPage = $this->resultPageFactory->create();

        /** @var \Magento\Framework\View\Element\Html\Links $navigationBlock */
        $navigationBlock = $resultPage->getLayout()->getBlock('customer_account_navigation');
        if ($navigationBlock) {
            $navigationBlock->setActive('sales/order/history');
        }

        return $resultPage;
    }
}
