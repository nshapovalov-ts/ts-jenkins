<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2021 Amasty (https://www.amasty.com)
 * @package Amasty_Rules
 */

declare(strict_types=1);

namespace Amasty\Rules\Ui\Form\Rule;

use Amasty\Rules\Model\Rule\ItemCalculationPrice;

/**
 * Options for dropdown "Calculate Discount Based On".
 * For usage @see ItemCalculationPrice
 */
class CalculateDiscountBasedOnOptions implements \Magento\Framework\Data\OptionSourceInterface
{
    /**
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['value' => ItemCalculationPrice::DEFAULT_PRICE, 'label' => __('Price (Special Price if Set)')],
            ['value' => ItemCalculationPrice::DISCOUNTED_PRICE, 'label' => __('Price After Previous Discount(s)')],
            [
                'value' => ItemCalculationPrice::ORIGIN_PRICE,
                'label' => __('Original Price, Apply to Special Price (if present)')
            ],
            [
                'value' => ItemCalculationPrice::ORIGIN_WITH_REVERT,
                'label' => __('Original Price, Apply to Original Price (skip if result is more than Special Price)')
            ],
        ];
    }
}
