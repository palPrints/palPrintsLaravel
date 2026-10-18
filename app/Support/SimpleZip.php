<?php

namespace App\Support;

use RuntimeException;

/**
 * Writes a plain .zip (entries stored, not compressed: the pictures inside are PNG/JPG already) without the PHP zip
 * extension, which a server may not have. Names are written as UTF-8. Meant for small packages, not for 4 GB archives.
 */
class SimpleZip
{
    /** @var resource */
    private $handle;

    /** @var array<int, array{name: string, crc: int, size: int, offset: int}> */
    private array $entries = [];

    private int $offset = 0;

    private int $dosTime;

    private int $dosDate;

    public function __construct(string $path)
    {
        $handle = fopen($path, 'wb');

        if ($handle === false) {
            throw new RuntimeException('Cannot write the package file.');
        }

        $this->handle = $handle;
        $now = getdate();
        $this->dosTime = ($now['hours'] << 11) | ($now['minutes'] << 5) | intdiv($now['seconds'], 2);
        $this->dosDate = ((max(1980, $now['year']) - 1980) << 9) | ($now['mon'] << 5) | $now['mday'];
    }

    public function addFile(string $name, string $sourcePath): void
    {
        $source = fopen($sourcePath, 'rb');

        if ($source === false) {
            throw new RuntimeException('Cannot read a file for the package.');
        }

        $size = (int) filesize($sourcePath);
        $crc = hexdec(hash_file('crc32b', $sourcePath));

        $this->writeHeader($name, $crc, $size);
        stream_copy_to_stream($source, $this->handle);
        fclose($source);
        $this->offset += $size;
        $this->entries[] = ['name' => $name, 'crc' => $crc, 'size' => $size, 'offset' => $this->offset - $size - $this->headerLength($name)];
    }

    public function addString(string $name, string $contents): void
    {
        $size = strlen($contents);
        $crc = hexdec(hash('crc32b', $contents));

        $this->writeHeader($name, $crc, $size);
        fwrite($this->handle, $contents);
        $this->offset += $size;
        $this->entries[] = ['name' => $name, 'crc' => $crc, 'size' => $size, 'offset' => $this->offset - $size - $this->headerLength($name)];
    }

    public function close(): void
    {
        $directoryStart = $this->offset;
        $directorySize = 0;

        foreach ($this->entries as $entry) {
            $record = pack('VvvvvvvVVVvvvvvVV', 0x02014B50, 20, 20, 0x0800, 0, $this->dosTime, $this->dosDate, $entry['crc'], $entry['size'], $entry['size'], strlen($entry['name']), 0, 0, 0, 0, 0, $entry['offset']).$entry['name'];
            fwrite($this->handle, $record);
            $directorySize += strlen($record);
        }

        fwrite($this->handle, pack('VvvvvVVv', 0x06054B50, 0, 0, count($this->entries), count($this->entries), $directorySize, $directoryStart, 0));
        fclose($this->handle);
    }

    private function headerLength(string $name): int
    {
        return 30 + strlen($name);
    }

    private function writeHeader(string $name, int $crc, int $size): void
    {
        fwrite($this->handle, pack('VvvvvvVVVvv', 0x04034B50, 20, 0x0800, 0, $this->dosTime, $this->dosDate, $crc, $size, $size, strlen($name), 0).$name);
        $this->offset += $this->headerLength($name);
    }
}
