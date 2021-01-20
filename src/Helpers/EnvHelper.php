<?php

namespace Tunguska\Monolog\Helpers;

class EnvHelper
{
    /**
     * $_SERVER keys to get server data
     * @var array<mixed>
     */
    public static $serverEnvInfo = [
        'REQUEST_METHOD' => 'request_method',
        'REQUEST_URI' => 'request_uri',
        'HTTP_HOST' => 'request_host',
        'SCRIPT_NAME' => 'script_name',
        'REMOTE_ADDR' => 'ip',
        'HTTP_USER_AGENT' => 'user_agent',
        'argv' => 'argv',

        /** @see http://nginx.org/ru/docs/http/ngx_http_geoip_module.html */
        'GEOIP_LATITUDE' => 'latitude',
        'GEOIP_LONGITUDE' => 'longitude',
        'GEOIP_COUNTRY_CODE' => 'country_code',
        'GEOIP_COUNTRY_NAME' => 'country_name',
        'GEOIP_REGION' => 'region',
        'GEOIP_CITY' => 'city',
        'GEOIP_CITY_CONTINENT_CODE' => 'continent_code',
    ];

    /**
     * @param array<mixed> $customEnvInfo
     *
     * @return array<mixed>
     */
    public static function getServerInfo(array $customEnvInfo = []): array
    {
        return array_merge(
            self::getFieldsByMapping(self::$serverEnvInfo, $_SERVER),
            $customEnvInfo
        );
    }

    /**
     * Mapping $parentArray keys to fields array
     *
     * @param array<mixed> $fieldsMapping
     * @param array<mixed> $parentArray
     *
     * @return array<mixed>
     */
    public static function getFieldsByMapping(array $fieldsMapping = [], array $parentArray = []): array
    {
        $out = [];
        foreach ($fieldsMapping as $field => $key) {
            if (!empty($parentArray[$field])) {
                $out[$key] = $parentArray[$field];
            }
        }

        return $out;
    }
}
