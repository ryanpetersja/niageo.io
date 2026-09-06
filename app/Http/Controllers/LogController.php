<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class LogController extends Controller
{
    /**
     * Only read the last ~2MB of a log file so a huge log never exhausts memory.
     */
    private const MAX_BYTES = 2_000_000;

    public function index(Request $request)
    {
        $files = $this->logFiles();
        $selected = $this->resolveFile($request->query('file'), $files);

        $entries = [];
        $truncated = false;
        $size = 0;

        if ($selected) {
            $path = $this->logPath($selected);
            $size = filesize($path) ?: 0;
            $contents = $this->tail($path, self::MAX_BYTES, $truncated);
            $entries = $this->parse($contents);
        }

        $level = strtoupper((string) $request->query('level', ''));
        if ($level !== '') {
            $entries = array_values(array_filter($entries, fn ($e) => $e['level'] === $level));
        }

        // Newest first.
        $entries = array_reverse($entries);

        return view('logs.index', [
            'files' => $files,
            'selected' => $selected,
            'entries' => $entries,
            'level' => $level,
            'levels' => ['ERROR', 'CRITICAL', 'ALERT', 'EMERGENCY', 'WARNING', 'NOTICE', 'INFO', 'DEBUG'],
            'truncated' => $truncated,
            'size' => $size,
        ]);
    }

    public function download(Request $request): BinaryFileResponse
    {
        $selected = $this->resolveFile($request->query('file'), $this->logFiles());
        abort_unless($selected, 404);

        return response()->download($this->logPath($selected));
    }

    public function clear(Request $request)
    {
        $selected = $this->resolveFile($request->input('file'), $this->logFiles());
        abort_unless($selected, 404);

        File::put($this->logPath($selected), '');

        return redirect()
            ->route('logs.index', ['file' => $selected])
            ->with('success', "Cleared {$selected}.");
    }

    /** List *.log files in the logs directory, newest first. */
    private function logFiles(): array
    {
        $dir = storage_path('logs');
        if (! is_dir($dir)) {
            return [];
        }

        $files = collect(File::files($dir))
            ->filter(fn ($f) => $f->getExtension() === 'log')
            ->sortByDesc(fn ($f) => $f->getMTime())
            ->map(fn ($f) => $f->getFilename())
            ->values()
            ->all();

        return $files;
    }

    /** Validate the requested filename against the real directory listing (no traversal). */
    private function resolveFile(?string $requested, array $files): ?string
    {
        if ($requested !== null && in_array(basename($requested), $files, true)) {
            return basename($requested);
        }

        return $files[0] ?? null;
    }

    private function logPath(string $file): string
    {
        return storage_path('logs/' . $file);
    }

    /** Read at most $maxBytes from the end of a file. */
    private function tail(string $path, int $maxBytes, bool &$truncated): string
    {
        $size = filesize($path) ?: 0;
        if ($size <= $maxBytes) {
            return (string) file_get_contents($path);
        }

        $truncated = true;
        $fh = fopen($path, 'rb');
        fseek($fh, -$maxBytes, SEEK_END);
        $data = stream_get_contents($fh);
        fclose($fh);

        // Drop the first (probably partial) entry so parsing starts on a clean boundary.
        $pos = strpos($data, "\n[");
        return $pos !== false ? substr($data, $pos + 1) : $data;
    }

    /**
     * Parse a Laravel log into entries: datetime, channel, level, and the full body
     * (message plus any stack trace) up to the next timestamped line.
     */
    private function parse(string $contents): array
    {
        $pattern = '/^\[(\d{4}-\d{2}-\d{2}[ T]\d{2}:\d{2}:\d{2})[^\]]*\]\s*([\w.-]+)\.(\w+):\s*(.*?)(?=^\[\d{4}-\d{2}-\d{2}|\z)/ms';

        preg_match_all($pattern, $contents, $matches, PREG_SET_ORDER);

        $entries = [];
        foreach ($matches as $m) {
            $body = rtrim($m[4]);
            $firstLine = trim(strtok($body, "\n"));

            $entries[] = [
                'datetime' => $m[1],
                'channel' => $m[2],
                'level' => strtoupper($m[3]),
                'summary' => $firstLine,
                'body' => $body,
                'hasMore' => str_contains($body, "\n"),
            ];
        }

        return $entries;
    }
}
