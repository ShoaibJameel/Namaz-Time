<?php
/**
 * Plugin Name: Namaz Timing Pro
 * Description: Professional Namaz timing plugin with modern frontend cards, shortcode support, and admin dashboard stats powered by AlAdhan API.
 * Version: 1.0.0
 * Author: Namaz Time
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * Text Domain: namaz-timing-pro
 */

if (!defined('ABSPATH')) {
    exit;
}

class Namaz_Timing_Pro {
    private const OPTION_STATS = 'ntp_stats';
    private const OPTION_PRESETS = 'ntp_presets';

    public function __construct() {
        add_action('init', [$this, 'register_shortcode']);
        add_action('wp_enqueue_scripts', [$this, 'register_assets']);
        add_action('admin_enqueue_scripts', [$this, 'admin_assets']);

        add_action('wp_ajax_ntp_get_prayer_times', [$this, 'ajax_get_prayer_times']);
        add_action('wp_ajax_nopriv_ntp_get_prayer_times', [$this, 'ajax_get_prayer_times']);

        add_action('wp_ajax_ntp_save_preset', [$this, 'ajax_save_preset']);
        add_action('wp_ajax_ntp_delete_preset', [$this, 'ajax_delete_preset']);

        add_action('admin_menu', [$this, 'register_admin_menu']);
    }

    public function register_assets(): void {
        wp_register_style(
            'ntp-public-css',
            plugin_dir_url(__FILE__) . 'assets/css/namaz-timing.css',
            [],
            '1.0.0'
        );

        wp_register_script(
            'ntp-public-js',
            plugin_dir_url(__FILE__) . 'assets/js/namaz-timing.js',
            [],
            '1.0.0',
            true
        );
    }

