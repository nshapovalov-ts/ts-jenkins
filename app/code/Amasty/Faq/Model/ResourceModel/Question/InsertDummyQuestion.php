<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2021 Amasty (https://www.amasty.com)
 * @package Amasty_Faq
 */


namespace Amasty\Faq\Model\ResourceModel\Question;

use Amasty\Faq\Api\Data\QuestionInterface;
use Amasty\Faq\Setup\Operation\CreateQuestionTable;

class InsertDummyQuestion extends \Amasty\Faq\Model\ResourceModel\AbstractDummy
{
    public function _construct()
    {
        $this->_init(CreateQuestionTable::TABLE_NAME, QuestionInterface::QUESTION_ID);
    }
}
