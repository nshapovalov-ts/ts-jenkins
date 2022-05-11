<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2021 Amasty (https://www.amasty.com)
 * @package Amasty_Rules
 */


namespace Amasty\Rules\Api;

interface ExtendedValidatorInterface
{
    /**
     * @param $combineCondition
     * @param $type
     *
     * @return bool|null
     */
    public function validate($combineCondition, $type);
}
