<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2021 Amasty (https://www.amasty.com)
 * @package Amasty_Rules
 */

declare(strict_types=1);

namespace Amasty\Rules\Test\Unit\Model\Rule;

use Magento\Quote\Model\Quote\Address\Item;
use Magento\SalesRule\Model\Validator;
use Amasty\Rules\Model\Rule\ItemCalculationPrice;
use PHPUnit\Framework\TestCase;

class ItemCalculationPriceTest extends TestCase
{
    /**
     * @var Item
     */
    private $item;

    /**
     * @var ItemCalculationPrice
     */
    private $itemCalculationPrice;

    public function setUp(): void
    {
        $objectManager = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);

        $validator = $this->initValidator();

        $this->item = $objectManager->getObject(Item::class, ['data' => [
            'calculation_price' => 8.0,
            'base_calculation_price' => 16.0,
            'discount_amount' => 4.0,
            'base_discount_amount' => 8.0,
            'original_price' => '10.0',
            'base_original_price' => '20.0',
            'qty' => 2,
        ]]);

        $this->itemCalculationPrice = $objectManager->getObject(
            ItemCalculationPrice::class,
            ['validator' => $validator]
        );
    }

    /**
     * @return Validator|\PHPUnit\Framework\MockObject\MockObject
     */
    private function initValidator()
    {
        $validator = $this->getMockBuilder(Validator::class)->disableOriginalConstructor()->getMock();
        $validator->expects($this->any())
            ->method('getItemBasePrice')
            ->willReturnCallback(
                function ($item) {
                    return $item->getBaseCalculationPrice();
                }
            );
        $validator->expects($this->any())
            ->method('getItemOriginalPrice')
            ->willReturnCallback(
                function ($item) {
                    return $item->getOriginalPrice();
                }
            );
        $validator->expects($this->any())
            ->method('getItemPrice')
            ->willReturnCallback(
                function ($item) {
                    return $item->getCalculationPrice();
                }
            );
        $validator->expects($this->any())
            ->method('getItemBaseOriginalPrice')
            ->willReturnCallback(
                function ($item) {
                    return $item->getBaseOriginalPrice();
                }
            );

        return $validator;
    }

    /**
     * @covers ItemCalculationPrice::getItemPrice
     * @dataProvider getItemPriceProvider
     *
     * @param int $priceSelector
     * @param $expected
     */
    public function testGetItemPrice(int $priceSelector, $expected)
    {
        $this->itemCalculationPrice->setPriceSelector($priceSelector);

        $result = $this->itemCalculationPrice->getItemPrice($this->item);

        $this->assertSame($expected, $result);
    }

    public function getItemPriceProvider()
    {
        return [
            'default_price' => [
                ItemCalculationPrice::DEFAULT_PRICE,
                8.0
            ],
            'discounted' => [
                ItemCalculationPrice::DISCOUNTED_PRICE,
                6.0
            ],
            'origin' => [
                ItemCalculationPrice::ORIGIN_PRICE,
                10.0
            ],
        ];
    }

    /**
     * @covers ItemCalculationPrice::getFinalPriceRevert
     */
    public function testGetFinalPriceRevert()
    {
        $result = $this->itemCalculationPrice->getFinalPriceRevert($this->item);

        $this->assertSame(2.0, $result);
    }

    /**
     * @covers ItemCalculationPrice::resolveFinalPriceRevert
     * @dataProvider resolveRevertData
     *
     * @param int $priceSelector
     * @param float $discount
     * @param array $additionalItemData
     * @param float $expected
     */
    public function testResolveFinalPriceRevert(int $priceSelector, $discount, $additionalItemData, $expected)
    {
        $this->itemCalculationPrice->setPriceSelector($priceSelector);
        $this->item->addData($additionalItemData);
        $result = $this->itemCalculationPrice->resolveFinalPriceRevert($discount, $this->item);

        $this->assertSame($expected, $result);
    }

    public function resolveRevertData()
    {
        return [
            'without_revert' => [
                ItemCalculationPrice::DEFAULT_PRICE,
                5.0,
                [],
                5.0
            ],
            'final_price_lower' => [
                ItemCalculationPrice::ORIGIN_WITH_REVERT,
                5.0,
                [],
                3.0
            ],
            'final_price_same' => [
                ItemCalculationPrice::ORIGIN_WITH_REVERT,
                5.0,
                [
                    'calculation_price' => 10.0,
                    'original_price' => '10.0',
                ],
                5.0
            ],
            'final_price_bigger' => [
                ItemCalculationPrice::ORIGIN_WITH_REVERT,
                5.0,
                [
                    'calculation_price' => 12.0,
                    'original_price' => '10.0',
                ],
                7.0
            ],
            'lower_then_discount' => [
                ItemCalculationPrice::ORIGIN_WITH_REVERT,
                5.0,
                [
                    'calculation_price' => 4.0,
                    'original_price' => '10.0',
                ],
                0.0
            ]
        ];
    }

    /**
     * @covers ItemCalculationPrice::resolveBaseFinalPriceRevert
     * @dataProvider resolveBaseRevertData
     *
     * @param int $priceSelector
     * @param float $discount
     * @param array $additionalItemData
     * @param float $expected
     */
    public function testResolveBaseFinalPriceRevert(int $priceSelector, $discount, $additionalItemData, $expected)
    {
        $this->itemCalculationPrice->setPriceSelector($priceSelector);
        $this->item->addData($additionalItemData);
        $result = $this->itemCalculationPrice->resolveBaseFinalPriceRevert($discount, $this->item);

        $this->assertSame($expected, $result);
    }

    public function resolveBaseRevertData()
    {
        return [
            'without_revert' => [
                ItemCalculationPrice::DEFAULT_PRICE,
                10.0,
                [],
                10.0
            ],
            'final_price_lower' => [
                ItemCalculationPrice::ORIGIN_WITH_REVERT,
                10.0,
                [],
                6.0
            ],
            'final_price_same' => [
                ItemCalculationPrice::ORIGIN_WITH_REVERT,
                10.0,
                [
                    'base_calculation_price' => 20.0,
                ],
                10.0
            ],
            'final_price_bigger' => [
                ItemCalculationPrice::ORIGIN_WITH_REVERT,
                10.0,
                [
                    'base_calculation_price' => 22.0,
                ],
                12.0
            ],
            'lower_then_discount' => [
                ItemCalculationPrice::ORIGIN_WITH_REVERT,
                10.0,
                [
                    'base_calculation_price' => 8.0,
                ],
                0.0
            ]
        ];
    }
}
