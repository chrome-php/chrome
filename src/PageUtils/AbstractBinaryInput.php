<?php

/*
 * This file is part of Chrome PHP.
 *
 * (c) Soufiane Ghzal <sghzal@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
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
    public function getResponseReader(): ResponseReader
    {
        return $this->responseReader;
    }

    /**
     * Get base64 representation of the file.
     *
     * @return mixed
     */
    public function getBase64(int $timeout = null)
    {
        $response = $this->responseReader->waitForResponse($timeout);

        if (!$response->isSuccessful()) {
            throw $this->getException($response->getErrorMessage());
        }

        return $response->getResultData('data');
    }

    /**
     * Save data to the given file.
     *
     * @param string $path
     *
     * @throws FilesystemException
     * @throws ScreenshotFailed
     */
    public function saveToFile(string $path, int $timeout = 5000): void
    {
        $response = $this->responseReader->waitForResponse($timeout);

        if (!$response->isSuccessful()) {
            throw $this->getException($response->getErrorMessage());
        }

        // create directory
        $dir = \dirname($path);
        if (!\file_exists($dir)) {
            if (!\mkdir($dir, 0777, true)) {
                throw new FilesystemException(\sprintf('Could not create the directory %s.', $dir));
            }
        }

        // save
        if (\file_exists($path)) {
            if (!\is_writable($path)) {
                throw new FilesystemException(\sprintf('The file %s is not writable.', $path));
            }
        } else {
            if (!\touch($path)) {
                throw new FilesystemException(\sprintf('The file %s could not be created.', $path));
            }
        }

        $file = \fopen($path, 'w');
        \stream_filter_append($file, 'convert.base64-decode');
        \fwrite($file, $response->getResultData('data'));
        \fclose($file);
    }

    /**
     * @internal
     *
     * @return \Exception
     */
    abstract protected function getException(string $message): \Exception;
}
