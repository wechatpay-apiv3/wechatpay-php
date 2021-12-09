<?php declare(strict_types=1);

namespace WeChatPay\Crypto;

use function array_values;
use function bin2hex;
use function fclose;
use function feof;
use function fopen;
use function fread;
use function hex2bin;
use function pack;
use function sprintf;
use function strlen;
use function substr;
use function unpack;

use const PHP_INT_MAX;

use UnexpectedValueException;

const SM3_CBLOCK = 64;
const SM3_LBLOCK = SM3_CBLOCK >> 2;
const SM3_BBLOCK = SM3_CBLOCK << 3;
const SM3_448MOD512 = SM3_BBLOCK - SM3_CBLOCK;

/** @var int 本类库能够处理的块(`BLOCK`)的最大值, 32位系统(`255M`), 64位系统(`9223372036854775807/8`) */
const SM3_PBLOCK_MAX = (PHP_INT_MAX >> 3) - (2 << 6);

/** @var int 无符号32位整型最大值 */
const SM3_UINT32_MAX = 0xffffffff;

/** @var int[] 4.1 初始值 */
const SM3_INIT_VECTOR = [0x7380166f, 0x4914b2b9, 0x172442d7, 0xda8a0600, 0xa96f30bc, 0x163138aa, 0xe38dee4d, 0xb0fb0e4e];

/** @var int[] 4.2 常量(64轮循环左移后的定值) */
const SM3_CONSTRAINTS = [/* based on the `0x79cc4519` and `0x7a879d8a` */
    0x79cc4519, 0xf3988a32, 0xe7311465, 0xce6228cb, 0x9cc45197, 0x3988a32f, 0x7311465e, 0xe6228cbc,
    0xcc451979, 0x988a32f3, 0x311465e7, 0x6228cbce, 0xc451979c, 0x88a32f39, 0x11465e73, 0x228cbce6,
    0x9d8a7a87, 0x3b14f50f, 0x7629ea1e, 0xec53d43c, 0xd8a7a879, 0xb14f50f3, 0x629ea1e7, 0xc53d43ce,
    0x8a7a879d, 0x14f50f3b, 0x29ea1e76, 0x53d43cec, 0xa7a879d8, 0x4f50f3b1, 0x9ea1e762, 0x3d43cec5,
    0x7a879d8a, 0xf50f3b14, 0xea1e7629, 0xd43cec53, 0xa879d8a7, 0x50f3b14f, 0xa1e7629e, 0x43cec53d,
    0x879d8a7a, 0x0f3b14f5, 0x1e7629ea, 0x3cec53d4, 0x79d8a7a8, 0xf3b14f50, 0xe7629ea1, 0xcec53d43,
    0x9d8a7a87, 0x3b14f50f, 0x7629ea1e, 0xec53d43c, 0xd8a7a879, 0xb14f50f3, 0x629ea1e7, 0xc53d43ce,
    0x8a7a879d, 0x14f50f3b, 0x29ea1e76, 0x53d43cec, 0xa7a879d8, 0x4f50f3b1, 0x9ea1e762, 0x3d43cec5,
];

/**
 * SM3密码杂凑算法/SM3 Cryptographic Hash Algorithm(`GM/T 0004-2012`)
 * @link http://www.sca.gov.cn/sca/xwdt/2010-12/17/1002389/files/302a3ada057c4a73830536d03e683110.pdf
 */
class Sm3
{
    /**
     * @param string $hex - 16进制字符串/The hexadecimally encoded binary string
     */
    private static function bin(string $hex): string
    {
        $str = hex2bin($hex);
        if (false === $str) {
            throw new UnexpectedValueException('Invalid the input \$hex string.');
        }

        return $str;
    }

    /**
     * @param int $size - 比特长度值/Value of the bit length
     */
    private static function mod(int $size): int
    {
        $mod = $size % SM3_BBLOCK;

        return SM3_448MOD512 - $mod - 1 + ($mod < SM3_448MOD512 ? 0 : SM3_BBLOCK);
    }

