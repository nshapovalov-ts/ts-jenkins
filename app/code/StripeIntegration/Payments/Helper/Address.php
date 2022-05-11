<?php

namespace StripeIntegration\Payments\Helper;

class Address
{
    public function getStripeAddressFromMagentoAddress($address)
    {
        if (empty($address))
            return null;

        $data = [
            "address" => [
                "line1" => $address->getStreetLine(1),
                "city" => $address->getCity(),
                "country" => $address->getCountryId(),
                "line2" => $address->getStreetLine(2),
                "postal_code" => $address->getPostcode(),
                "state" => $address->getRegion()
            ],
            "name" => $address->getName(),
            "email" => $address->getEmail(),
            "phone" => $address->getTelephone()
        ];

        foreach ($data['address'] as $key => $value) {
            if (empty($data['address'][$key]))
                unset($data['address'][$key]);
        }

        foreach ($data as $key => $value) {
            if (empty($data[$key]))
                unset($data[$key]);
        }

        return $data;
    }
}
