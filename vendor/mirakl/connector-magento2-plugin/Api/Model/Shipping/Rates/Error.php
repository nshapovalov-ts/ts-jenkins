<?php
namespace Mirakl\Api\Model\Shipping\Rates;

class Error
{
    const INTERNAL_SERVER_ERROR                    = 0;
    const HTTP_STATUS_CODE_EXCEPTION               = 1;
    const HTTP_MESSAGE_NOT_READABLE_EXCEPTION      = 2;
    const TENANT_NOT_FOUND_EXCEPTION               = 3;
    const PROVIDER_CONFIGURATION_SERVICE_EXCEPTION = 4;
    const PROVIDER_EXECUTION_SERVICE_EXCEPTION     = 5;
    const VALIDATION_SERVICE_EXCEPTION             = 6;
    const SERVICE_EXCEPTION                        = 7;
    const REQUEST_TIMEOUT_EXCEPTION                = 8;
    const ILLEGAL_ARGUMENT_SERVICE_EXCEPTION       = 9;

    /**
     * @param   int $code
     * @return  bool
     */
    public static function isProviderError($code)
    {
        return $code == self::PROVIDER_CONFIGURATION_SERVICE_EXCEPTION
            || $code == self::PROVIDER_EXECUTION_SERVICE_EXCEPTION;
    }
}