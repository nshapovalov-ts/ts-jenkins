<?php
namespace Mirakl\Api\Test\Unit\Model\Log;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Mirakl\Api\Model\Log\RequestLogValidator;
use PHPUnit\Framework\TestCase;

class RequestLogValidatorTest extends TestCase
{
    /** @var RequestLogValidator */
    protected $requestLogValidator;

    /**
     * @var \Mirakl\Api\Helper\Config|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $configMock;

    /**
     * @var \Mirakl\Core\Request\RequestInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $requestMock;

    protected function setUp(): void
    {
        $this->configMock = $this->getMockBuilder(\Mirakl\Api\Helper\Config::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->requestMock = $this->getMockBuilder(\Mirakl\Core\Request\RequestInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->requestLogValidator = (new ObjectManager($this))->getObject(RequestLogValidator::class, [
            'config' => $this->configMock
        ]);
    }

    public function testValidateWithLoggingDisabled()
    {
        $this->configMock->expects($this->once())
            ->method('isApiLogEnabled')
            ->willReturn(false);

        $this->assertFalse($this->requestLogValidator->validate($this->requestMock));
    }

    public function testValidateWithEmptyFilter()
    {
        $this->configMock->expects($this->once())
            ->method('isApiLogEnabled')
            ->willReturn(true);

        $this->configMock->expects($this->once())
            ->method('getApiLogFilter')
            ->willReturn('');

        $this->assertTrue($this->requestLogValidator->validate($this->requestMock));
    }

    /**
     * @param   string  $filter
     * @param   string  $requestUri
     * @param   array   $requestQueryParams
     * @param   bool    $expected
     * @dataProvider getTestValidateWithFilterDataProvider
     */
    public function testValidateWithFilter($filter, $requestUri, array $requestQueryParams, $expected)
    {
        $this->configMock->expects($this->once())
            ->method('isApiLogEnabled')
            ->willReturn(true);

        $this->configMock->expects($this->once())
            ->method('getApiLogFilter')
            ->willReturn($filter);

        $this->requestMock->expects($this->once())
            ->method('getQueryParams')
            ->willReturn($requestQueryParams);

        $this->requestMock->expects($this->once())
            ->method('getUri')
            ->willReturn($requestUri);

        $this->assertSame($expected, $this->requestLogValidator->validate($this->requestMock));
    }

    /**
     * @return  array
     */
    public function getTestValidateWithFilterDataProvider()
    {
        return [
            ['api/orders', 'locales', [], false],
            ['api/shipping/rates|api/locales', 'locales', [], true],
            ['api/shipping/rates|api/locales', 'shipping/rates', [], true],
            ['api/shipping/rates\?shipping_zone_code=INT|api/locales', 'shipping/rates', [], false],
            ['api/shipping/rates\?shipping_zone_code=INT|api/locales', 'shipping/rates', ['shipping_zone_code' => 'INT'], true],
        ];
    }
}