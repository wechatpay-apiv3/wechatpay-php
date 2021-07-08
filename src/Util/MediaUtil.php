<?php declare(strict_types=1);

namespace WeChatPay\Util;

use function basename;
use function sprintf;

use UnexpectedValueException;

use GuzzleHttp\Utils as GHU;
use GuzzleHttp\Psr7\Utils;
use GuzzleHttp\Psr7\LazyOpenStream;
use GuzzleHttp\Psr7\MultipartStream;
use GuzzleHttp\Psr7\FnStream;
use GuzzleHttp\Psr7\CachingStream;
use Psr\Http\Message\StreamInterface;

/**
 * Util for Media(image or video) uploading.
 */
class MediaUtil
{
    /**
     * @var string - local file path
     */
    private $filepath;

    /**
     * @var ?StreamInterface - file content stream to upload
     */
    private $fileStream;

    /**
     * @var string - upload meta json string
     */
    private $meta;

    /**
     * @var MultipartStream - upload contents stream
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

        $json = GHU::jsonEncode([
            'filename' => $basename,
            'sha256'   => Utils::hash($stream, 'sha256'),
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
             /** @var callable __toString -  for signature */
            '__toString' => static function () use ($json) { return $json; },
             /** @var callable getSize - let the `CURL` to use `CURLOPT_UPLOAD` context */
            'getSize' => static function () { return null; },
        ]);
    }

    /**
     * Get the `meta` of the multipart data string
     */
    public function getMeta(): string
    {
        return $this->meta;
    }

    /**
     * Get the `GuzzleHttp\Psr7\CachingStream`|`GuzzleHttp\Psr7\LazyOpenStream` context
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
