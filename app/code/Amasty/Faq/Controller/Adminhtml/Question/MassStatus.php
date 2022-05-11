<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2021 Amasty (https://www.amasty.com)
 * @package Amasty_Faq
 */


namespace Amasty\Faq\Controller\Adminhtml\Question;

use Amasty\Faq\Api\Data\QuestionInterface;

class MassStatus extends \Amasty\Faq\Controller\Adminhtml\AbstractMassAction
{
    /**
     * @param QuestionInterface $question
     */
    protected function itemAction(QuestionInterface $question)
    {
        $question->setStatus($this->getRequest()->getParam('status'));
        $this->repository->save($question);
    }
}
