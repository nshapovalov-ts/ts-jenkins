<?php
namespace Mirakl\Api\Helper;

use Mirakl\MMP\Common\Domain\Collection\AdditionalFieldCollection;
use Mirakl\MMP\Front\Request\AdditionalField\GetAdditionalFieldRequest;

class AdditionalField extends ClientHelper\MMP
{
    /**
     * (AF01) Get the list of any additional fields
     *
     * @param   array   $entities   For example: ['OFFER', 'SHOP']
     * @param   string  $locale
     * @return  AdditionalFieldCollection
     */
    public function getAdditionalFields(array $entities, $locale = null)
    {
        $request = new GetAdditionalFieldRequest();
        $request->setEntities($entities);
        $request->setLocale($this->validateLocale($locale));

        return $this->send($request);
    }
}