    public function admin_assets(string $hook): void {
        if ($hook !== 'toplevel_page_namaz-timing-pro') {
            return;
        }

        wp_enqueue_style(
            'ntp-admin-css',
            plugin_dir_url(__FILE__) . 'assets/css/admin.css',
            [],
            '1.0.0'
        );

        wp_enqueue_script(
            'ntp-admin-js',
            plugin_dir_url(__FILE__) . 'assets/js/admin.js',
            ['jquery'],
            '1.0.0',
            true
        );

        wp_localize_script('ntp-admin-js', 'NTPAdmin', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('ntp_admin_nonce'),
            'messages' => [
                'saved' => __('Preset saved successfully.', 'namaz-timing-pro'),
                'deleted' => __('Preset deleted.', 'namaz-timing-pro'),
                'error' => __('Something went wrong. Please try again.', 'namaz-timing-pro'),
            ],
        ]);
    }

    public function register_shortcode(): void {
        add_shortcode('namaz_timing', [$this, 'render_shortcode']);
    }

    public function render_shortcode(array $atts = []): string {
        $atts = shortcode_atts([
            'city' => '',
            'country' => '',
            'method' => '2',
            'title' => __('Namaz Timing', 'namaz-timing-pro'),
        ], $atts, 'namaz_timing');

        wp_enqueue_style('ntp-public-css');
        wp_enqueue_script('ntp-public-js');

        wp_localize_script('ntp-public-js', 'NTPData', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('ntp_frontend_nonce'),
            'defaultCity' => sanitize_text_field($atts['city']),
            'defaultCountry' => sanitize_text_field($atts['country']),
            'defaultMethod' => absint($atts['method']) ?: 2,
            'title' => sanitize_text_field($atts['title']),
            'i18n' => [
                'button' => __('Get Prayer Time', 'namaz-timing-pro'),
                'loading' => __('Loading prayer times...', 'namaz-timing-pro'),
                'upcoming' => __('Upcoming Prayer', 'namaz-timing-pro'),
                'location' => __('Location', 'namaz-timing-pro'),
                'countdown' => __('Time Remaining', 'namaz-timing-pro'),
                'today' => __('Today\'s Prayer Timings', 'namaz-timing-pro'),
                'error' => __('Could not fetch prayer timings. Please check details.', 'namaz-timing-pro'),
            ],
        ]);

        ob_start();
        include plugin_dir_path(__FILE__) . 'templates/shortcode.php';
        return (string) ob_get_clean();
    }

    public function ajax_get_prayer_times(): void {
        check_ajax_referer('ntp_frontend_nonce', 'nonce');

        $city = isset($_POST['city']) ? sanitize_text_field(wp_unslash($_POST['city'])) : '';
        $country = isset($_POST['country']) ? sanitize_text_field(wp_unslash($_POST['country'])) : '';
        $method = isset($_POST['method']) ? absint($_POST['method']) : 2;

        if (!$city || !$country) {
            wp_send_json_error(['message' => __('City and country are required.', 'namaz-timing-pro')], 400);
        }

        $url = add_query_arg([
            'city' => rawurlencode($city),
            'country' => rawurlencode($country),
            'method' => $method,
        ], 'https://api.aladhan.com/v1/timingsByCity');

        $response = wp_remote_get($url, ['timeout' => 15]);

        if (is_wp_error($response)) {
            wp_send_json_error(['message' => $response->get_error_message()], 500);
        }

        $code = wp_remote_retrieve_response_code($response);
        $body = json_decode((string) wp_remote_retrieve_body($response), true);

        if ($code !== 200 || empty($body['data']['timings'])) {
            wp_send_json_error(['message' => __('Unexpected response from AlAdhan API.', 'namaz-timing-pro')], 500);
        }

        $timings = $this->normalize_timings($body['data']['timings']);
        $meta = [
            'timezone' => $body['data']['meta']['timezone'] ?? 'UTC',
            'date' => $body['data']['date']['readable'] ?? '',
            'location' => trim($city . ', ' . $country),
        ];

        $nextPrayer = $this->get_upcoming_prayer($timings, $meta['timezone']);

        $this->increment_stats($city, $country);

        wp_send_json_success([
            'timings' => $timings,
            'meta' => $meta,
            'nextPrayer' => $nextPrayer,
        ]);
    }

    public function register_admin_menu(): void {
        add_menu_page(
            __('Namaz Timing Pro', 'namaz-timing-pro'),
            __('Namaz Timing', 'namaz-timing-pro'),
            'manage_options',
            'namaz-timing-pro',
            [$this, 'render_admin_page'],
            'dashicons-clock',
            58
        );
    }

    public function render_admin_page(): void {
        if (!current_user_can('manage_options')) {
            return;
        }

        $stats = get_option(self::OPTION_STATS, [
            'total_requests' => 0,
            'top_locations' => [],
        ]);

        $presets = get_option(self::OPTION_PRESETS, []);

        include plugin_dir_path(__FILE__) . 'templates/admin-page.php';
    }

    public function ajax_save_preset(): void {
        check_ajax_referer('ntp_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Unauthorized request.', 'namaz-timing-pro')], 403);
        }

        $city = isset($_POST['city']) ? sanitize_text_field(wp_unslash($_POST['city'])) : '';
        $country = isset($_POST['country']) ? sanitize_text_field(wp_unslash($_POST['country'])) : '';
        $method = isset($_POST['method']) ? absint($_POST['method']) : 2;
        $label = isset($_POST['label']) ? sanitize_text_field(wp_unslash($_POST['label'])) : '';

        if (!$city || !$country || !$label) {
            wp_send_json_error(['message' => __('Label, city, and country are required.', 'namaz-timing-pro')], 400);
        }

        $presets = get_option(self::OPTION_PRESETS, []);
        $id = sanitize_key($label . '-' . wp_generate_password(4, false, false));

        $presets[$id] = [
            'label' => $label,
            'city' => $city,
            'country' => $country,
            'method' => $method,
            'shortcode' => sprintf('[namaz_timing city="%s" country="%s" method="%d" title="%s"]', esc_attr($city), esc_attr($country), $method, esc_attr($label)),
        ];

        update_option(self::OPTION_PRESETS, $presets, false);

        wp_send_json_success(['id' => $id, 'preset' => $presets[$id]]);
    }

    public function ajax_delete_preset(): void {
        check_ajax_referer('ntp_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Unauthorized request.', 'namaz-timing-pro')], 403);
        }

        $id = isset($_POST['id']) ? sanitize_key(wp_unslash($_POST['id'])) : '';

        $presets = get_option(self::OPTION_PRESETS, []);

        if (!isset($presets[$id])) {
            wp_send_json_error(['message' => __('Preset not found.', 'namaz-timing-pro')], 404);
        }

        unset($presets[$id]);
        update_option(self::OPTION_PRESETS, $presets, false);

        wp_send_json_success();
    }

    private function normalize_timings(array $timings): array {
        $keys = ['Fajr', 'Dhuhr', 'Asr', 'Maghrib', 'Isha'];
        $normalized = [];

        foreach ($keys as $key) {
            if (!isset($timings[$key])) {
                continue;
            }
            $normalized[$key] = preg_replace('/\s*\(.+\)$/', '', (string) $timings[$key]);
        }

        return $normalized;
    }

    private function get_upcoming_prayer(array $timings, string $timezone): array {
        $now = new DateTime('now', new DateTimeZone($timezone));

        foreach ($timings as $name => $time) {
            $prayerTime = DateTime::createFromFormat('H:i', $time, new DateTimeZone($timezone));
            if (!$prayerTime) {
                continue;
            }
            $prayerTime->setDate((int) $now->format('Y'), (int) $now->format('m'), (int) $now->format('d'));

            if ($prayerTime > $now) {
                return [
                    'name' => $name,
                    'time' => $time,
                    'timestamp' => $prayerTime->getTimestamp(),
                ];
            }
        }

        $firstName = array_key_first($timings);
        if ($firstName) {
            $first = DateTime::createFromFormat('H:i', $timings[$firstName], new DateTimeZone($timezone));
            if ($first) {
                $first->setDate((int) $now->format('Y'), (int) $now->format('m'), (int) $now->format('d'));
                $first->modify('+1 day');
                return [
                    'name' => $firstName,
                    'time' => $timings[$firstName],
                    'timestamp' => $first->getTimestamp(),
                ];
            }
        }

        return [
            'name' => '',
            'time' => '',
            'timestamp' => $now->getTimestamp(),
        ];
    }

    private function increment_stats(string $city, string $country): void {
        $stats = get_option(self::OPTION_STATS, [
            'total_requests' => 0,
            'top_locations' => [],
        ]);

        $locationKey = strtolower(trim($city . ', ' . $country));

        $stats['total_requests'] = (int) ($stats['total_requests'] ?? 0) + 1;

        if (!isset($stats['top_locations'][$locationKey])) {
            $stats['top_locations'][$locationKey] = 0;
        }

        $stats['top_locations'][$locationKey]++;
        arsort($stats['top_locations']);
        $stats['top_locations'] = array_slice($stats['top_locations'], 0, 10, true);

        update_option(self::OPTION_STATS, $stats, false);
    }
}

new Namaz_Timing_Pro();
