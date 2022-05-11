<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2021 Amasty (https://www.amasty.com)
 * @package Amasty_Faq
 */


namespace Amasty\Faq\Model\Import\Question\Behaviors;

use Amasty\Faq\Api\ImportExport\QuestionInterface;
use Magento\Framework\Exception\CouldNotDeleteException;

class Delete extends AbstractBehavior
{
    /**
     * @param array $importData
     *
     * @return void
     */
    public function execute(array $importData)
    {
        foreach ($importData as $questionData) {
            if (!empty($questionData[QuestionInterface::QUESTION_ID])) {
                try {
                    $this->repository->deleteById((int)$questionData[QuestionInterface::QUESTION_ID]);
                } catch (CouldNotDeleteException $e) {
                    null;
                }
            }
        }
    }
}
