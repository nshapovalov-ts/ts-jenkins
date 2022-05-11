<?php
/**
 * Retailplace_Wishlist
 *
 * @copyright   Copyright (c) 2022 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Alexander Korsun <aleksandr@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\Wishlist\Model\Wishlist;

use Magento\Wishlist\Helper\Data as WishlistHelper;
use Sm\Market\Helper\Data as SmMarketHelper;

class Config
{
    /**
     * @var SmMarketHelper
     */
    private $smMarketHelper;

    /**
     * @var WishlistHelper
     */
    private $wishlistHelper;

    /**
     * Config constructor.
     * @param SmMarketHelper $smMarketHelper
     * @param WishlistHelper $wishlistHelper
     */
    public function __construct(
        SmMarketHelper $smMarketHelper,
        WishlistHelper $wishlistHelper
    ) {
        $this->smMarketHelper = $smMarketHelper;
        $this->wishlistHelper = $wishlistHelper;
    }

    /**
     * @return bool
     */
    public function isAllow()
    {
        return $this->wishlistHelper->isAllow() && $this->smMarketHelper->getAdvanced('show_wishlist_button');
    }
}
