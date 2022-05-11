<?php

/**
 * Retailplace_MiraklSeller
 *
 * @copyright   Copyright (c) 2021 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Dmitriy Fionov <dmitriy@tradesquare.com.au>
 * @author      Ruslan Mukhatov <ruslan@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\MiraklSeller\Block;

class SellerTab extends Seller
{
    /**
     * @return SellerTab|void
     */
    public function _prepareLayout()
    {

    }

    /**
     * @return mixed
     */
    public function isEnabled()
    {
        return $this->helper->isEnabled();
    }

    /**
     * @return int
     */
    public function getProductCount()
    {
        return $this->getLayer()->getProductCollection()->getSize();
    }

    /**
     * @return int|void
     */
    public function getSellerCount()
    {
        return $this->getCollection()->getSize();
    }
}
