<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2021 Amasty (https://www.amasty.com)
 * @package Amasty_Faq
 */


namespace Amasty\Faq\Model\Import\Question\Behaviors;

use Magento\Framework\Exception\CouldNotSaveException;

class Add extends AbstractBehavior
{
    /**
     * @param array $importData
     *
     * @return void
     */
    public function execute(array $importData)
    {
        $this->setStores();
        foreach ($importData as $questionData) {
            $question = $this->questionFactory->create();
            $this->setQuestionData($question, $questionData);
            try {
                $this->repository->save($question);
            } catch (CouldNotSaveException $e) {
                null;
            }
        }
    }
}
