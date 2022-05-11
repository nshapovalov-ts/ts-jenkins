<?php
namespace Mirakl\Core\Test\Integration;

use Mirakl\Process\Model\ProcessFactory;
use Mirakl\Process\Model\ResourceModel\ProcessFactory as ProcessResourceFactory;

class TestCase extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * @var ProcessFactory;
     */
    protected $processFactory;

    /**
     * @var ProcessResourceFactory
     */
    protected $processResourceFactory;

    protected function setUp(): void
    {
        $this->objectManager = \Magento\TestFramework\Helper\Bootstrap::getObjectManager();
        $this->processFactory = $this->objectManager->create(ProcessFactory::class);
        $this->processResourceFactory = $this->objectManager->create(ProcessResourceFactory::class);
    }

    /**
     * @param   string  $fileName
     * @return  bool|string
     */
    protected function getFileContents($fileName)
    {
        return file_get_contents($this->getFilePath($fileName));
    }

    /**
     * @return  string
     */
    protected function getFilesDir()
    {
        return realpath(dirname((new \ReflectionClass(static::class))->getFileName()) . '/_files');
    }

    /**
     * @param   string  $file
     * @return  string
     */
    protected function getFilePath($file)
    {
        return $this->getFilesDir() . '/' . $file;
    }

    /**
     * @param   string  $fileName
     * @param   bool    $assoc
     * @return  array
     */
    protected function _getJsonFileContents($fileName, $assoc = true)
    {
        return json_decode($this->getFileContents($fileName), $assoc);
    }
}
