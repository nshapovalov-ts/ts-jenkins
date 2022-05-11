<?php
namespace Mirakl\Api\Helper;

use Mirakl\Core\Domain\FileWrapper;
use Mirakl\MMP\Common\Domain\Collection\Message\OrderMessageCollection;
use Mirakl\MMP\Common\Domain\Collection\Order\Document\OrderDocumentCollection;
use Mirakl\MMP\Common\Domain\Evaluation as MiraklEvaluation;
use Mirakl\MMP\Common\Domain\Message\MessageCreated;
use Mirakl\MMP\Common\Domain\Message\Thread\ThreadCreated;
use Mirakl\MMP\Common\Domain\Order\Message\CreateOrderMessage;
use Mirakl\MMP\Common\Domain\Order\Message\CreateOrderThread;
use Mirakl\MMP\Common\Domain\UserType;
use Mirakl\MMP\Common\Request\Order\Message\CreateOrderThreadRequest;
use Mirakl\MMP\Front\Domain\Collection\Order\Tax\OrderTaxCollection;
use Mirakl\MMP\Front\Domain\Order\Create\CreatedOrders;
use Mirakl\MMP\Front\Domain\Order\Create\CreateOrder;
use Mirakl\MMP\Front\Domain\Order\Evaluation\CreateOrderEvaluation;
use Mirakl\MMP\Front\Request\Order\Document\DownloadOrdersDocumentsRequest;
use Mirakl\MMP\Front\Request\Order\Document\GetOrderDocumentsRequest;
use Mirakl\MMP\Front\Request\Order\Evaluation\CreateOrderEvaluationRequest;
use Mirakl\MMP\Front\Request\Order\Evaluation\GetOrderEvaluationRequest;
use Mirakl\MMP\Front\Request\Order\Message\CreateOrderMessageRequest;
use Mirakl\MMP\Front\Request\Order\Message\GetOrderMessagesRequest;
use Mirakl\MMP\Front\Request\Order\Tax\GetOrderTaxesRequest;
use Mirakl\MMP\Front\Request\Order\Workflow\CreateOrderRequest;
use Mirakl\MMP\Front\Request\Order\Workflow\InvalidateOrderRequest;
use Mirakl\MMP\Front\Request\Order\Workflow\ValidateOrderRequest;
use Mirakl\MMP\FrontOperator\Domain\Collection\Order\OrderCollection;
use Mirakl\MMP\FrontOperator\Domain\Order as MiraklOrder;
use Mirakl\MMP\FrontOperator\Request\Order\GetOrdersRequest;
use Mirakl\MMP\FrontOperator\Request\Order\Incident\CloseIncidentRequest;
use Mirakl\MMP\FrontOperator\Request\Order\Incident\OpenIncidentRequest;
use Mirakl\MMP\FrontOperator\Request\Order\Workflow\ReceiveOrderRequest;

class Order extends ClientHelper\MMP
{
    /**
     * (OR01) Creates a new order on the Mirakl platform
     *
     * @param   CreateOrder  $order
     * @return  CreatedOrders
     */
    public function createOrder(CreateOrder $order)
    {
        if (!$locale = $this->validateLocale($order->getCustomer()->getLocale())) {
            $order->getCustomer()->unsetData('locale'); // Reset the locale if not handled by Mirakl
        }

        $request = new CreateOrderRequest($order);

        $this->_eventManager->dispatch('mirakl_api_create_order_before', ['request' => $request]);

        return $this->send($request);
    }

    /**
     * (OR02) Validates a commercial order which is in STAGING state
     *
     * @param   string  $commercialId
     */
    public function validateOrder($commercialId)
    {
        $request = new ValidateOrderRequest($commercialId);

        $this->_eventManager->dispatch('mirakl_api_validate_order_before', ['request' => $request]);

        $this->send($request);
    }

