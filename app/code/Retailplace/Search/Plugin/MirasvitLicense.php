<?php

namespace Retailplace\Search\Plugin;

use Mirasvit\Core\Model\License;

class MirasvitLicense
{
    /**
     * @param License $subject
     * @param \Closure $proceed
     * @param string $className
     * @return bool
     */
    public function aroundGetStatus(
        License $subject,
        \Closure $proceed,
        $className = ''
    ) {
        return true;
    }
}
