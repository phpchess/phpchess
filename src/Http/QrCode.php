<?php

declare(strict_types=1);

namespace PhpChess\Http;

use InvalidArgumentException;

/**
 * Draws a QR code as SVG, for invite links (G-21): a teacher shows it on
 * the board and pupils scan it with their phones.
 *
 * Our own code instead of a library (A16, A21), kept to what an invite link
 * needs: byte mode, error correction level M (15 % of the code may be
 * damaged), versions 1 to 6 (up to 106 characters). It follows the QR
 * standard (ISO/IEC 18004) step by step:
 *
 *   1. Data:      mode "byte", the length, the bytes, then padding.
 *   2. Checking:  Reed-Solomon error correction codewords per block.
 *   3. Drawing:   finder, timing and alignment patterns, then the data in a
 *                 zigzag from the bottom right.
 *   4. Masking:   each of the 8 masks is tried; the one with the fewest
 *                 hard-to-scan patterns is kept, and the format bits say which.
 *
 * The SVG is drawn with plain rectangles, so it needs no script or style.
 */
final class QrCode
{
    /**
     * Per version (level M): data codewords, error correction codewords per
     * block, number of blocks. From the standard's table 9.
     */
    private const array VERSIONS = [
        1 => [16, 10, 1],
        2 => [28, 16, 1],
        3 => [44, 26, 1],
        4 => [64, 18, 2],
        5 => [86, 24, 2],
        6 => [108, 16, 4],
    ];

    /** @var array<int, array<int, bool>> row => column => dark */
    private array $modules = [];

    /** @var array<int, array<int, bool>> modules that belong to patterns, not data */
    private array $isPattern = [];

    private int $size;

    private function __construct(private readonly int $version)
    {
        $this->size = 17 + 4 * $version;
        for ($row = 0; $row < $this->size; $row++) {
            $this->modules[$row] = array_fill(0, $this->size, false);
            $this->isPattern[$row] = array_fill(0, $this->size, false);
        }
    }

    // --- The SVG ---

    /** The QR code for a text (a link) as an SVG image, with a light border. */
    public static function svg(string $text, int $pixelsPerModule = 6): string
    {
        $matrix = self::matrix($text);
        $border = 4;
        $count = count($matrix);
        $side = ($count + 2 * $border) * $pixelsPerModule;

        $rectangles = [];
        foreach ($matrix as $row => $columns) {
            foreach ($columns as $column => $isDark) {
                if ($isDark) {
                    $rectangles[] = '<rect x="' . ($column + $border) . '" y="' . ($row + $border) . '" width="1" height="1"/>';
                }
            }
        }

        return '<svg xmlns="http://www.w3.org/2000/svg" class="qr-code" role="img" width="' . $side . '" height="' . $side . '"'
            . ' viewBox="0 0 ' . ($count + 2 * $border) . ' ' . ($count + 2 * $border) . '" shape-rendering="crispEdges">'
            . '<rect width="100%" height="100%" fill="#fff"/><g fill="#000">' . implode('', $rectangles) . '</g></svg>';
    }

    /**
     * The QR code as rows of dark (true) and light (false) modules.
     *
     * @return array<int, array<int, bool>>
     */
    public static function matrix(string $text): array
    {
        $version = self::smallestVersionFor(strlen($text));
        $code = new self($version);
        $code->drawPatterns();
        $codewords = self::withErrorCorrection(self::dataCodewords($text, $version), $version);
        $code->drawData($codewords);
        $code->applyBestMask();

        return $code->modules;
    }

    private static function smallestVersionFor(int $byteCount): int
    {
        foreach (self::VERSIONS as $version => [$dataCodewords]) {
            // 4 bits mode + 8 bits length come before the bytes.
            if ($byteCount <= intdiv($dataCodewords * 8 - 12, 8)) {
                return $version;
            }
        }
        throw new InvalidArgumentException('The text is too long for a QR code of version 6.');
    }

    // --- 1. Data codewords ---

