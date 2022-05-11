<?php declare(strict_types=1);
/**
 * Retailplace_Offerdetail
 *
 * @copyright   Copyright (c) 2022 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Nikolay Shapovalov <nikolay@tradesquare.com.au>
 */

namespace Retailplace\Offerdetail\Plugin;

use Retailplace\Offerdetail\Model\ConfigProvider;
use Mirakl\Connector\Model\Offer;

/**
 * Class LeadTimeToShip implements plugin for adding default value for lead_time_to_ship field
 */
class LeadTimeToShip
{
    /**
     * @var ConfigProvider
     */
    private $configProvider;

    /**
     * @param ConfigProvider $configProvider
     */
    public function __construct(
        ConfigProvider $configProvider
    ) {
        $this->configProvider = $configProvider;
    }

    /**
     * @param Offer $subject
     * @param int|null $result
     * @return int|null
     */
    public function afterGetLeadtimeToShip(Offer $subject, ?int $result): ?int
    {
        if ($this->configProvider->isDefaultValueEnable() && !$result) {
            $result = $this->configProvider->leadTimeDefaultValue();
        }

        return $result;
    }
}
