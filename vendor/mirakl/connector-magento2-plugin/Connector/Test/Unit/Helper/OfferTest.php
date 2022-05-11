<?php
namespace Mirakl\Connector\Test\Unit\Helper;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Mirakl\Connector\Helper\Offer as OfferHelper;
use PHPUnit\Framework\TestCase;

class CategoryTest extends TestCase
{
    /** @var OfferHelper */
    protected $offerHelper;

    protected function setUp(): void
    {
        $this->offerHelper = (new ObjectManager($this))->getObject(OfferHelper::class);
    }

    /**
     * @param   float       $expected
     * @param   array       $offerData
     * @param   int|null    $qty
     * @group foo
     * @dataProvider getTestGetOfferFinalPriceDataProvider
     */
    public function testGetOfferFinalPrice($expected, array $offerData, $qty = null)
    {
        /** @var \Mirakl\Connector\Model\Offer $offer */
        $offer = (new ObjectManager($this))->getObject(\Mirakl\Connector\Model\Offer::class);
        $offer->setData($offerData);

        $this->assertSame($expected, $this->offerHelper->getOfferFinalPrice($offer, $qty));
    }

    /**
     * @return  array
     */
    public function getTestGetOfferFinalPriceDataProvider()
    {
        return [
            [
                $expected = 9.90,
                $offerData = [
                    'price' => '9.90',
                ],
                $qty = 1
            ],
            [
                $expected = 6.0,
                $offerData = [
                    'price'           => '10',
                    'discount_price'  => '0.00',
                    'discount_ranges' => '5|6.00',
                    'price_ranges'    => '1|10.00,5|8.00,6|5.00'
                ],
                $qty = 5
            ],
            [
                $expected = 5.0,
                $offerData = [
                    'price'           => '10',
                    'discount_price'  => '0.00',
                    'discount_ranges' => '5|6.00',
                    'price_ranges'    => '1|10.00,5|8.00,6|5.00'
                ],
                $qty = 6
            ],
            [
                $expected = 4.58,
                $offerData = [
                    'price'           => '10',
                    'discount_price'  => '0.00',
                    'discount_ranges' => '5|4.58',
                    'price_ranges'    => '1|10.00,5|8.00,6|5.00'
                ],
                $qty = 6
            ],
            [
                $expected = 5.0,
                $offerData = [
                    'price'           => '10',
                    'price_ranges'    => '1|10.00,5|5.00,6|8.00'
                ],
                $qty = 5
            ],
            [
                $expected = 8.0,
                $offerData = [
                    'price'           => '10',
                    'price_ranges'    => '1|10.00,5|5.00,6|8.00'
                ],
                $qty = 6
            ],
            [
                $expected = 10.0,
                $offerData = [
                    'price'           => '10',
                    'price_ranges'    => '1|10.00,5|5.00,6|8.00'
                ],
                $qty = 1
            ],
            [
                $expected = 5.0,
                $offerData = [
                    'price'           => '10',
                    'discount_price'  => '5.00',
                    'discount_ranges' => '1|5.00',
                    'price_ranges'    => '1|10.00'
                ],
                $qty = 1
            ],
        ];
    }
}