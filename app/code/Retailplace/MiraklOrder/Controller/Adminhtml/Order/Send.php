<?php

/**
 * Retailplace_MiraklOrder
 *
 * @copyright   Copyright (c) 2022 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Evgeniy Derevyanko <evgeniy@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\MiraklOrder\Controller\Adminhtml\Order;

use Exception;
use GuzzleHttp\Exception\BadResponseException;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\ResultInterface;
use Magento\Sales\Controller\Adminhtml\Order as OrderController;
use Magento\Sales\Model\Order as OrderModel;
use Mirakl\Connector\Helper\Order;
use function Mirakl\parse_json_response;

/**
 * class Send
 */
class Send extends OrderController
{
    /**
     * Execute method
     *
     * @return ResponseInterface|Redirect|ResultInterface
     */
    public function execute()
    {
        /** @var OrderModel $order */
        if ($order = $this->_initOrder()) {
            try {
                /** @var Order $orderHelper */
                $orderHelper = $this->_objectManager->get('Mirakl\Connector\Helper\Order');

                $createdOrders = $orderHelper->createMiraklOrder($order, true, false);
                $offersNotShippable = $createdOrders->getOffersNotShippable();
                if ($offersNotShippable && $offersNotShippable->count()) {
                    $reason = $offersNotShippable->first()->getReason();
                    throw new Exception(__('Something went wrong while sending the order to Mirakl: %1.', $reason));
                }

                $this->messageManager->addSuccessMessage(__('The order has been sent to Mirakl.'));
            } catch (BadResponseException $e) {
                $response = parse_json_response($e->getResponse());
                $this->messageManager->addErrorMessage($response['message']);
            } catch (Exception $e) {
                $this->messageManager->addErrorMessage($e->getMessage());
                $this->logger->error($e->getMessage());
            }
        }

        $resultRedirect = $this->resultRedirectFactory->create();
        $resultRedirect->setPath('sales/order/view', ['_current' => true]);

        return $resultRedirect;
    }
}
