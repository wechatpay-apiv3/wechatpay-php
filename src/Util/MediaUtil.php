<?php
/**
 * MediaUtil
 * PHP version 5
 *
 * @category Class
 * @package  WechatPay
 * @author   WeChatPay Team
 * @link     https://pay.weixin.qq.com
 */

namespace WechatPay\GuzzleMiddleware\Util;

use GuzzleHttp\Psr7\LazyOpenStream;
use GuzzleHttp\Psr7\MultipartStream;
use GuzzleHttp\Psr7\FnStream;
use GuzzleHttp\Psr7\CachingStream;

/**
 * Util for Media(image or video) uploading.
 *
 * @package  WechatPay
 */
class MediaUtil {

    /**
     * local file path
     *
     * @var string
     */
    private $filepath;

    /**
     * file content stream to upload
     * @var string
     */
    private $fileStream;

    /**
     * upload meta json
     *
     * @var string
     */
    private $json;

    /**
     * upload contents stream
     *
     * @var MultipartStream
     */
    private $multipart;


    /**
     * multipart stream wrapper
     *
     * @var FnStream
     */
    private $stream;

    /**
     * Constructor
     *
     * @param string $filepath The media file path or file name,
     *                         should be one of the
     *                         images(jpg|bmp|png)
     *                         or
     *                         video(avi|wmv|mpeg|mp4|mov|mkv|flv|f4v|m4v|rmvb)
     * @param StreamInterface $fileStream  File content stream, optional
     */
    public function __construct($filepath, $fileStream = null)
    {
        $this->filepath = $filepath;
        $this->fileStream = $fileStream;
        $this->composeStream();
    }

    /**
     * Compose the GuzzleHttp\Psr7\FnStream
     */
    private function composeStream()
    {
        $basename = \basename($this->filepath);
        $stream = isset($this->fileStream) ? $this->fileStream : new LazyOpenStream($this->filepath, 'r');
        if (!$stream->isSeekable()) {
            $stream = new CachingStream($stream);
        }

        $json = \GuzzleHttp\json_encode([
            'filename' => $basename,
            'sha256'   => \GuzzleHttp\Psr7\hash($stream, 'sha256'),
        ]);
        $this->meta = $json;

        $multipart = new MultipartStream([
            [
                'name'     => 'meta',
                'contents' => $json,
                'headers'  => [
                    'Content-Type' => 'application/json',
                ],
            ],
            [
                'name'     => 'file',
                'filename' => $basename,
                'contents' => $stream,
            ],
        ]);
        $this->multipart = $multipart;

        $this->stream = FnStream::decorate($multipart, [
             // for signature
            '__toString' => function () use ($json) {
                return $json;
            },
             // let the `CURL` to use `CURLOPT_UPLOAD` context
            'getSize' => function () {
                return null;
            },
        ]);
    }

    /**
     * Get the `meta` of the multipart data string
     */
    public function getMeta()
    {
        return $this->meta;
    }

    /**
     * Get the `GuzzleHttp\Psr7\FnStream` context
     */
    public function getStream()
    {
        return $this->stream;
    }

    /**
     * Get the `Content-Type` of the `GuzzleHttp\Psr7\MultipartStream`
     */
    public function getContentType()
    {
        return 'multipart/form-data; boundary=' . $this->multipart->getBoundary();
    }
}
