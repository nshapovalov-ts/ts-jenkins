<?php
namespace Mirakl\FrontendDemo\Model\Evaluation;

class EvaluationFormatter
{
    /**
     * Formats evaluation in order to be compatible with X stars display
     *
     * @param   float   $evaluation
     * @param   int     $stars
     * @return  float
     */
    public static function format($evaluation, $stars = 5)
    {
        return round($evaluation * 100 / $stars, 2);
    }
}