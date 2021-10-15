<?php declare(strict_types=1);

namespace WeChatPay\Util;

use function basename;
use function sprintf;
use function json_encode;

use UnexpectedValueException;

use GuzzleHttp\Psr7\Utils;
use GuzzleHttp\Psr7\BufferStream;
use GuzzleHttp\Psr7\LazyOpenStream;
use GuzzleHttp\Psr7\MultipartStream;
use GuzzleHttp\Psr7\FnStream;
use GuzzleHttp\Psr7\CachingStream;
use Psr\Http\Message\StreamInterface;

/**
 * Util for Media(image, video or text/csv whose are the platform acceptable file types etc) uploading.
 */
class MediaUtil
{
    /**
     * @var string - local file path
     */
    private $filepath;

    /**
     * @var ?StreamInterface - The `file` stream
     */
    private $fileStream;

    /**
     * @var StreamInterface - The `meta` stream
     */
    private $metaStream;

    /**
     * @var MultipartStream - The `multipart/form-data` stream
     */
    private $multipart;

    /**
     * @var StreamInterface - multipart stream wrapper
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
     *                         or
     *                         text/csv whose are the platform acceptable etc.
     * @param ?StreamInterface $fileStream  File content stream, optional
     */
    public function __construct(string $filepath, ?StreamInterface $fileStream = null)
    {
        $this->filepath = $filepath;
        $this->fileStream = $fileStream;
        $this->composeStream();
    }

    /**
     * Compose the GuzzleHttp\Psr7\FnStream
     */
    private function composeStream(): void
    {
        $basename = basename($this->filepath);
        $stream = $this->fileStream ?? new LazyOpenStream($this->filepath, 'rb');
        if ($stream instanceof StreamInterface && !($stream->isSeekable())) {
            $stream = new CachingStream($stream);
        }
        if (!($stream instanceof StreamInterface)) {
            throw new UnexpectedValueException(sprintf('Cannot open or caching the file: `%s`', $this->filepath));
        }

        $buffer = new BufferStream();
        $metaStream = FnStream::decorate($buffer, [
            'getSize' => static function () { return null; },
            // The `BufferStream` doen't have `uri` metadata(`null` returned),
            // but the `MultipartStream` did checked this prop with the `substr` method, which method described
            // the first paramter must be the string on the `strict_types` mode.
            // Decorate the `getMetadata` for this case.
            'getMetadata' => static function($key = null) use ($buffer) {
                if ('uri' === $key) { return 'php://temp'; }
                return $buffer->getMetadata($key);
            },
        ]);

        $this->fileStream = $this->fileStream ?? $stream;
        $this->metaStream = $metaStream;

        $this->setMeta();

        $multipart = new MultipartStream([
            [
                'name'     => 'meta',
                'contents' => $this->metaStream,
                'headers'  => [
                    'Content-Type' => 'application/json',
                ],
            ],
            [
                'name'     => 'file',
                'filename' => $basename,
                'contents' => $this->fileStream,
            ],
        ]);
        $this->multipart = $multipart;

        $this->stream = FnStream::decorate($multipart, [
            '__toString' => function () { return $this->getMeta(); },
            'getSize' => static function () { return null; },
        ]);
    }

    /**
     * Set the `meta` part of the `multipart/form-data` stream
     *
     * Note: The `meta` weren't be the `media file`'s `meta data` anymore.
     *
     *       Previous whose were designed as `{filename,sha256}`,
     *       but another API was described asof `{bank_type,filename,sha256}`.
     *
     *       Exposed the ability of setting the `meta` for the `new` data structure.
     *
     * @param ?string $json - The `meta` string
     * @since v1.3.2
     */
    public function setMeta(?string $json = null): int
    {
        $content = $json ?? (string)json_encode([
            'filename' => basename($this->filepath),
            'sha256' => $this->fileStream ? Utils::hash($this->fileStream, 'sha256') : '',
        ]);
        // clean the metaStream's buffer string
        $this->metaStream->getContents();

        return $this->metaStream->write($content);
    }

    /**
     * Get the `meta` string
     */
    public function getMeta(): string
    {
        $json = (string)$this->metaStream;
        $this->setMeta($json);

        return $json;
    }

    /**
     * Get the `FnStream` which is the `MultipartStream` decorator
     */
    public function getStream(): StreamInterface
    {
        return $this->stream;
    }

    /**
     * Get the `Content-Type` value from the `{$this->multipart}` instance
     */
    public function getContentType(): string
    {
        return 'multipart/form-data; boundary=' . $this->multipart->getBoundary();
    }
}
