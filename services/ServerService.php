<?php

class ServerService
{
    public static function getIpAddress(): string
    {
        if (isset($_SERVER["HTTP_CF_CONNECTING_IP"]) && filter_var($_SERVER["HTTP_CF_CONNECTING_IP"], FILTER_VALIDATE_IP)) {
            return $_SERVER["HTTP_CF_CONNECTING_IP"];
        }

        if (!empty($_SERVER["HTTP_X_FORWARDED_FOR"])) {
            $ips = array_map("trim", explode(",", $_SERVER["HTTP_X_FORWARDED_FOR"]));
            foreach ($ips as $ip) {
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }

        $remoteAddr = $_SERVER["REMOTE_ADDR"] ?? "127.0.0.1";
        return $remoteAddr === "127.0.0.1" ? "66.249.66.138" : $remoteAddr;
    }

    public static function getUserAgent(): string
    {
        return $_SERVER["HTTP_USER_AGENT"] ?? "";
    }
}