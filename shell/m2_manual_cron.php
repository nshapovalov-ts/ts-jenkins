<?php
error_reporting(E_ALL);
@ini_set('display_errors', 1);
@ini_set('memory_limit', '-1');
set_time_limit(0);

use Magento\Framework\App\Bootstrap;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
require 'app/bootstrap.php';
if (php_sapi_name() !== 'cli' && isset($_GET['job'])) {
    define('CRONJOBCLASS', $_GET['job']);
} elseif (php_sapi_name() !== 'cli') {
    die('Please add the class of the cron job you want to execute as a job parameter (?job=Vendor\Module\Class)');
} elseif (!isset($argv[1])) {
    die('Please add the class of the cron job you want to execute enclosed IN DOUBLE QUOTES as a parameter.' . PHP_EOL);
} else {
    define('CRONJOBCLASS', $argv[1]);
}

class CronRunner extends \Magento\Framework\App\Http implements \Magento\Framework\AppInterface
{
    public function __construct(
        \Magento\Framework\App\State $state,
        \Magento\Framework\App\Response\Http $response,
        \Magento\MessageQueue\Test\Unit\Console\StartConsumerCommandTest $testCase
    ) {
        $this->testCase = $testCase;
        $this->_response = $response;
        $state->setAreaCode('adminhtml');
    }

    public function launch()
    {
        $input = $this->testCase->getMockBuilder(\Symfony\Component\Console\Input\InputInterface::class)
            ->disableOriginalConstructor()->getMock();
        $output = $this->testCase->getMockBuilder(\Symfony\Component\Console\Output\OutputInterface::class)
            ->disableOriginalConstructor()->getMock();
        $cron = \Magento\Framework\App\ObjectManager::getInstance()
            ->create(CRONJOBCLASS);
        echo "<pre>";
        $cron->execute($input, $output);
        echo "</pre>";
        return $this->_response;
    }
}

$bootstrap = \Magento\Framework\App\Bootstrap::create(BP, $_SERVER);
$app = $bootstrap->createApplication('CronRunner');
$bootstrap->run($app);

//
// $params = $_SERVER;
//
// $bootstrap = Bootstrap::create(BP, $params);
//
// $obj = $bootstrap->getObjectManager();
//
// $state = $obj->get('Magento\Framework\App\State');
// $state->setAreaCode(\Magento\Framework\App\Area::AREA_GLOBAL);
// $product = $obj->get('Magento\Analytics\Cron\SignUp')->execute();
// $product = $obj->get('Magento\Analytics\Cron\Update')->execute();
// $product = $obj->get('Magento\Analytics\Cron\CollectData')->execute();
// echo "done";
