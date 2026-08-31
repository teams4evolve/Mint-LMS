<?php
declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

if (!function_exists('get_option')) {
    function get_option($option, $default = false) {
        return $default;
    }
}