    /**
     * @param string $word - 字/The `32bit` string
     * @return int[] - 无符号32位比特值(大端)/The unsigned `32bit` long in big endian byte order
     */
    private static function uInt32BE(string $word): array
    {
        $bits = unpack('N*', $word);
        if (false === $bits) {
            throw new UnexpectedValueException('Cannot `unpack` the input \$thing string.');
        }

        return array_values($bits);
    }

    /**
     * 循环左移
     * @param int $a - 32位比特值/The `32Bit` value
     * @param int $k - 左移比特位/The Bit size
     */
    private static function rotate(int $a, int $k): int
    {
        return (($a << $k) & SM3_UINT32_MAX) | ($a >> (32 - $k));
    }

    /**
     * 4.3 布尔函数`FF`，随`j`的变化取不同的表达式
     *
     * @param int $j
     * @param int $x
     * @param int $y
     * @param int $z
     */
    private static function FF(int $j, int $x, int $y, int $z): int
    {
        return $j < SM3_LBLOCK ? $x ^ $y ^ $z : ($x & $y) | (($x | $y) & $z);
    }

    /**
     * 4.3 布尔函数`GG`，随`j`的变化取不同的表达式
     *
     * @param int $j
     * @param int $x
     * @param int $y
     * @param int $z
     */
    private static function GG(int $j, int $x, int $y, int $z): int
    {
        return $j < SM3_LBLOCK ? $x ^ $y ^ $z : $z ^ $x & ($y ^ $z);
    }

    /**
     * 4.4 压缩函数中的置换函数`P0`
     *
     * @param int $x
     */
    private static function P0(int $x): int
    {
        return $x ^ self::rotate($x, 9) ^ self::rotate($x, 17);
    }

    /**
     * 4.4 消息扩展中的置换函数`P1`
     *
     * @param int $x
     */
    private static function P1(int $x): int
    {
        return $x ^ self::rotate($x, 15) ^ self::rotate($x, 23);
    }

    /**
     * 5.2 填充
     *
     * 假设消息`m`的长度为`l`比特。
     * 首先将比特`1`添加到消息的末尾，再添加`k`个`0`，
     * `k`是满足`l` + `1` + `k` ≡ `448mod512`的最小的非负整数。
     * 然后再添加一个64位比特串，该比特串是长度`l`的二进制表示。
     *
     * @param int $length - 字符长度值/Value of the bytes length
     */
    private static function pad(int $length): string
    {
        $bit = $length << 3;
        $len = self::mod($bit);

        return self::bin(sprintf(
            '%s%s%s', $len % 2 ? '' : '0', 1 << ($len % 4), str_repeat('0', $len >> 2)
        )) . pack('J', $bit);
    }

    /**
     * 5.3.1 迭代过程
     *
     * @param int[] $iv - 向量值/The vector string
     * @param string $word - 字/The `32bit` string
     */
    private static function calc(array $iv, string $word): string
    {
        $max = strlen($word);
        for ($i = 0; $i < $max; $i += SM3_CBLOCK) {
            $iv = self::CF($iv, substr($word, $i, SM3_CBLOCK));
        }

        return bin2hex(pack('N*', ...$iv));
    }

    /**
     * 5.3.2 消息扩展
     *
     * @param int[] $iv - 向量值/The vector string
     * @param string $thing - 待迭代压缩比特串/The `32bit` string
     * @return int[]
     */
    private static function CF(array $iv, string $thing): array
    {
        /** 5.3.2.b */
        $word = self::uInt32BE($thing);
        for ($i = 16; $i < 68; $i++) {
            $word[$i] = self::expand($word[$i - 16], $word[$i - 9], $word[$i - 3], $word[$i - 13], $word[$i - 6]);
        }

        /** 5.3.2.c */
        [$a, $b, $c, $d, $e, $f, $g, $h] = $iv;
        for ($j = 0; $j < 64; $j++) {
            [$a, $b, $c, $d, $e, $f, $g, $h] = self::compress(
                $a, $b, $c, $d, $e, $f, $g, $h,
                SM3_CONSTRAINTS[$j],
                $word[$j],
                $word[$j] ^ $word[$j + 4],
                self::FF($j, $a, $b, $c),
                self::GG($j, $e, $f, $g)
            );
        }

        return [$a ^ $iv[0], $b ^ $iv[1], $c ^ $iv[2], $d ^ $iv[3], $e ^ $iv[4], $f ^ $iv[5], $g ^ $iv[6], $h ^ $iv[7]];
    }

