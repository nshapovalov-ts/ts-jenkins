<?php

/**
 * Retailplace_MiraklFrontendDemo
 *
 * @copyright   Copyright (c) 2021 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Alexander Korsun <aleksandr@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\MiraklFrontendDemo\Model\Response\Http;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\ResponseInterface;
use InvalidArgumentException;
use Exception;
use Magento\Framework\Filesystem;
use Magento\Framework\Exception\FileSystemException;

class FileFactory
{
    /**
     * @var ResponseInterface
     */
    protected $response;

    /**
     * @var Filesystem
     */
    protected $filesystem;

    /**
     * @param ResponseInterface $response
     * @param Filesystem $filesystem
     */
    public function __construct(
        ResponseInterface $response,
        Filesystem $filesystem
    ) {
        $this->response = $response;
        $this->filesystem = $filesystem;
    }

    /**
     * Declare headers and content file in response for file download
     *
     * @param string $fileName
     * @param string|array $content set to null to avoid starting output, $contentLength should be set explicitly in
     *                              that case
     * @param string $baseDir
     * @param string $contentType
     * @param int|null $contentLength explicit content length, if strlen($content) isn't applicable
     *
     * @throws FileSystemException
     * @throws Exception
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function create(
        string $fileName,
        $content,
        string $baseDir = DirectoryList::ROOT,
        string $contentType = 'application/octet-stream',
        ?int $contentLength = null
    ) {
        $dir = $this->filesystem->getDirectoryWrite($baseDir);
        $isFile = false;
        $file = null;

        if (isset($content['type']) && $content['type'] === 'string') {
            $fileContent = $content['value'];
        } else {
            $fileContent = $content;
        }

        if (is_array($content)) {
            if (!isset($content['type']) || !isset($content['value'])) {
                throw new InvalidArgumentException("Invalid arguments. Keys 'type' and 'value' are required.");
            }
            if ($content['type'] == 'filename') {
                $isFile = true;
                $file = $content['value'];
                if (!$dir->isFile($file)) {
                    // phpcs:ignore Magento2.Exceptions.DirectThrow
                    throw new Exception((string) new \Magento\Framework\Phrase('File not found'));
                }
                $contentLength = $dir->stat($file)['size'];
            }
        }
        $this->response->setHttpResponseCode(200)
            ->setHeader('Pragma', 'public', true)
            ->setHeader('Cache-Control', 'must-revalidate=0, post-check=0, pre-check=0, no-cache=1, max-age=0, no-store=1', true)
            ->setHeader('Content-type', $contentType, true)
            ->setHeader('Content-Length', $contentLength === null ? strlen($fileContent) : $contentLength, true)
            ->setHeader('Content-Disposition', 'attachment; filename="' . $fileName . '"', true)
            ->setHeader('Last-Modified', date('r'), true);

        if ($content !== null) {
            $this->response->sendHeaders();
            if ($isFile) {
                $stream = $dir->openFile($file, 'r');
                while (!$stream->eof()) {
                    // phpcs:ignore Magento2.Security.LanguageConstruct.DirectOutput
                    echo $stream->read(1024);
                }
            } else {
                $dir->writeFile($fileName, $fileContent);
                $file = $fileName;
                $stream = $dir->openFile($fileName, 'r');
                while (!$stream->eof()) {
                    // phpcs:ignore Magento2.Security.LanguageConstruct.DirectOutput
                    echo $stream->read(1024);
                }
            }
            $stream->close();
            flush();
            if (!empty($content['rm'])) {
                $dir->delete($file);
            }
        }

        die();
    }
}
