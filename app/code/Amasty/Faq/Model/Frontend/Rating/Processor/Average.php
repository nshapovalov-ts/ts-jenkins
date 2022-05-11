<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2021 Amasty (https://www.amasty.com)
 * @package Amasty_Faq
 */


declare(strict_types=1);

namespace Amasty\Faq\Model\Frontend\Rating\Processor;

use Amasty\Faq\Api\Data\QuestionInterface;
use Magento\Framework\App\RequestInterface;

class Average extends YesNoVoting
{
    public function getVotingData(array $questionIds): array
    {
        return array_map(function ($question) {
            $questionId = $question[QuestionInterface::QUESTION_ID];
            return [
                'id' => $questionId,
                'isVoted' => $this->voting->isVotedQuestion($questionId),
                'average' => $question[QuestionInterface::AVERAGE_RATING] ?? 0,
                'total' => $question[QuestionInterface::AVERAGE_TOTAL] ?? 0,
            ];
        }, $this->getQuestionCollection($questionIds)->getData());
    }

    public function saveVote(RequestInterface $request, QuestionInterface $question): void
    {
        if ($voteValue = (int)$request->getParam('starNumber')) {
            $average = $question->getAverageRating();
            $total = $question->getAverageTotal();
            $newAverage = ($average * $total + $voteValue) / ++$total;
            $question->setAverageRating($newAverage);
            $question->setAverageTotal($total);
            $this->repository->save($question);
            $this->voting->setVotedQuestion($question->getQuestionId());
        }
    }

    protected function getQuestionCollection(array $questionIds)
    {
        $questionCollection = parent::getQuestionCollection($questionIds);
        $questionCollection->addFieldToSelect([
            QuestionInterface::AVERAGE_RATING,
            QuestionInterface::AVERAGE_TOTAL,
        ]);

        return $questionCollection;
    }
}