    /**
     * @return list<int>
     */
    private static function dataCodewords(string $text, int $version): array
    {
        $capacityBits = self::VERSIONS[$version][0] * 8;

        $bits = '0100';                                   // mode: byte
        $bits .= sprintf('%08b', strlen($text));          // length (8 bits up to version 9)
        foreach (str_split($text) as $character) {
            $bits .= sprintf('%08b', ord($character));
        }
        $bits .= str_repeat('0', min(4, $capacityBits - strlen($bits)));   // terminator
        $bits .= str_repeat('0', (8 - strlen($bits) % 8) % 8);              // to a whole byte

        $codewords = [];
        foreach (str_split($bits, 8) as $byte) {
            $codewords[] = (int) bindec($byte);
        }
        // Padding bytes 236 and 17 take turns until the code is full.
        for ($pad = 0; count($codewords) < self::VERSIONS[$version][0]; $pad++) {
            $codewords[] = $pad % 2 === 0 ? 0xEC : 0x11;
        }

        return $codewords;
    }

    // --- 2. Error correction (Reed-Solomon) ---

    /**
     * Splits the data into blocks, adds each block's error correction, and
     * interleaves them: first byte of every block, then the second, and so on.
     *
     * @param list<int> $data
     * @return list<int>
     */
    private static function withErrorCorrection(array $data, int $version): array
    {
        [$dataCount, $eccPerBlock, $blockCount] = self::VERSIONS[$version];
        $blockLength = intdiv($dataCount, $blockCount);
        $divisor = self::generatorPolynomial($eccPerBlock);

        $dataBlocks = array_chunk($data, $blockLength);
        $eccBlocks = array_map(fn (array $block): array => self::remainder($block, $divisor), $dataBlocks);

        $result = [];
        foreach ([[$dataBlocks, $blockLength], [$eccBlocks, $eccPerBlock]] as [$blocks, $length]) {
            for ($index = 0; $index < $length; $index++) {
                foreach ($blocks as $block) {
                    $result[] = $block[$index];
                }
            }
        }

        return $result;
    }

    /**
     * The error correction codewords for one block of data. Public so the
     * test can check it against the standard's worked example.
     *
     * @param list<int> $data
     * @return list<int>
     */
    public static function errorCorrectionCodewords(array $data, int $eccCount): array
    {
        return self::remainder($data, self::generatorPolynomial($eccCount));
    }

    /**
     * The generator polynomial (x - a^0)(x - a^1)...(x - a^(degree-1)) in
     * GF(256), as its coefficients without the leading 1.
     *
     * @return list<int>
     */
    private static function generatorPolynomial(int $degree): array
    {
        $coefficients = array_fill(0, $degree, 0);
        $coefficients[$degree - 1] = 1;
        $root = 1;
        for ($step = 0; $step < $degree; $step++) {
            for ($index = 0; $index < $degree; $index++) {
                $coefficients[$index] = self::multiply($coefficients[$index], $root);
                if ($index + 1 < $degree) {
                    $coefficients[$index] ^= $coefficients[$index + 1];
                }
            }
            $root = self::multiply($root, 0x02);
        }

        return array_values($coefficients);
    }

    /**
     * The remainder of the data divided by the generator: the error correction codewords.
     *
     * @param list<int> $data
     * @param list<int> $divisor
     * @return list<int>
     */
    private static function remainder(array $data, array $divisor): array
    {
        $result = array_fill(0, count($divisor), 0);
        foreach ($data as $byte) {
            $factor = $byte ^ array_shift($result);
            $result[] = 0;
            foreach ($divisor as $index => $coefficient) {
                $result[$index] ^= self::multiply($coefficient, $factor);
            }
        }

        return array_values($result);
    }

