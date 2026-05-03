<?php
function get_settings(): array {
    $defaults = ['table_count' => 10, 'gst_enabled' => true, 'gst_number' => '', 'gst_rate' => 10];
    $file = __DIR__ . '/settings.json';
    if (!file_exists($file)) return $defaults;
    return array_merge($defaults, json_decode(file_get_contents($file), true) ?: []);
}

function time_ago(string $datetime): string {
    $diff = time() - strtotime($datetime);
    if ($diff < 60)    return $diff . 's ago';
    if ($diff < 3600)  return floor($diff / 60) . ' min ago';
    if ($diff < 86400) return floor($diff / 3600) . ' hr ago';
    return date('d M', strtotime($datetime));
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
