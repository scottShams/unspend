<?php
// Server-side country detection to avoid CORS issues
header('Content-Type: application/json');

// Function to get country from IP using ipapi.co
function getCountryFromIP() {
    // Get user's IP address - prioritize forwarded headers for better accuracy
    $ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ??
          $_SERVER['HTTP_X_REAL_IP'] ??
          $_SERVER['HTTP_CF_CONNECTING_IP'] ??  // Cloudflare
          $_SERVER['REMOTE_ADDR'] ?? '';

    // Clean up IP (remove port if present and get first IP if multiple)
    $ip = trim(explode(',', $ip)[0]);
    $ip = trim(explode(':', $ip)[0]); // Remove port

    // Skip private/local IPs and invalid IPs
    if (empty($ip) || filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
        return null;
    }

    // For testing - if IP is localhost, return BD for Bangladesh
    if ($ip === '127.0.0.1' || $ip === '::1') {
        return 'BD';
    }

    // Use ipapi.co API (server-side request to avoid CORS)
    $api_url = "http://ipapi.co/{$ip}/json/";

    // Initialize cURL with better options
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $api_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_USERAGENT, 'unSpend/1.0');
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // For development

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    if ($http_code === 200 && $response && !$error) {
        $data = json_decode($response, true);
        if ($data && isset($data['country_code']) && !empty($data['country_code'])) {
            return strtoupper($data['country_code']);
        }
    }

    return null;
}

// Function to get country from timezone (fallback)
function getCountryFromTimezone() {
    // Get timezone from PHP or JavaScript-style timezone
    $timezone = date_default_timezone_get();

    // If timezone is not set, try to detect from request headers
    if ($timezone === 'UTC' || empty($timezone)) {
        // Check for timezone in request (if sent from frontend)
        $timezone = $_GET['tz'] ?? $_SERVER['HTTP_TIMEZONE'] ?? '';
        if (empty($timezone)) {
            // Default to Bangladesh timezone for this project
            $timezone = 'Asia/Dhaka';
        }
    }

    // Comprehensive timezone to country mapping
    $countryMap = [
        'Asia/Dhaka' => 'BD',
        'Asia/Kolkata' => 'IN',
        'Asia/Karachi' => 'PK',
        'Asia/Shanghai' => 'CN',
        'Asia/Tokyo' => 'JP',
        'Asia/Seoul' => 'KR',
        'Asia/Bangkok' => 'TH',
        'Asia/Singapore' => 'SG',
        'Asia/Hong_Kong' => 'HK',
        'Asia/Manila' => 'PH',
        'Asia/Jakarta' => 'ID',
        'Asia/Kuala_Lumpur' => 'MY',
        'America/New_York' => 'US',
        'America/Los_Angeles' => 'US',
        'America/Chicago' => 'US',
        'America/Denver' => 'US',
        'America/Toronto' => 'CA',
        'America/Vancouver' => 'CA',
        'America/Mexico_City' => 'MX',
        'America/Sao_Paulo' => 'BR',
        'America/Buenos_Aires' => 'AR',
        'Europe/London' => 'GB',
        'Europe/Paris' => 'FR',
        'Europe/Berlin' => 'DE',
        'Europe/Rome' => 'IT',
        'Europe/Madrid' => 'ES',
        'Europe/Amsterdam' => 'NL',
        'Europe/Moscow' => 'RU',
        'Europe/Istanbul' => 'TR',
        'Australia/Sydney' => 'AU',
        'Australia/Melbourne' => 'AU',
        'Australia/Perth' => 'AU',
        'Pacific/Auckland' => 'NZ',
        'Africa/Cairo' => 'EG',
        'Africa/Johannesburg' => 'ZA',
        'Africa/Lagos' => 'NG'
    ];

    return $countryMap[$timezone] ?? 'BD'; // Default to Bangladesh for this project
}

try {
    // Try IP-based detection first
    $country_code = getCountryFromIP();

    // Fallback to timezone detection if IP detection fails
    if (!$country_code) {
        $country_code = getCountryFromTimezone();
    }

    echo json_encode([
        'success' => true,
        'country_code' => $country_code,
        'method' => $country_code ? 'ip' : 'timezone'
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Failed to detect country',
        'country_code' => 'US' // Default fallback
    ]);
}
?>