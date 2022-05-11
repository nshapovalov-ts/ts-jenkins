<?php
namespace Mirakl\FrontendDemo\Observer;

use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Registry;
use Magento\Framework\UrlInterface;
use Magento\Sales\Controller\AbstractController\OrderLoaderInterface;
use Mirakl\Connector\Helper\Order as OrderHelper;

class HandleOrderViewObserver implements ObserverInterface
{
    /**
     * @var OrderLoaderInterface
     */
    private $orderLoader;

    /**
     * @var Registry
     */
    private $coreRegistry;

    /**
     * @var OrderHelper
     */
    private $orderHelper;

    /**
     * @var UrlInterface
     */
    private $url;

    /**
     * @param   OrderLoaderInterface    $orderLoader
     * @param   Registry                $coreRegistry
     * @param   OrderHelper             $orderHelper
     * @param   UrlInterface            $url
     */
    public function __construct(
        OrderLoaderInterface $orderLoader,
        Registry $coreRegistry,
        OrderHelper $orderHelper,
        UrlInterface $url
    ) {
        $this->coreRegistry = $coreRegistry;
        $this->orderLoader = $orderLoader;
        $this->orderHelper = $orderHelper;
        $this->url = $url;
    }

    /**
     * Handles sales/order/view page which has to be redirected to history if order contains some Mirakl products
     *
     * {@inheritdoc}
     */
    public function execute(Observer $observer)
    {
        $request = $observer->getEvent()->getRequest();
        $this->orderLoader->load($request);
        $order = $this->coreRegistry->registry('current_order');

        // Avoid already registered error
        $this->coreRegistry->unregister('current_order');

        if ($order && $order->getMiraklSent() && $this->orderHelper->isFullMiraklOrder($order)) {
            /** @var \Magento\Framework\App\Action\Action $controller */
            $controller = $observer->getEvent()->getControllerAction();
            /** @var \Magento\Framework\App\Response\Http $response */
            $response = $controller->getResponse();
            $response->setRedirect($this->url->getUrl('*/*/history'));
        }
    }
}