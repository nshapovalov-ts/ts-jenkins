<?php
namespace Mirakl\FrontendDemo\Common;

use Mirakl\FrontendDemo\Model\Evaluation\EvaluationFormatter;
use Mirakl\MMP\Common\Domain\Evaluation;
use Mirakl\MMP\Common\Domain\Evaluation\Assessment;

trait AssessmentTrait
{
    /**
     * @param   Assessment  $assessment
     * @return  bool
     */
    public function isBooleanAssessment(Assessment $assessment)
    {
        return $assessment->getType() == Evaluation\AssessmentType::BOOLEAN;
    }

    /**
     * Calculates the percentage of an assessment response
     *
     * @param   Assessment  $assessment
     * @return  float
     */
    public function getAssessmentPercent(Assessment $assessment)
    {
        return EvaluationFormatter::format($assessment->getResponse());
    }

    /**
     * Calculates the percentage of an evaluation
     *
     * @param   Evaluation  $evaluation
     * @return  float
     */
    public function getEvaluationPercent(Evaluation $evaluation)
    {
        return EvaluationFormatter::format($evaluation->getGrade());
    }
}
