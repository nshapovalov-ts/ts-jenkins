<?php declare(strict_types=1);

namespace Retailplace\CustomerAccount\Model\Service;

class Abn
{
    /**
     * Lookup for party name based on ABN
     *
     * @param $abn
     * @return bool
     */
    public function getRecordFromAbrNumber($abn)
    {
        try {
            $abn = preg_replace("/\s+/", "", $abn);
            $client = new \SoapClient('http://abr.business.gov.au/abrxmlsearch/ABRXMLSearch.asmx?wsdl', ['connection_timeout' => 3]);

            $params = new \stdClass();
            $params->searchString = $abn;
            $params->includeHistoricalDetails = 'N';
            $params->authenticationGuid = '7ce68f24-188d-4f4e-9fd6-0f479b215173';

            if (strlen($abn) == 11) {
                $response = $client->ABRSearchByABN($params);
            } else {
                $response = $client->ABRSearchByASIC($params);
            }
            if (isset($response->ABRPayloadSearchResults->response->businessEntity) && isset($response->ABRPayloadSearchResults->response->businessEntity->entityStatus)) {
                $business_entity = $response->ABRPayloadSearchResults->response->businessEntity;

                if (is_array($business_entity->ABN)) {
                    $abn = end($business_entity->ABN);
                } else {
                    $abn = $business_entity->ABN;
                }
                $status = $response->ABRPayloadSearchResults->response->businessEntity->entityStatus->entityStatusCode;
                if ($abn && $status == "Active") {
                    return true;
                }
            }
        } catch (\Exception $exception) {
            //Do nothing
        }
        return false;
    }
}
