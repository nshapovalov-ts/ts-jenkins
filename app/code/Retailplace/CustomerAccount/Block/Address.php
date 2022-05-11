<?php declare(strict_types=1);

namespace Retailplace\CustomerAccount\Block;

use Magento\Framework\Exception\NoSuchEntityException;

class Address extends \Magento\Customer\Block\Account\Dashboard\Address
{
    /**
     * @return \Magento\Customer\Api\Data\AddressInterface|null
     */
    public function getDefaultShippingAddress()
    {
        try {
            return $this->currentCustomerAddress->getDefaultShippingAddress();
        } catch (NoSuchEntityException $e) {
            return null;
        }
    }

    /**
     * Return the id of the region being edited.
     *
     * @return int region id
     */
    public function getRegionId()
    {
        $region = $this->getDefaultShippingAddress()->getRegion();
        return $region === null ? 0 : $region->getRegionId();
    }

    /**
     * Return the name of the region for the address being edited.
     *
     * @return string region name
     */
    public function getRegion()
    {
        $region = $this->getDefaultShippingAddress()->getRegion();
        return $region === null ? '' : $region->getRegion();
    }

    /**
     * Return the specified numbered street line.
     *
     * @param int $lineNumber
     * @param $street
     * @return string
     */
    public function getStreetLine($lineNumber, $street)
    {
        return isset($street[$lineNumber - 1]) ? $street[$lineNumber - 1] : '';
    }
}