    /**
     * (OR03) Invalidates a commercial order which is in STAGING state
     *
     * @param   string  $commercialId
     */
    public function invalidateOrder($commercialId)
    {
        $request = new InvalidateOrderRequest($commercialId);

        $this->_eventManager->dispatch('mirakl_api_invalidate_order_before', ['request' => $request]);

        $this->send($request);
    }

    /**
     * Fetches Mirakl orders associated with the specified commercial id
     *
     * @param   string|array    $commercialIds
     * @param   bool            $paginate
     * @param   string          $locale
     * @return  OrderCollection
     */
    public function getOrdersByCommercialId($commercialIds, $paginate = false, $locale = null)
    {
        if (!is_array($commercialIds)) {
            $commercialIds = [$commercialIds];
        }

        return $this->getOrders(['commercial_ids' => $commercialIds], $paginate, $locale);
    }

    /**
     * (OR11) Fetches multiple orders matching specified parameters
     *
     * @param   array   $params
     * @param   bool    $paginate
     * @param   string  $locale
     * @return  OrderCollection
     */
    public function getOrders(array $params, $paginate = false, $locale = null)
    {
        $request = new GetOrdersRequest();
        $request->setData($params);
        $request->setPaginate($paginate);
        $request->setLocale($this->validateLocale($locale));

        $this->_eventManager->dispatch('mirakl_api_get_orders_before', ['request' => $request]);

        return $this->send($request);
    }

    /**
     * (OR62) Opens an incident on a Mirakl order line
     *
     * @param   MiraklOrder $miraklOrder
     * @param   string      $orderLineId
     * @param   string      $reason
     */
    public function openIncident(MiraklOrder $miraklOrder, $orderLineId, $reason)
    {
        $request = new OpenIncidentRequest($miraklOrder->getId(), $orderLineId, $reason);

        $this->_eventManager->dispatch('mirakl_api_open_incident_before', [
            'request'      => $request,
            'mirakl_order' => $miraklOrder,
        ]);

        $this->send($request);
    }

    /**
     * (OR63) Closes an incident opened on a Mirakl order line
     *
     * @param   MiraklOrder $miraklOrder
     * @param   string      $orderLineId
     * @param   string      $reason
     */
    public function closeIncident(MiraklOrder $miraklOrder, $orderLineId, $reason)
    {
        $request = new CloseIncidentRequest($miraklOrder->getId(), $orderLineId, $reason);

        $this->_eventManager->dispatch('mirakl_api_close_incident_before', [
            'request'      => $request,
            'mirakl_order' => $miraklOrder,
        ]);

        $this->send($request);
    }

    /**
     * (OR41) Fetches messages of a Mirakl order
     *
     * @param   MiraklOrder $miraklOrder
     * @param   string      $userType
     * @param   bool        $paginate
     * @return  OrderMessageCollection
     */
    public function getOrderMessages(MiraklOrder $miraklOrder, $userType = UserType::ALL, $paginate = false)
    {
        $request = new GetOrderMessagesRequest($miraklOrder->getId());
        $request->setPaginate($paginate);
        $request->setUserType($userType);
        $request->sortAsc();

        $this->_eventManager->dispatch('mirakl_api_get_order_messages_before', [
            'request'      => $request,
            'mirakl_order' => $miraklOrder,
        ]);

        return $this->send($request);
    }

    /**
     * (OR42) Posts a message on a Mirakl order
     *
     * @param   MiraklOrder         $miraklOrder
     * @param   CreateOrderMessage  $message
     * @return  MessageCreated
     */
    public function createOrderMessage(MiraklOrder $miraklOrder, CreateOrderMessage $message)
    {
        $request = new CreateOrderMessageRequest($miraklOrder->getId(), $message);

        $this->_eventManager->dispatch('mirakl_api_create_order_message_before', [
            'request'      => $request,
            'mirakl_order' => $miraklOrder,
        ]);

        return $this->send($request);
    }

