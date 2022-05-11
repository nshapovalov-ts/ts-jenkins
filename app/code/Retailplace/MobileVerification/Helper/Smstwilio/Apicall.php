<?php

/**
 * Retailplace_MobileVerification
 *
 * @copyright   Copyright (c) 2021 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Ruslan Mukhatov <ruslan@tradesquare.com.au>
 */

namespace Retailplace\MobileVerification\Helper\Smstwilio;

class Apicall extends \Magecomp\Smstwilio\Helper\Apicall
{
    /**
     * @return string
     */
    public function getMobileNumber(): string
    {
        return (string) $this->scopeConfig->getValue(
            self::XML_TWILIOSMS_MOBILENUMBER,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * @param string $mobilenumbers
     * @param string $message
     * @return bool|string
     */
    public function callApiUrl($mobilenumbers, $message)
    {
        try {
            $account_sid = $this->getAccountsid();
            $auth_token = $this->getAuthtoken();

            if (substr($mobilenumbers, 0, 1) !== '+') {
                $mobilenumbers = '+' . $mobilenumbers;
            }

            $client = new \Twilio\Rest\Client($account_sid, $auth_token);
            $returntwilio = $client->messages->create(
                $mobilenumbers,
                array('messagingServiceSid' => $this->getMobileNumber(), 'body' => $message)
            );

            if ($returntwilio->status == 'undelivered') {
                return false;
            }
            return true;
        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }
}
