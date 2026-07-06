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

use Exception;
use HeadlessChromium\Communication\ResponseReader;
use HeadlessChromium\Exception\FilesystemException;
use HeadlessChromium\Exception\ScreenshotFailed;

abstract class AbstractBinaryInput
{
    // must be a multiple of four so that each base64 chunk can be decoded on its own
    private const WRITE_CHUNK_SIZE = 1024 * 1024;

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
     * The data is decoded and written to the stream in chunks, avoiding
     * allocating the decoded data in memory as a whole. The given stream
     * must be blocking.
     *
     * @param resource|null $stream  If not provided, a php://temp stream is opened
     * @param int           $timeout
     *
     * @throws FilesystemException
     * @throws ScreenshotFailed
     *
     * @return resource
     */
    public function saveToStream($stream = null, int $timeout = 5000)
    {
        $response = $this->responseReader->waitForResponse($timeout);

        if (!$response->isSuccessful()) {
            throw $this->getException($response->getErrorMessage());
        }

        $ownStream = null === $stream;

        if ($ownStream) {
            $stream = \fopen('php://temp', 'r+');

            if (false === $stream) {
                throw new FilesystemException('Could not open a temporary stream.');
            }
        } elseif (!\is_resource($stream)) {
            throw new FilesystemException('The given stream is not a valid stream resource.');
        }

        $data = (string) $response->getResultData('data');
        $length = \strlen($data);

        for ($offset = 0; $offset < $length; $offset += self::WRITE_CHUNK_SIZE) {
            $chunk = \base64_decode(\substr($data, $offset, self::WRITE_CHUNK_SIZE), true);

            if (false === $chunk) {
                throw $this->getException('Failed to decode the data to save.');
            }

            $chunkLength = \strlen($chunk);
            $written = 0;

            while ($written < $chunkLength) {
                $result = @\fwrite($stream, 0 === $written ? $chunk : \substr($chunk, $written));

                if (false === $result || 0 === $result) {
                    throw new FilesystemException('Could not write to the given stream.');
                }

                $written += $result;
            }
        }

        if (!\fflush($stream)) {
            throw new FilesystemException('Could not flush the given stream.');
        }

        if ($ownStream) {
            \rewind($stream);
        }

        return $stream;
    }

    /**
     * @internal
     *
     * @return Exception
     */
    abstract protected function getException(string $message): Exception;
}
