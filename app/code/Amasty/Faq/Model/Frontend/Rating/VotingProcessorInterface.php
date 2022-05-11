<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2021 Amasty (https://www.amasty.com)
 * @package Amasty_Faq
 */


namespace Amasty\Faq\Model\Frontend\Rating;

use Amasty\Faq\Api\Data\QuestionInterface;
use Amasty\Faq\Exceptions\VotingNotAllowedException;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\CouldNotSaveException;

interface VotingProcessorInterface
{
    /**
     * Returns voting data contains data for rendering on frontend
     *
     * @param array $questionIds
     * @return array
     */
    public function getVotingData(array $questionIds): array;

    /**
     * Process saving question's vote
     *
     * @param RequestInterface $request
     * @param QuestionInterface $question
     *
     * @throws CouldNotSaveException
     */
    public function saveVote(RequestInterface $request, QuestionInterface $question): void;
}
