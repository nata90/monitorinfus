<?php

/**
 * Memuat variabel lingkungan dari file .env (jika ada) ke getenv()/$_ENV.
 * Nilai yang sudah ada di environment proses tidak akan ditimpa.
 */

if (class_exists(\Dotenv\Dotenv::class) && is_file(dirname(__DIR__) . '/.env')) {
    \Dotenv\Dotenv::createUnsafeImmutable(dirname(__DIR__))->safeLoad();
}

if (!function_exists('env')) {
    /**
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    function env(string $key, $default = null)
    {
        $value = getenv($key);
        if ($value === false || $value === '') {
            return $default;
        }

        switch (strtolower($value)) {
            case 'true':
                return true;
            case 'false':
                return false;
            case 'null':
                return null;
        }

        return $value;
    }
}
