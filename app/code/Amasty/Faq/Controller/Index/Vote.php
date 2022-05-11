<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2021 Amasty (https://www.amasty.com)
 * @package Amasty_Faq
 */


namespace Amasty\Faq\Controller\Index;

use Amasty\Faq\Exceptions\VotingNotAllowedException;
use Amasty\Faq\Model\Frontend\Rating\VotingService;
use Amasty\Faq\Model\OptionSource\Question;
use Amasty\Faq\Model\QuestionRepository;
use Magento\Framework\App\Action\Context;

class Vote extends \Magento\Framework\App\Action\Action
{
    /**
     * @var QuestionRepository
     */
    private $repository;

    /**
     * @var VotingService
     */
    private $votingService;

    public function __construct(
        Context $context,
        QuestionRepository $repository,
        VotingService $votingService
    ) {
        parent::__construct($context);
        $this->repository = $repository;
        $this->votingService = $votingService;
    }

    /**
     * @inheritDoc
     */
    public function execute()
    {
        /** @var \Magento\Framework\Controller\Result\Json $resultJson */
        $resultJson = $this->resultFactory->create(\Magento\Framework\Controller\ResultFactory::TYPE_JSON);
        $questionId = (int)$this->_request->getParam('id');
        $resultData = [
            'result' => [
                'code' => 'unknown-question',
                'message' => __('Question doesn\'t exists.')
            ]
        ];

        if ($questionId && $question = $this->getQuestion($questionId)) {
            try {
                $this->votingService->saveVotingData($this->_request, $question);
                $resultData = [
                    'result' => [
                        'code' => 'success',
                        'message' => __('You successfully voted.')
                    ]
                ];
            } catch (VotingNotAllowedException $e) {
                $resultJson->setHttpResponseCode(403);
                $resultData = [
                    'result' => [
                        'code' => $e->getMessageCode(),
                        'message' => $e->getMessage()
                    ]
                ];
            } catch (\Exception $e) {
                $resultData = [
                    'result' => [
                        'code' => 'error',
                        'message' => __('Can\'t save question.')
                    ]
                ];
            }
        }

        return $resultJson->setData($resultData);
    }

    private function getQuestion(int $questionId)
    {
        $question = $this->repository->getById($questionId);
        if ($question->getStatus() == Question\Status::STATUS_ANSWERED
            && $question->getVisibility() != Question\Visibility::VISIBILITY_NONE
        ) {
            return $question;
        }

        return false;
    }
}
