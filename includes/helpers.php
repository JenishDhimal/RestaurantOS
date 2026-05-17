<?php
function get_settings(): array {
    $defaults = ['table_count' => 10, 'gst_enabled' => true, 'gst_number' => '', 'gst_rate' => 10];
    $file = __DIR__ . '/settings.json';
    if (!file_exists($file)) return $defaults;
    return array_merge($defaults, json_decode(file_get_contents($file), true) ?: []);
}

function time_ago(string $datetime): string {
    $ts = strtotime($datetime);
    if (date('Y-m-d', $ts) === date('Y-m-d')) {
        return date('g:i A', $ts);
    }
    return date('d M, g:i A', $ts);
}

function status_class(string $status): string {
    return match($status) {
        'Received'  => 'status-received',
        'Preparing' => 'status-preparing',
        'Ready'     => 'status-ready',
        'Paid'      => 'status-paid',
        default     => '',
    };
}
