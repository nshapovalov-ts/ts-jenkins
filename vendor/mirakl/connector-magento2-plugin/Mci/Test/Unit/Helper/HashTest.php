<?php
namespace Mirakl\Mci\Test\Unit\Helper;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Mirakl\Mci\Helper\Hash as HashHelper;
use PHPUnit\Framework\TestCase;

class HashTest extends TestCase
{
    /** @var HashHelper */
    protected $hashHelper;

    /** @var \Mirakl\Mci\Helper\Config|\PHPUnit_Framework_MockObject_MockObject */
    protected $mciConfig;

    /** @var \Magento\Framework\DB\Adapter\AdapterInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $connection;

    protected function setUp(): void
    {
        $this->mciConfig = $this->getMockBuilder('Mirakl\Mci\Helper\Config')
            ->setMethods(['isCheckDataHash'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->connection = $this->getMockBuilder('Magento\Framework\DB\Adapter\AdapterInterface')
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $resource = $this->getMockBuilder('Magento\Framework\App\ResourceConnection')
            ->disableOriginalConstructor()
            ->setMethods(['getConnection', 'getTableName'])
            ->getMock();
        $resource->expects($this->any())
            ->method('getConnection')
            ->willReturn($this->connection);
        $resource->expects($this->any())
            ->method('getTableName')
            ->willReturnArgument(0);

        $this->hashHelper = (new ObjectManager($this))->getObject(HashHelper::class, [
            'resource' => $resource,
            'mciConfig' => $this->mciConfig
        ]);
    }

    public function testClearHashes()
    {
        $this->connection->expects($this->any())
            ->method('truncateTable')
            ->willReturnSelf();

        $result = $this->hashHelper->clearHashes();

        $this->assertNull($result);
    }

    public function testIsShopHashExists()
    {
        $this->mciConfig->expects($this->any())
            ->method('isCheckDataHash')
            ->willReturnOnConsecutiveCalls(false, true);

        $selectMock = $this->getMockBuilder('Magento\Framework\DB\Select')
            ->disableOriginalConstructor()
            ->getMock();
        $selectMock->expects($this->any())
            ->method('from')
            ->willReturn($selectMock);
        $selectMock->expects($this->any())
            ->method('where')
            ->willReturn($selectMock);
        $selectMock->expects($this->any())
            ->method('limit')
            ->willReturn($selectMock);

        $this->connection->expects($this->any())
            ->method('select')
            ->willReturn($selectMock);
        $this->connection->expects($this->any())
            ->method('fetchOne')
            ->with($selectMock)
            ->willReturn(123);

        $this->assertFalse($this->hashHelper->isShopHashExists('2002', 'ABCD-1234', '0392hdbs32'));
        $this->assertTrue($this->hashHelper->isShopHashExists('2001', 'XYZ-5678', '2932sh1z092'));
    }

    public function testSaveShopHash()
    {
        $this->connection->expects($this->any())
            ->method('insertOnDuplicate')
            ->willReturnSelf();

        $result = $this->hashHelper->saveShopHash('2002', 'ABCD-1234', '0392hdbs32');

        $this->assertNotEmpty($result);
    }

    public function testDeleteShopHash()
    {
        $this->connection->expects($this->any())
            ->method('delete')
            ->willReturnSelf();

        $result = $this->hashHelper->deleteShopHash('2002', 'ABCD-1234');

        $this->assertNotEmpty($result);
    }
}