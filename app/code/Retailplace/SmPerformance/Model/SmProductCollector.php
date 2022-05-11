<?php

/**
 * Retailplace_SmPerformance
 *
 * @copyright   Copyright (c) 2021 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Dmitriy Fionov <dmitriy@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\SmPerformance\Model;

/**
 * Class SmProductCollector
 */
class SmProductCollector
{
    /** @var array */
    private $productsList = [];

    /**
     * Products Setter
     *
     * @param array $products
     */
    public function setProducts(array $products)
    {
        $this->productsList = $products;
    }

    /**
     * Products Getter
     *
     * @return array
     */
    public function getProducts(): array
    {
        return $this->productsList;
    }
}
