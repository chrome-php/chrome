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
     * @param int|null $timeout
     *
     * @return mixed
     */
    public function getBase64(?int $timeout = null)
    {
        $response = $this->responseReader->waitForResponse($timeout);

        if (!$response->isSuccessful()) {
            throw $this->getException($response->getErrorMessage());
        }

        return $response->getResultData('data');
    }

    /**
     * Get raw binary data.
     *
     * @param int|null $timeout
     *
     * @return string
     */
    public function getRawBinary(?int $timeout = null): string
    {
        return \base64_decode($this->getBase64($timeout), true);
    }

    /**
     * Save data to the given file.
     *
     * @param string $path
     * @param int    $timeout
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
     * Save data to the given stream.
     *
     * @param resource|null $stream If not provided, a php://temp is opened
     * @param int|null      $timeout
     *
     * @throws FilesystemException
     *
     * @return resource
     */
    public function saveToStream($stream = null, int $timeout = 5000)
    {
        $response = $this->responseReader->waitForResponse($timeout);

        if (!$response->isSuccessful()) {
            throw $this->getException($response->getErrorMessage());
        }

        $ownStream = $stream === null;

        if ($ownStream) {
            $stream = \fopen('php://temp', 'r+');

            if ($stream === false) {
                throw new FilesystemException('Could not open a temporary stream.');
            }
        }

        $filter = \stream_filter_append($stream, 'convert.base64-decode', STREAM_FILTER_WRITE);
        \fwrite($stream, $response->getResultData('data'));
        \stream_filter_remove($filter);
        \fflush($stream);

        if ($ownStream) {
            \rewind($stream);
        }

        return $stream;
    }

    /**
     * @internal
     *
     * @return \Exception
     */
    abstract protected function getException(string $message): \Exception;
}