    /**
     * 5.3.2.a
     *
     * @param int $w0
     * @param int $w7
     * @param int $w13
     * @param int $w3
     * @param int $w10
     */
    private static function expand(int $w0, int $w7, int $w13, int $w3, int $w10): int
    {
        return self::P1($w0 ^ $w7 ^ self::rotate($w13, 15)) ^ self::rotate($w3, 7) ^ $w10;
    }

    /**
     * 5.3.3 压缩函数
     *
     * 令`A`,`B`,`C`,`D`,`E`,`F`,`G`,`H`为字寄存器,`SS1`,`SS2`,`TT1`,`TT2`为中间变量,
     * 压缩函数`Vi+1 = CF(V(i), B(i))`, `0 ≤ i ≤ n−1`。
     * 循环计算结果描述为`V(i+1) ← ABCDEFGH ⊕ V(i)`其中，字的存储为大端(`big-endian`)格式。
     *
     * @param int $a
     * @param int $b
     * @param int $c
     * @param int $d
     * @param int $e
     * @param int $f
     * @param int $g
     * @param int $h
     * @param int $tj
     * @param int $wi
     * @param int $wj
     * @param int $ff
     * @param int $gg
     * @return int[]
     */
    private static function compress(int $a, int $b, int $c, int $d, int $e, int $f, int $g, int $h, int $tj, int $wi, int $wj, int $ff, int $gg): array
    {
        $a12 = self::rotate($a, 12);
        $ss1 = self::rotate(($a12 + $e + $tj) & SM3_UINT32_MAX, 7);
        $ss2 = $ss1 ^ $a12;
        $tt1 = ($ff + $d + $ss2 + $wj) & SM3_UINT32_MAX;
        $tt2 = ($gg + $h + $ss1 + $wi) & SM3_UINT32_MAX;
        $d   = $c;
        $c   = self::rotate($b, 9);
        $b   = $a;
        $a   = $tt1;
        $h   = $g;
        $g   = self::rotate($f, 19);
        $f   = $e;
        $e   = self::P0($tt2);

        return [$a, $b, $c, $d, $e, $f, $g, $h];
    }

    /**
     * 5.4 杂凑值(字符)
     *
     * **警告：** 如果给定的字符串超大，可能会撑爆内存，推荐使用 `::file($path)` 流式处理。
     *
     * @param string $thing - 字符消息/The bytes string
     */
    public static function digest(string $thing): string
    {
        $len = strlen($thing);
        // While the \$len was already overhead of the signed `PHP_INT_MAX`, here shall be a problem.
        if ($len > SM3_PBLOCK_MAX) {
            throw new UnexpectedValueException('Cannot guarantee the \$thing is proceed correctly.');
        }

        return self::calc(SM3_INIT_VECTOR, $thing . self::pad($len));
    }

    /**
     * 5.4 杂凑值(文件)
     *
     * @param string $path - 文件路径/The file path string
     */
    public static function file(string $path): string
    {
        $fd = fopen($path, 'rb');
        if (false === $fd) {
            throw new UnexpectedValueException('Cannot `fopen` the file \$path string.');
        }

        $iv  = SM3_INIT_VECTOR;
        $len = 0;
        do {
            $str = fread($fd, SM3_CBLOCK);
            if (false === $str) {
                $message = 'Cannot `fread` the file \$path string.';
                break;
            }
            $len += $one = strlen($str);
            if ($len > SM3_PBLOCK_MAX) {
                $message = 'The precision is reachable, cannot process any more.';
                break;
            }
            if ($one < SM3_CBLOCK) {
                // While the \$one >= `SM3_448MOD512/8`, the \$str shall be padded in two `SM3_CBLOCK` length.
                break;
            }
            $iv = self::CF($iv, $str);
        } while (!feof($fd));
        fclose($fd);

        if (isset($message)) {
            throw new UnexpectedValueException($message);
        }

        return self::calc($iv, $str . self::pad($len));
    }
}
