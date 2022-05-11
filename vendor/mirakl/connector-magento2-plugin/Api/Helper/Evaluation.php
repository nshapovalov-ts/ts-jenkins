<?php
namespace Mirakl\Api\Helper;

use Mirakl\MMP\Common\Domain\Collection\Evaluation\AssessmentCollection;
use Mirakl\MMP\FrontOperator\Request\Order\Evaluation\GetAssessmentsRequest;

class Evaluation extends ClientHelper\MMP
{
    /**
     * (EV01) Fetches the evaluation criterias used to evaluate an order
     *
     * @param   string  $locale
     * @return  AssessmentCollection
     */
    public function getAssessments($locale = null)
    {
        $request = new GetAssessmentsRequest();
        $request->setLocale($this->validateLocale($locale));

        return $this->send($request);
    }
}
