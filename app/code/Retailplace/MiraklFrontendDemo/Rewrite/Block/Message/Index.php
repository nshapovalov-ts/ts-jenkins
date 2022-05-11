<?php

/**
 * Retailplace_MiraklFrontendDemo
 *
 * @copyright   Copyright (c) 2021 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Alexander Korsun <aleksandr@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\MiraklFrontendDemo\Rewrite\Block\Message;

use DateTimeInterface;
use Exception;
use Magento\Customer\Model\Session;
use Magento\Framework\Registry;
use Magento\Framework\View\Element\Template\Context;
use Magento\Framework\View\Page\Config;
use Mirakl\Api\Helper\Message as MessageApi;
use Mirakl\FrontendDemo\Block\Message\Index as MessageIndex;
use Retailplace\MiraklFrontendDemo\Api\MessagesStatsRepositoryInterface;

class Index extends MessageIndex
{
    /**
     * @var Registry
     */
    protected $coreRegistry;

    /**
     * @var MessageApi
     */
    protected $messageApi;

    /**
     * @var Session
     */
    protected $customerSession;

    /**
     * @var string
     */
    protected $_template = 'Mirakl_FrontendDemo::message/index.phtml';

    /**
     * @var array
     */
    protected $availableLimit = [10 => 10, 20 => 20, 50 => 50];

    /**
     * @var int
     */
    protected $defaultLimit = 10;

    /**
     * @var int
     */
    protected $limit = 10;

    /**
     * @var Config
     */
    protected $pageConfig;
    /**
     * @var MessagesStatsRepositoryInterface
     */
    private $messagesStatsRepository;

    /**
     * @param Registry $coreRegistry
     * @param MessageApi $messageApi
     * @param Session $customerSession
     * @param Context $context
     * @param MessagesStatsRepositoryInterface $messagesStatsRepository
     * @param array $data
     */
    public function __construct(
        Registry $coreRegistry,
        MessageApi $messageApi,
        Session $customerSession,
        Context $context,
        MessagesStatsRepositoryInterface $messagesStatsRepository,
        array $data = []
    ) {
        parent::__construct($coreRegistry, $messageApi, $customerSession, $context, $data);
        $this->messagesStatsRepository = $messagesStatsRepository;
    }

    /**
     * @param DateTimeInterface|string $date
     * @return  string
     * @throws Exception
     */
    public function formatDateLong($date): string
    {
        $gmt = new \DateTimeZone('GMT');
        $date = $date instanceof DateTimeInterface ? $date : new \DateTime($date);
        $date->setTimezone($gmt);

        $now = new \DateTime();
        $now->setTimezone($gmt);

        if ($date->format('Ymd') == $now->format('Ymd')) {
            return $this->formatTime(\Mirakl\date_format($date), null, null, "dd/MM/Y, hh:mm");
        }

        return $this->formatDate(\Mirakl\date_format($date), null, null, null, "dd/MM/Y, hh:mm");
    }

    /**
     * Retrieve formatting date
     *
     * @param null|string|DateTimeInterface $date
     * @param int $format
     * @param bool $showTime
     * @param null|string $timezone
     * @param string $pattern
     * @return string
     * @throws Exception
     */
    public function formatDate(
        $date = null,
        $format = \IntlDateFormatter::SHORT,
        $showTime = false,
        $timezone = null,
        $pattern = null
    ): string {
        $date = $date instanceof DateTimeInterface ? $date : new \DateTime($date);
        return $this->_localeDate->formatDateTime(
            $date,
            $format,
            $showTime ? $format : \IntlDateFormatter::NONE,
            null,
            $timezone,
            $pattern
        );
    }

    /**
     * Retrieve formatting time
     *
     * @param \DateTime|string|null $time
     * @param int $format
     * @param bool $showDate
     * @param null $pattern
     * @return  string
     * @throws Exception
     */
    public function formatTime(
        $time = null,
        $format = \IntlDateFormatter::SHORT,
        $showDate = false,
        $pattern = null
    ): string {
        $time = $time instanceof \DateTimeInterface ? $time : new \DateTime($time);
        return $this->_localeDate->formatDateTime(
            $time,
            $showDate ? $format : \IntlDateFormatter::NONE,
            $format,
            null,
            null,
            $pattern
        );
    }

    /**
     * get Thread Info
     *
     * @return array
     * @throws Exception
     */
    public function getThreadInfo(): array
    {
        return $this->messagesStatsRepository->getThreadInfo();
    }

}
