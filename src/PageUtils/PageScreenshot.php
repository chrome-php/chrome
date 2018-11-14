<?php
/**
 * @license see LICENSE
 */

namespace HeadlessChromium\PageUtils;

use HeadlessChromium\Communication\ResponseReader;
use HeadlessChromium\Exception\FilesystemException;
use HeadlessChromium\Exception\ScreenshotFailed;

class PageScreenshot
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
    public function getResponseReader(): ResponseReader
    {
        return $this->responseReader;
    }

    /**
     * Get base64 representation of the file
     * @return mixed
     * @throws ScreenshotFailed
     */
    public function getBase64()
    {
        $response = $this->responseReader->waitForResponse();

        if (!$response->isSuccessful()) {
            throw new ScreenshotFailed(
                sprintf('Cannot make a screenshot. Reason : %s', $response->getErrorMessage())
            );
        }

        return $response->getResultData('data');
    }

    /**
     * Save screenshot to the given file
     * @param string $path
     * @throws FilesystemException
     * @throws ScreenshotFailed
     */
    public function saveToFile(string $path, int $timeout = 5000)
    {

        $response = $this->responseReader->waitForResponse($timeout);

        if (!$response->isSuccessful()) {
            throw new ScreenshotFailed(
                sprintf('Cannot make a screenshot. Reason : %s', $response->getErrorMessage())
            );
        }

        // create directory
        $dir = dirname($path);
        if (!file_exists($dir)) {
            if (!mkdir($dir, 0777, true)) {
                throw new FilesystemException(
                    sprintf('Could not create the directory %s.', $dir)
                );
            }
        }

        // save screenshot
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
}
