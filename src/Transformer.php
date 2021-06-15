<?php

namespace WeChatPay;

use const LIBXML_VERSION;
use const LIBXML_NONET;
use const LIBXML_COMPACT;
use const LIBXML_NOCDATA;
use const LIBXML_NOBLANKS;

use function array_walk;
use function is_array;
use function is_object;
use function preg_replace;
use function strpos;
use function preg_match;

use SimpleXMLElement;
use Traversable;
use XMLWriter;

/**
 * Transform the `XML` to `Array` or `Array` to `XML`.
 */
class Transformer
{
    /**
     * Convert the $xml string to array.
     *
     * Always issue the `additional Libxml parameters` asof `LIBXML_NONET`
     *                                                    | `LIBXML_COMPACT`
     *                                                    | `LIBXML_NOCDATA`
     *                                                    | `LIBXML_NOBLANKS`
     *
     * @param string [$xml = '<xml/>'] - The xml string
     *
     * @return array - The array
     */
    public static function toArray(string $xml = '<xml/>'): array
    {
        LIBXML_VERSION < 20900 && $previous = libxml_disable_entity_loader(true);

        $el = simplexml_load_string(static::sanitize($xml), SimpleXMLElement::class, LIBXML_NONET | LIBXML_COMPACT | LIBXML_NOCDATA | LIBXML_NOBLANKS);

        LIBXML_VERSION < 20900 && libxml_disable_entity_loader($previous);

        return static::cast($el);
    }

    /**
     * Recursive cast the $thing as array data structure.
     *
     * @param array|object|SimpleXMLElement $thing - The thing
     *
     * @return array - The array
     */
    protected static function cast($thing): array
    {
        $data = (array) $thing;
        array_walk($data, ['static', 'value']);

        return $data;
    }

    /**
     * Cast the value $thing, specially doing the `array`, `object`, `SimpleXMLElement` to `array`
     *
     * @param string|array|object|SimpleXMLElement &$thing - The value thing
     *
     * @return void
     */
    protected static function value(&$thing): void
    {
        is_array($thing) && $thing = static::cast($thing);
        if (is_object($thing) && $thing instanceof SimpleXMLElement) {
            $thing = $thing->count() ? static::cast($thing) : (string) $thing;
        }
    }

    /**
     * Trim invalid characters from the $xml string
     *
     * @see https://github.com/w7corp/easywechat/pull/1419
     * @license https://github.com/w7corp/easywechat/blob/4.x/LICENSE
     *
     * @param string $xml
     *
     * @return string
     */
    public static function sanitize(string $xml): string
    {
        return preg_replace('#[^\x{9}\x{A}\x{D}\x{20}-\x{D7FF}\x{E000}-\x{FFFD}\x{10000}-\x{10FFFF}]+#u', '', $xml);
    }

    /**
     * Transform the given $data array as of an XML string.
     *
     * @param array $data - The data array
     * @param boolean [$headless = true] - The headless flag, default True means without the `<?xml version="1.0" encoding="UTF-8" ?>` doctype
     * @param boolean [indent = false] - Toggle indentation on/off, default is off
     * @param string [$root = 'xml'] - The root node label
     * @param string [$item = 'item'] - The nest array identify text
     *
     * @return string - The xml string
     */
    public static function toXml(array $data, bool $headless = true, bool $indent = false, string $root = 'xml', string $item = 'item'): string
    {
        $writer = new XMLWriter();
        $writer->openMemory();
        $writer->setIndent($indent);
        $headless || $writer->startDocument('1.0', 'utf-8');
        $writer->startElement($root);
        static::walk($writer, $data, $item);
        $writer->endElement();
        $headless || $writer->endDocument();
        $xml = $writer->outputMemory();
        $writer = null;

        return $xml;
    }

    /**
     * Walk the given data array by the `XMLWriter` instance.
     *
     * @param XMLWritter &$writer - The `XMLWritter` instance
     * @param array $data - The data array
     * @param string $item - The nest array identify tag text
     *
     * @return void
     */
    protected static function walk(XMLWriter &$writer, array $data, string $item): void
    {
        foreach ($data as $key => $value) {
            $tag = static::isElementNameValid($key) ? $key : $item;
            $writer->startElement($tag);
            if (is_array($value) || (is_object($value) && $value instanceof Traversable)) {
                static::walk($writer, (array) $value, $item);
            } else {
                static::content($writer, $value);
            }
            $writer->endElement();
        }
    }

    /**
     * Write content text.
     *
     * The content text includes the characters `<`, `>`, `&` and `"` are written as CDATA references.
     * All others including `'` are written literally.
     *
     * @param XMLWritter &$writer - The `XMLWritter` instance
     * @param string|null $thing - The content text
     *
     * @return void
     */
    protected static function content(XMLWriter &$writer, ?string $thing = null): void
    {
        static::needsCdataWrapping($thing) && $writer->writeCdata($thing) || $writer->text($thing);
    }

    /**
     * Checks the name is a valid xml element name.
     *
     * @see Symfony\Component\Serializer\Encoder\XmlEncoder::isElementNameValid
     * @license https://github.com/symfony/serializer/blob/5.3/LICENSE
     *
     * @param string|null $name - The name
     *
     * @return boolean - True means valid
     */
    protected static function isElementNameValid(?string $name = null): bool
    {
        return $name && false === strpos($name, ' ') && preg_match('#^[\pL_][\pL0-9._:-]*$#ui', $name);
    }

    /**
     * Checks if a value contains any characters which would require CDATA wrapping.
     *
     * Notes here: the `XMLWriter` shall been wrapped the '"' string as '&quot;' symbol string,
     *             it's strictly following the `XMLWriter` specification here.
     *
     * @see Symfony\Component\Serializer\Encoder\XmlEncoder::needsCdataWrapping
     * @license https://github.com/symfony/serializer/blob/5.3/LICENSE
     *
     * @param string|null $value - The value
     *
     * @return boolean - True means need
     */
    protected static function needsCdataWrapping(?string $value = null): bool
    {
        return 0 < preg_match('#[>&"<]#', $value);
    }
}