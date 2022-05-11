<?php
namespace Mirakl\Api\Helper;

use Mirakl\MMP\Common\Domain\Reason\ReasonType;
use Mirakl\MMP\FrontOperator\Domain\Collection\Reason\ReasonCollection;
use Mirakl\MMP\FrontOperator\Request\Reason\GetReasonsRequest;
use Mirakl\MMP\FrontOperator\Request\Reason\GetTypeReasonsRequest;

class Reason extends ClientHelper\MMP
{
    /**
     * (RE01) Fetches reasons from Mirakl platform that can be used for opening incident or create a refund.
     *
     * @param   string  $locale
     * @return  ReasonCollection
     */
    public function getReasons($locale = null)
    {
        $request = new GetReasonsRequest();
        $request->setLocale($this->validateLocale($locale));

        return $this->send($request);
    }

    /**
     * (RE02) Fetches reasons by type
     *
     * @param   string  $type
     * @param   string  $locale
     * @return  ReasonCollection
     */
    public function getTypeReasons($type = ReasonType::INCIDENT_OPEN, $locale = null)
    {
        $request = new GetTypeReasonsRequest($type);
        $request->setLocale($this->validateLocale($locale));

        return $this->send($request);
    }

    /**
     * Fetches reasons for opening an incident
     *
     * @param   string  $locale
     * @return  ReasonCollection
     */
    public function getOpenIncidentReasons($locale = null)
    {
        return $this->getTypeReasons(ReasonType::INCIDENT_OPEN, $locale);
    }

    /**
     * Fetches reasons for closing an incident
     *
     * @param   string  $locale
     * @return  ReasonCollection
     */
    public function getCloseIncidentReasons($locale = null)
    {
        return $this->getTypeReasons(ReasonType::INCIDENT_CLOSE, $locale);
    }

    /**
     * Fetches reasons for sending an order message
     *
     * @param   string  $locale
     * @return  ReasonCollection
     */
    public function getOrderMessageReasons($locale = null)
    {
        return $this->getTypeReasons(ReasonType::ORDER_MESSAGING, $locale);
    }
}
