<?php

namespace Ymow\OptimizePdf\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\Response\Http;
use Magento\Framework\Filesystem;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

class Data extends AbstractHelper
{
    /**
     * @var Http
     */
    protected $_response;
    /**
     * @var Filesystem
     */
    protected $_filesystem;
    /**
     * @var DirectoryList
     */
    protected $directoryList;

    /**
     * @param Context $context
     * @param Http $response
     * @param Filesystem $filesystem
     * @param DirectoryList $directoryList
     */
    public function __construct(
        Context $context,
        Http $response,
        Filesystem $filesystem,
        DirectoryList $directoryList
    ) {
        parent::__construct($context);
        $this->_response = $response;
        $this->_filesystem = $filesystem;
        $this->directoryList = $directoryList;
    }

    /**
     * @throws FileSystemException
     */
    public function optimizeFdfFileSize(
        $fileName,
        $content,
        $baseDir = DirectoryList::ROOT,
        $contentType = 'application/pdf'
    ) {
        $dir = $this->_filesystem->getDirectoryWrite($baseDir);
        $isFile = false;
        $file = null;
        $fileContent = $this->getFileContent($content);
        if (is_array($content)) {
            if (!isset($content['type']) || !isset($content['value'])) {
                throw new \InvalidArgumentException("Invalid arguments. Keys 'type' and 'value' are required.");
            }
            if ($content['type'] == 'filename') {
                $isFile = true;
                $file = $content['value'];
                if (!$dir->isFile($file)) {
                    throw new \Exception((string)new \Magento\Framework\Phrase('File not found'));
                }
            }
        }
        $this->_response->setHttpResponseCode(200)
            ->setHeader('Pragma', 'public', true)
            ->setHeader('Cache-Control', 'must-revalidate, post-check=0, pre-check=0', true)
            ->setHeader('Content-type', $contentType, true)
            ->setHeader('Content-Disposition', 'attachment; filename="' . $fileName . '"', true)
            ->setHeader('Last-Modified', date('r'), true);

        if ($content !== null) {
            $this->_response->sendHeaders();
            $fileNameNew = 'optimize_'.$fileName;
            $pathFolder = $this->directoryList->getPath($baseDir);
            $pathFileNameOld = $pathFolder.'/'.$fileName;
            $pathFileNameNew = $pathFolder.'/'.$fileNameNew;

            if ($isFile) {
                $this->createOptimizePdf($file, $pathFileNameNew);
                $stream = $dir->openFile($pathFileNameNew, 'r');
                while (!$stream->eof()) {
                    echo $stream->read(1024);
                }
            } else {
                $dir->writeFile($fileName, $fileContent);
                $this->createOptimizePdf($pathFileNameOld, $pathFileNameNew);
                $stream = $dir->openFile($fileNameNew, 'r');
                while (!$stream->eof()) {
                    echo $stream->read(1024);
                }
            }
            $stream->close();
            flush();
            $dir->delete($fileName);
            if (!empty($content['rm'])) {
                $dir->delete($fileNameNew);
            }
        }
        return $this->_response;
    }

    /**
     * @param $content
     * @return mixed
     */
    private function getFileContent($content)
    {
        if (isset($content['type']) && $content['type'] === 'string') {
            return $content['value'];
        }
        return $content;
    }

    /**
     * @param $pathFileNameOld
     * @param $pathFileNameNew
     * @return void
     */
    private function createOptimizePdf($pathFileNameOld, $pathFileNameNew)
    {
        $ghostscriptCommand = "gs -sDEVICE=pdfwrite -dCompatibilityLevel=1.4 -dPDFSETTINGS=/ebook -dNOPAUSE -dBATCH -sOutputFile=$pathFileNameNew $pathFileNameOld";
        $process = new Process($ghostscriptCommand);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }
    }
}

