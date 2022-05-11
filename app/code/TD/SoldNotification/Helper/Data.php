<?php
/**
 * Recent Order Notification
 * @author      Trinh Doan
 * @copyright   Copyright (c) 2017 Trinh Doan
 * @package     TD_SoldNotification
 */

namespace TD\SoldNotification\Helper;

use Magento\Framework\App\Helper\AbstractHelper;

/**
 * Class Data
 * @package Gielberkers\Example\Helper
 */
class Data extends AbstractHelper
{
    public function getTimeAgo($dattime)
    {
        $current_time = time();
        $time = strtotime($dattime);
        $different_time = round(($current_time - $time) / 60);
        $output_min = '';
        $output_hour = '';
        $output_day = '';
        $output_sec = '';
        $hour = round($different_time / 60);
        $hour_mod = ($different_time % 60);
        $min = ($different_time % 60);
        $min_mod = $different_time % 60;
        $day = round($different_time / (60 * 24));
        $day_mod = $different_time % (60 * 24);
        $second = $current_time - $time;
        if ($day > 1) {
            $output_day = $day . ' ' . __('days') . ' ';
        }
        if ($day == 1) {
            $output_day = __('a day') . ' ';
        }
        if ($day < 1) {
            if ($hour > 1) {
                $output_hour = $hour . ' ' . __('hours') . ' ';
            }
            if ($hour == 1) {
                $output_hour = __('an hour') . ' ';
                if ($hour_mod > 1) {
                    $output_min = $hour_mod . ' ' . __('minutes') . ' ';
                }
            }
            if ($hour == 0) {
                if ($min > 10) {
                    $output_min = $min . ' ' . __('minutes') . ' ';
                }
                if ($min > 1 && $min < 10) {
                    $output_min = __('a few minutes') . ' ';
                }
                if ($min == 1) {
                    $output_min = __('a minute') . ' ';
                }
                if ($min == 0) {
                    $output_sec = ' ' . __('a few seconds') . ' ';
                }

            }
        }

        $output = $output_day . $output_hour . $output_min . $output_sec . __('ago');

        return $output;
    }

    public function getFirstTime()
    {
        return 5000;
    }

    public function getTimeDelay()
    {
        return 15000;
    }
}