    /**
     * (OR43) Create a thread on an order
     *
     * @param  MiraklOrder          $miraklOrder
     * @param  CreateOrderThread    $thread
     * @param  FileWrapper[]        $files
     * @return ThreadCreated
     */
    public function createOrderThread(MiraklOrder $miraklOrder, CreateOrderThread $thread, $files = [])
    {
        $request = new CreateOrderThreadRequest($miraklOrder->getId(), $thread);
        if (count($files)) {
            $request->setFiles($files);
        }

        $this->_eventManager->dispatch('mirakl_api_create_order_thread_before', [
            'request'      => $request,
            'mirakl_order' => $miraklOrder,
        ]);

        return $this->send($request);
    }

    /**
     * (OR25) Marks a Mirakl order as RECEIVED
     *
     * @param   MiraklOrder $miraklOrder
     */
    public function receiveOrder(MiraklOrder $miraklOrder)
    {
        $request = new ReceiveOrderRequest($miraklOrder->getId());

        $this->_eventManager->dispatch('mirakl_api_receive_order_before', [
            'request'      => $request,
            'mirakl_order' => $miraklOrder,
        ]);

        $this->send($request);
    }

    /**
     * (OR52) Sends an evaluation on a Mirakl order
     *
     * @param   MiraklOrder             $miraklOrder
     * @param   CreateOrderEvaluation   $evaluation
     */
    public function evaluateOrder(MiraklOrder $miraklOrder, CreateOrderEvaluation $evaluation)
    {
        $request = new CreateOrderEvaluationRequest($miraklOrder->getId(), $evaluation);

        $this->_eventManager->dispatch('mirakl_api_evaluate_order_before', [
            'request'      => $request,
            'mirakl_order' => $miraklOrder,
        ]);

        $this->send($request);
    }

    /**
     * (OR51) Fetches evaluation of a Mirakl order
     *
     * @param   MiraklOrder $miraklOrder
     * @return  MiraklEvaluation
     */
    public function getOrderEvaluation(MiraklOrder $miraklOrder)
    {
        $request = new GetOrderEvaluationRequest($miraklOrder->getId());

        $this->_eventManager->dispatch('mirakl_api_get_order_evaluation_before', [
            'request'      => $request,
            'mirakl_order' => $miraklOrder,
        ]);

        return $this->send($request);
    }

    /**
     * Fetches documents of a single Mirakl order
     *
     * @param   MiraklOrder $miraklOrder
     * @return  OrderDocumentCollection
     */
    public function getOrderDocuments(MiraklOrder $miraklOrder)
    {
        return $this->getOrdersDocuments([$miraklOrder->getId()]);
    }

    /**
     * (OR72) Fetches documents of multiple Mirakl orders
     *
     * @param   array   $orderIds
     * @return  OrderDocumentCollection
     */
    public function getOrdersDocuments(array $orderIds)
    {
        $request = new GetOrderDocumentsRequest($orderIds);

        $this->_eventManager->dispatch('mirakl_api_get_order_documents_before', [
            'request' => $request,
        ]);

        return $this->send($request);
    }

    /**
     * (OR75) Fetches Mirakl configured taxes
     *
     * @return  OrderTaxCollection
     */
    public function getOrderTaxes()
    {
        $request = new GetOrderTaxesRequest();

        $this->_eventManager->dispatch('mirakl_api_get_order_taxes_before', [
            'request' => $request,
        ]);

        return $this->send($request);
    }

    /**
     * Downloads a single order document
     *
     * @param   int $docId
     * @return  FileWrapper
     */
    public function downloadDocument($docId)
    {
        return $this->downloadDocuments([$docId]);
    }

    /**
     * (OR73) Downloads multiple order documents
     *
     * @param   array   $docIds
     * @return  FileWrapper
     */
    public function downloadDocuments(array $docIds)
    {
        $request = new DownloadOrdersDocumentsRequest();
        $request->setDocumentIds($docIds);

        return $this->send($request);
    }
}
