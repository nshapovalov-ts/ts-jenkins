<?php
namespace Mirakl\Connector\Test\Unit\Model\Order;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Mirakl\Connector\Model\Order\Payment as PaymentModel;
use Mirakl\MMP\FrontOperator\Domain\Collection\Payment\Debit\DebitOrderCollection;
use Mirakl\MMP\FrontOperator\Domain\Collection\Payment\Refund\RefundOrderCollection;
use PHPUnit\Framework\TestCase;

class PaymentTest extends TestCase
{
    /** @var PaymentModel */
    protected $paymentModel;

    /** @var \Magento\Framework\Event\ManagerInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $eventManagerMock;

    /** @var \Mirakl\Api\Helper\Payment|\PHPUnit_Framework_MockObject_MockObject */
    protected $paymentApiMock;

    protected function setUp(): void
    {
        $this->eventManagerMock = $this->getMockBuilder(\Magento\Framework\Event\ManagerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->paymentApiMock = $this->getMockBuilder(\Mirakl\Api\Helper\Payment::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->paymentModel = (new ObjectManager($this))->getObject(PaymentModel::class, [
            'eventManager' => $this->eventManagerMock,
            'paymentApi' => $this->paymentApiMock,
        ]);
    }

    public function testCollectDebits()
    {
        $debits = new DebitOrderCollection();

        $this->paymentApiMock->expects($this->once())
            ->method('getAllOrderDebits')
            ->willReturn($debits);

        $this->eventManagerMock->expects($this->once())
            ->method('dispatch')
            ->with('mirakl_customer_debit_list', ['debits' => $debits]);

        $this->paymentModel->collectDebits();
    }


    public function testCollectRefunds()
    {
        $refunds = new RefundOrderCollection();

        $this->paymentApiMock->expects($this->once())
            ->method('getAllOrderRefunds')
            ->willReturn($refunds);

        $this->eventManagerMock->expects($this->once())
            ->method('dispatch')
            ->with('mirakl_customer_refund_list', ['refunds' => $refunds]);

        $this->paymentModel->collectRefunds();
    }
}