    /** Multiplication in GF(256) with the QR polynomial x^8 + x^4 + x^3 + x^2 + 1 (0x11D). */
    private static function multiply(int $left, int $right): int
    {
        $product = 0;
        for ($bit = 7; $bit >= 0; $bit--) {
            $product = ($product << 1) ^ (($product >> 7) * 0x11D);
            $product ^= (($right >> $bit) & 1) * $left;
        }

        return $product & 0xFF;
    }

    // --- 3. Drawing ---

    private function drawPatterns(): void
    {
        // Timing patterns: alternating modules along row 6 and column 6.
        for ($index = 0; $index < $this->size; $index++) {
            $this->setPattern(6, $index, $index % 2 === 0);
            $this->setPattern($index, 6, $index % 2 === 0);
        }

        // Finder patterns in three corners (with their light separators).
        foreach ([[3, 3], [3, $this->size - 4], [$this->size - 4, 3]] as [$centreRow, $centreColumn]) {
            for ($rowOffset = -4; $rowOffset <= 4; $rowOffset++) {
                for ($columnOffset = -4; $columnOffset <= 4; $columnOffset++) {
                    $row = $centreRow + $rowOffset;
                    $column = $centreColumn + $columnOffset;
                    if ($row < 0 || $row >= $this->size || $column < 0 || $column >= $this->size) {
                        continue;
                    }
                    $distance = max(abs($rowOffset), abs($columnOffset));
                    $this->setPattern($row, $column, $distance !== 2 && $distance !== 4);
                }
            }
        }

        // One alignment pattern near the bottom right (versions 2 to 6).
        if ($this->version >= 2) {
            $centre = $this->size - 7;
            for ($rowOffset = -2; $rowOffset <= 2; $rowOffset++) {
                for ($columnOffset = -2; $columnOffset <= 2; $columnOffset++) {
                    $this->setPattern($centre + $rowOffset, $centre + $columnOffset, max(abs($rowOffset), abs($columnOffset)) !== 1);
                }
            }
        }

        // Reserve the format areas (filled after masking) and the dark module.
        $this->drawFormatBits(0);
    }

    /**
     * Places the codewords in the zigzag: two columns at a time from the
     * right, going up then down, skipping the vertical timing column.
     *
     * @param list<int> $codewords
     */
    private function drawData(array $codewords): void
    {
        $bitIndex = 0;
        $bitCount = count($codewords) * 8;
        for ($right = $this->size - 1; $right >= 1; $right -= 2) {
            if ($right === 6) {
                $right = 5;
            }
            $isUpward = (($right + 1) & 2) === 0;
            for ($step = 0; $step < $this->size; $step++) {
                $row = $isUpward ? $this->size - 1 - $step : $step;
                for ($offset = 0; $offset < 2; $offset++) {
                    $column = $right - $offset;
                    if ($this->isPattern[$row][$column] || $bitIndex >= $bitCount) {
                        continue;
                    }
                    $byte = $codewords[intdiv($bitIndex, 8)];
                    $this->modules[$row][$column] = (($byte >> (7 - $bitIndex % 8)) & 1) === 1;
                    $bitIndex++;
                }
            }
        }
    }

    // --- 4. Masking and format bits ---

    private function applyBestMask(): void
    {
        $bestMask = 0;
        $bestPenalty = PHP_INT_MAX;
        for ($mask = 0; $mask < 8; $mask++) {
            $this->flipWithMask($mask);
            $this->drawFormatBits($mask);
            $penalty = $this->penalty();
            if ($penalty < $bestPenalty) {
                $bestPenalty = $penalty;
                $bestMask = $mask;
            }
            $this->flipWithMask($mask);   // flipping twice undoes it
        }
        $this->flipWithMask($bestMask);
        $this->drawFormatBits($bestMask);
    }

