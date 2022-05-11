<?php
/**
 * Retailplace_Stripe
 *
 * @copyright   Copyright (c) 2022 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Natalia Sekulich <natalia@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\Stripe\Ui\Component\Listing\Column;

use Exception;
use Psr\Log\LoggerInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Stripe\PaymentMethod as StripePaymentMethod;
use Magento\Ui\Component\Listing\Columns\Column;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use Retailplace\Stripe\Rewrite\Model\Method\Invoice;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Framework\View\Element\UiComponent\ContextInterface;

/**
 * Class NetDaysCount
 */
class NetDaysCount extends Column
{
    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;


    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param OrderRepositoryInterface $orderRepository
     * @param LoggerInterface $logger
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        OrderRepositoryInterface $orderRepository,
        LoggerInterface $logger,
        array $components = [],
        array $data = []
    ) {
        parent::__construct($context, $uiComponentFactory, $components, $data);
        $this->orderRepository = $orderRepository;
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc
     * @since 100.1.0
     */
    public function prepareDataSource(array $dataSource)
    {
        if (empty($dataSource['data']['items'])) {
            return $dataSource;
        }
        foreach ($dataSource['data']['items'] as &$item) {
            if (isset($item[StripePaymentMethod::OBJECT_NAME])
                && $item[StripePaymentMethod::OBJECT_NAME] == Invoice::METHOD_CODE
            ) {
                try {
                    $order = $this->orderRepository->get($item[OrderInterface::ENTITY_ID]);
                    $payment = $order->getPayment();
                    if ($payment) {
                        $item[$this->getData('name')] = $this->getDaysDueString($payment);
                    }
                } catch (Exception $e) {
                    $this->logger->error($e->getMessage());
                }
            }
        }

        return $dataSource;
    }

    /**
     * Get Net days due string from order payment
     *
     * @param OrderPaymentInterface $payment
     *
     * @return string
     */
    private function getDaysDueString(OrderPaymentInterface $payment): string
    {
        $result = '';
        $additionalInfo = $payment->getAdditionalInformation();
        $daysDue = $additionalInfo['days_due'] ?? '';
        if ($daysDue) {
            $result = __(sprintf("Net %d days", $daysDue))->render();
        }

        return $result;
    }
}
