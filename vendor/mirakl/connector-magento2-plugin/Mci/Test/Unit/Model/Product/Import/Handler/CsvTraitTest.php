<?php
namespace Mirakl\Mci\Test\Unit\Model\Product\Import\Handler;

use Mirakl\Mci\Model\Product\Import\Handler\CsvTrait;
use PHPUnit\Framework\TestCase;

class CsvTraitTest extends TestCase
{
    /** @var CsvTrait */
    protected $handler;

    protected function setUp(): void
    {
        $this->handler = $this->getObjectForTrait(CsvTrait::class);
    }

    /**
     * @param   array           $data
     * @param   string          $fileDelimiter
     * @param   string          $testedDelimiter
     * @param   string|false    $expectedDelimiter
     *
     * @dataProvider getTestValidDelimiterDataProvider
     */
    public function testGetValidDelimiter($data, $fileDelimiter, $testedDelimiter, $expectedDelimiter)
    {
        $fh = tmpfile();
        $this->assertTrue(is_resource($fh));

        fputcsv($fh, $data, $fileDelimiter);
        rewind($fh);

        $this->assertSame($expectedDelimiter, $this->handler->getValidDelimiter($fh, $testedDelimiter));

        fclose($fh);
    }

    /**
     * @return  array
     */
    public function getTestValidDelimiterDataProvider()
    {
        return [
            [
                // | delimiter has no fallback
                ['foo', 'bar', 'baz'], '|', '|', '|',
            ],
            [
                // ; delimiter has fallback but is identical everywhere so it must be valid
                ['foo', 'bar', 'baz'], ';', ';', ';',
            ],
            [
                // tested ; delimiter is different than , file delimiter so the valid , delimiter should be returned
                ['foo', 'bar', 'baz'], ',', ';', ',',
            ],
            [
                // tested , delimiter is different than ; file delimiter so the valid ; delimiter should be returned
                ['foo', 'bar', 'baz'], ';', ',', ';',
            ],
            [
                // no valid delimiter found
                ['foo', 'bar', 'baz'], '|', ';', false,
            ],
            [
                // empty data must return false
                [''], ';', ';', false,
            ],
        ];
    }
}