    /** Flips every data module where the mask's condition holds. */
    private function flipWithMask(int $mask): void
    {
        for ($row = 0; $row < $this->size; $row++) {
            for ($column = 0; $column < $this->size; $column++) {
                if ($this->isPattern[$row][$column]) {
                    continue;
                }
                $flip = match ($mask) {
                    0 => ($row + $column) % 2 === 0,
                    1 => $row % 2 === 0,
                    2 => $column % 3 === 0,
                    3 => ($row + $column) % 3 === 0,
                    4 => (intdiv($row, 2) + intdiv($column, 3)) % 2 === 0,
                    5 => ($row * $column) % 2 + ($row * $column) % 3 === 0,
                    6 => (($row * $column) % 2 + ($row * $column) % 3) % 2 === 0,
                    default => (($row + $column) % 2 + ($row * $column) % 3) % 2 === 0,
                };
                if ($flip) {
                    $this->modules[$row][$column] = !$this->modules[$row][$column];
                }
            }
        }
    }

    /**
     * The 15 format bits (level M = 00, the mask number, a BCH check code,
     * XOR 101010000010010), drawn twice: around the top-left finder, and
     * split between the other two.
     */
    private function drawFormatBits(int $mask): void
    {
        $data = (0b00 << 3) | $mask;
        $remainder = $data;
        for ($step = 0; $step < 10; $step++) {
            $remainder = ($remainder << 1) ^ (($remainder >> 9) * 0x537);
        }
        $bits = (($data << 10) | $remainder) ^ 0x5412;
        $bit = fn (int $index): bool => (($bits >> $index) & 1) === 1;

        // First copy, around the top-left finder (x = column, y = row).
        for ($index = 0; $index <= 5; $index++) {
            $this->setPattern($index, 8, $bit($index));
        }
        $this->setPattern(7, 8, $bit(6));
        $this->setPattern(8, 8, $bit(7));
        $this->setPattern(8, 7, $bit(8));
        for ($index = 9; $index < 15; $index++) {
            $this->setPattern(8, 14 - $index, $bit($index));
        }

        // Second copy: bottom left and top right.
        for ($index = 0; $index < 8; $index++) {
            $this->setPattern(8, $this->size - 1 - $index, $bit($index));
        }
        for ($index = 8; $index < 15; $index++) {
            $this->setPattern($this->size - 15 + $index, 8, $bit($index));
        }
        $this->setPattern($this->size - 8, 8, true);   // the module that is always dark
    }

    /**
     * How hard the code is to scan (lower is better), from three of the
     * standard's rules: runs of five or more modules of one colour in a row
     * or column, 2x2 blocks of one colour, and too much dark or light.
     */
    private function penalty(): int
    {
        $penalty = 0;
        for ($line = 0; $line < $this->size; $line++) {
            foreach ([true, false] as $isRow) {
                $runLength = 1;
                for ($index = 1; $index < $this->size; $index++) {
                    $current = $isRow ? $this->modules[$line][$index] : $this->modules[$index][$line];
                    $previous = $isRow ? $this->modules[$line][$index - 1] : $this->modules[$index - 1][$line];
                    if ($current === $previous) {
                        $runLength++;
                        continue;
                    }
                    $penalty += $runLength >= 5 ? $runLength - 2 : 0;
                    $runLength = 1;
                }
                $penalty += $runLength >= 5 ? $runLength - 2 : 0;
            }
        }

        $darkCount = 0;
        for ($row = 0; $row < $this->size; $row++) {
            for ($column = 0; $column < $this->size; $column++) {
                $darkCount += $this->modules[$row][$column] ? 1 : 0;
                if ($row > 0 && $column > 0) {
                    $colour = $this->modules[$row][$column];
                    if ($colour === $this->modules[$row - 1][$column] && $colour === $this->modules[$row][$column - 1] && $colour === $this->modules[$row - 1][$column - 1]) {
                        $penalty += 3;
                    }
                }
            }
        }
        $darkPercent = intdiv($darkCount * 100, $this->size * $this->size);
        $penalty += intdiv(abs($darkPercent - 50), 5) * 10;

        return $penalty;
    }

    private function setPattern(int $row, int $column, bool $isDark): void
    {
        $this->modules[$row][$column] = $isDark;
        $this->isPattern[$row][$column] = true;
    }
}
