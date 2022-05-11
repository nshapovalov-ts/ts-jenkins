<?php

namespace Retailplace\MiraklApi\Plugin\Helper;

use Mirakl\Api\Helper\Message as HelperMessage;
use Mirakl\MMP\Common\Domain\Collection\SeekableCollection;
use Mirakl\MMP\Common\Domain\Message\Thread\ThreadDetails;
use Psr\Log\LoggerInterface;
use Retailplace\MiraklFrontendDemo\Api\MessagesStatsRepositoryInterface;
use Retailplace\MiraklFrontendDemo\Api\MessagesRepositoryInterface;

class Message
{
    /**
     * @var MessagesStatsRepositoryInterface
     */
    private $messagesStatsRepository;
    /**
     * @var LoggerInterface
     */
    protected $logger;
    /**
     * @var MessagesRepositoryInterface
     */
    private $messagesRepository;

    /**
     * @param MessagesStatsRepositoryInterface $messagesStatsRepository
     * @param MessagesRepositoryInterface $messagesRepository
     * @param LoggerInterface $logger
     */
    public function __construct(
        MessagesStatsRepositoryInterface $messagesStatsRepository,
        MessagesRepositoryInterface $messagesRepository,
        LoggerInterface $logger
    ) {
        $this->messagesStatsRepository = $messagesStatsRepository;
        $this->messagesRepository = $messagesRepository;
        $this->logger = $logger;
    }

    /**
     * @param HelperMessage $subject
     * @param SeekableCollection $threads
     * @return SeekableCollection
     */
    public function afterGetThreads(HelperMessage $subject, SeekableCollection $threads)
    {
        try {
            $this->messagesStatsRepository->updateThreads($threads);
            $this->messagesStatsRepository->updateMessageCounter();
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
        }

        return $threads;
    }

    /**
     * @param HelperMessage $subject
     * @param ThreadDetails $thread
     * @return ThreadDetails
     */
    public function afterGetThreadDetails(HelperMessage $subject, ThreadDetails $thread)
    {
        try {
            $this->messagesRepository->updateMessages($thread);
            $this->messagesStatsRepository->updateMessageCounter();
        } catch (\Exception $e) {
            $this->logger->warning($e->getMessage());
        }

        return $thread;
    }
}
