<?php
// 配置文件 - 用于统一管理项目路径
class Config {
    // 获取基础URL
    public static function getBaseUrl() {
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
        $host = $_SERVER['HTTP_HOST'];
        $basePath = dirname(dirname($_SERVER['PHP_SELF']));
        $basePath = rtrim($basePath, '/');
        return $protocol . $host . $basePath;
    }
    
    // 获取买家端URL
    public static function getBuyerUrl($key = '') {
        $url = self::getBaseUrl() . '/buyer.php';
        if ($key) {
            $url .= '?key=' . urlencode($key);
        }
        return $url;
    }
    
    // 获取卖家端URL
    public static function getSellerUrl() {
        return self::getBaseUrl() . '/seller.php';
    }
    
    // 获取API基础URL
    public static function getApiUrl($endpoint = '') {
        $url = self::getBaseUrl() . '/api/';
        if ($endpoint) {
            $url .= $endpoint;
        }
        return $url;
    }
}
?>