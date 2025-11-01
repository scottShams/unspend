<?php

/**
 * Environment Configuration Loader
 * Loads environment variables from .env file
 */

class Env {
    private static $loaded = false;
    private static $env = [];

    /**
     * Load environment variables from .env file
     */
    public static function load($path = null) {
        if (self::$loaded) {
            return;
        }

        $envFile = $path ?: __DIR__ . '/../.env';

        if (!file_exists($envFile)) {
            // Try to load from .env.example if .env doesn't exist
            $exampleFile = __DIR__ . '/../.env.example';
            if (file_exists($exampleFile)) {
                $envFile = $exampleFile;
            } else {
                throw new Exception('.env file not found. Please create .env file or copy from .env.example');
            }
        }

        $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        foreach ($lines as $line) {
            // Skip comments
            if (strpos(trim($line), '#') === 0) {
                continue;
            }

            // Parse key=value pairs
            if (strpos($line, '=') !== false) {
                list($key, $value) = explode('=', $line, 2);
                $key = trim($key);
                $value = trim($value);

                // Remove quotes if present
                $value = trim($value, '"\'');
                self::$env[$key] = $value;
            }
        }

        self::$loaded = true;
    }

    /**
     * Get environment variable
     */
    public static function get($key, $default = null) {
        if (!self::$loaded) {
            self::load();
        }

        return self::$env[$key] ?? $default;
    }

    /**
     * Set environment variable (for testing)
     */
    public static function set($key, $value) {
        self::$env[$key] = $value;
    }

    /**
     * Check if environment variable exists
     */
    public static function has($key) {
        if (!self::$loaded) {
            self::load();
        }

        return isset(self::$env[$key]);
    }

    /**
     * Get all environment variables
     */
    public static function all() {
        if (!self::$loaded) {
            self::load();
        }

        return self::$env;
    }
}

// Auto-load environment variables
Env::load();