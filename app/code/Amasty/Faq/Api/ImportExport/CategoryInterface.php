<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2021 Amasty (https://www.amasty.com)
 * @package Amasty_Faq
 */


namespace Amasty\Faq\Api\ImportExport;

interface CategoryInterface extends \Amasty\Faq\Api\Data\CategoryInterface
{
    const STORE_CODES = 'store_codes';

    const QUESTION_IDS = 'question_ids';
}
