<?php
/**
 * @license see LICENSE
 */

namespace HeadlessChromium;

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
    public function saveToFile(string $path)
    {

        $response = $this->responseReader->waitForResponse();

        if (!$response->isSuccessful()) {
            throw new ScreenshotFailed(
                sprintf('Cannot make a screenshot. Reason : %s', $response->getErrorMessage())
            );
        }

        // create directory
        $dir = dirname($path);
        if (!file_exists($dir)) {
            if (!mkdir($path, 0777, true)) {
                throw new FilesystemException(
                    sprintf('Could not create the directory %s.', $dir)
                );
            }
        }

        // save screenshot
        if (!is_writable($path)) {
            throw new FilesystemException(
                sprintf('The file %s is not writable.', $path)
            );
        }

        $file = fopen($path, 'wb');
        stream_filter_append($file, 'convert.base64-decode');
        fwrite($file, $response->getResultData('data'));
        fclose($file);
    }
}
