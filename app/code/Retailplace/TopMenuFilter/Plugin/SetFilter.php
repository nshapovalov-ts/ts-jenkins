<?php
declare(strict_types=1);

/**
 * Retailplace_TopMenuFilter
 *
 * @copyright   Copyright (c) 2021 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Alexander Korsun <aleksandr@tradesquare.com.au>
 */

namespace Retailplace\TopMenuFilter\Plugin;

use Magento\Catalog\Model\Layer\Filter\Item;
use Magento\Catalog\Model\Layer\State;
use Amasty\Shopby\Model\Source\DisplayMode;
use Magento\Framework\Exception\LocalizedException;


class SetFilter
{

    /**
     * Before Add Filter
     *
     * @param State $subject
     * @param Item $filter
     * @return array
     * @throws LocalizedException
     */
    public function beforeAddFilter(State $subject, Item $filter): array
    {
        if ($filter->getFilter()->getRequestVar() == DisplayMode::ATTRUBUTE_PRICE) {
            $label = $filter->getLabel();

            $arguments = array_map(function ($v) {
                $patern = '/(\d+.\d+.\d+)|(\d+.\d+)/';
                if (preg_match($patern, $v, $matches)) {
                    $amount = $matches[0];
                    $amount = str_replace(",", "", $amount);
                    $amount = floatval($amount);
                    $amount = (string)round($amount);
                    return preg_replace($patern, $amount, $v);
                }

                return $v;
            }, $label->getArguments());
            $newLabel = __($label->getText(), $arguments);
            $filter->setLabel($newLabel);
        }

        return [$filter];
    }
}
