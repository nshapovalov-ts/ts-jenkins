<?php

/**
 * Retailplace_MiraklFrontendDemo
 *
 * @copyright   Copyright (c) 2021 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Alexander Korsun <aleksandr@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\MiraklFrontendDemo\Plugin\Helper;

use Mirakl\FrontendDemo\Helper\Message as HelperMessage;
use Closure;
use Mirakl\MMP\Common\Domain\Collection\Message\Thread\ThreadParticipantCollection;
use Mirakl\MMP\Common\Domain\Message\Thread\ThreadParticipant;
use Mirakl\MMP\Common\Domain\Message\Thread\Thread;

class Message extends HelperMessage
{

    /**
     * @param Thread $thread
     * @param array $excludeTypes
     * @return  array
     */
    public function aroundGetCurrentParticipantsNames(
        HelperMessage $subject,
        Closure $proceed,
        Thread $thread,
        $excludeTypes = []
    ) {
        return $this->getParticipantsNames($thread->getCurrentParticipants(), $excludeTypes);
    }

    /**
     * @param Thread $thread
     * @param array $excludeTypes
     * @return  array
     */
    public function aroundGetAuthorizedParticipantsNames(
        HelperMessage $subject,
        Closure $proceed,
        Thread $thread,
        array $excludeTypes = []
    ) {
        return $this->getParticipantsNames($thread->getAuthorizedParticipants(), $excludeTypes);
    }

    /**
     * @param ThreadParticipantCollection $participants
     * @param array $excludeTypes
     * @return array
     */
    public function getParticipantsNames(
        ThreadParticipantCollection $participants,
        array $excludeTypes = []
    ) {
        $participantsNames = [];

        /** @var ThreadParticipant $participant */
        foreach ($participants as $participant) {
            if (!empty($excludeTypes) && in_array($participant->getType(), $excludeTypes)) {
                continue;
            }
            $displayName = $participant->getDisplayName();
            if ($displayName == "Operator") {
                $displayName = "Tradesquare Support";
            }
            $participantsNames[] = $displayName;
        }

        return $participantsNames;
    }

    /**
     * @param HelperMessage $subject
     * @param $topicValue
     * @return  string
     */
    public function afterGetTopic(
        HelperMessage $subject,
        $topicValue
    ): string {
        return __($topicValue)->render();
    }
}
