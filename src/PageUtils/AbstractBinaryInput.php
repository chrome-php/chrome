<?php
/**
 * @license see LICENSE
 */

namespace HeadlessChromium\PageUtils;

use HeadlessChromium\Communication\ResponseReader;
use HeadlessChromium\Exception\FilesystemException;
use HeadlessChromium\Exception\ScreenshotFailed;

abstract class AbstractBinaryInput
{
    /**
     * @var ResponseReader
     */
    protected $responseReader;

    /**
     * @param ResponseReader $responseReader
     */
    public function __construct(ResponseReader $responseReader)
    {
        $this->responseReader = $responseReader;
    }

    /**
     * @return ResponseReader
     */
    public function getResponseReader()
    {
        return $this->responseReader;
    }

    /**
     * Get base64 representation of the file
     * @return mixed
     */
    public function getBase64()
    {
        $response = $this->responseReader->waitForResponse();

        if (!$response->isSuccessful()) {
            throw $this->getException($response->getErrorMessage());
        }

        return $response->getResultData('data');
    }

    /**
     * Save data to the given file
     * @param string $path
     * @throws FilesystemException
     * @throws ScreenshotFailed
     */
    public function saveToFile($path, $timeout = 5000)
    {
        $response = $this->responseReader->waitForResponse($timeout);

        if (!$response->isSuccessful()) {
            throw $this->getException($response->getErrorMessage());
        }

        // create directory
        $dir = dirname($path);
        if (!file_exists($dir)) {
            if (!mkdir($dir, 0777, true) || !is_dir($dir)) {
                throw new FilesystemException(
                    sprintf('Could not create the directory %s.', $dir)
                );
            }
        }

        // save
        if (file_exists($path)) {
            if (!is_writable($path)) {
                throw new FilesystemException(
                    sprintf('The file %s is not writable.', $path)
                );
            }
        } else {
            if (!touch($path)) {
                throw new FilesystemException(
                    sprintf('The file %s could not be created.', $path)
                );
            }
        }

        $file = fopen($path, 'wb');
        stream_filter_append($file, 'convert.base64-decode');
        fwrite($file, $response->getResultData('data'));
        fclose($file);
    }

    /**
     * @internal
     * @return \Exception
     */
    abstract protected function getException($message);
}
