<?php

if (! function_exists('format_bytes')) {
    /** format_bytes(2516582) => "2.4 MB" */
    function format_bytes(int $bytes): string
    {
        if ($bytes <= 0) {
            return '0 KB';
        }

        $units = ['B', 'KB', 'MB', 'GB'];
        $i = min((int) floor(log($bytes, 1024)), count($units) - 1);
        $value = $bytes / (1024 ** $i);

        return ($i === 0 ? (string) $bytes : number_format($value, $value >= 10 ? 0 : 1, '.', '')).' '.$units[$i];
    }
}
