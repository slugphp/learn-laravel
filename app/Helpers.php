<?php

function br()
{
    //windows平台相当于    echo "\r\n";
    //unix\linux平台相当于    echo "\n";
    //mac平台相当于    echo "\r";
    return PHP_SAPI == 'cli' ? PHP_EOL : '<br>';
}

/**
 * 缩进数据，以json在HTML上展示
 */
function indentToJson($data)
{
    $json = json_encode($data, JSON_PRETTY_PRINT);
    if (PHP_SAPI == 'cli') return $json;
    $search = ["\n", " "];
    $replace = ['<br>', '&nbsp;'];
    return str_replace($search, $replace, $json);
}


/**
 * simple curl
 * @param  string $url
 * @param  array  $param
 * @return mix
 */
function simpleCurl($url = '', $param = []) {
    // params init
    if (!$url) return false;
    $parseUrl = parse_url($url);
    if (!isset($param['method'])) $param['method'] = 'get';
    if (!isset($param['data'])) $param['data'] = [];
    if (!isset($param['header'])) $param['header'] = [];
    if (!isset($param['cookie'])) $param['cookie'] = [];
    if (!isset($param['return'])) $param['return'] = 'body';
    // cookie keep
    $sessionKey = md5($parseUrl['host'] . 'simple-curl');
    $cookieFunc = function ($action = 'get', $cookieData = []) use ($sessionKey) {
        $dir = __DIR__ . '/simple-curl-cache';
        @mkdir($dir);
        if ($action == 'set') {
            return @file_put_contents("$dir/$sessionKey", json_encode($cookieData));
        } else {
            return json_decode(@file_get_contents("$dir/$sessionKey"), true);
        }

    };
    // curl init
    $ch = curl_init();
    if ($param['method'] == 'get' && $param['data']) {
        $joint = $parseUrl['query'] ? '&' : '?';
        $url .= $joint . http_build_query($param['data']);
    }
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    // https支持
    if ($parseUrl['scheme'] == 'https') {
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    }
    // header
    $header = [];
    if (strpos(json_encode($param['header']), 'User-Agent') === false) {
        $header[] = 'User-Agent:Mozilla/5.0 (Macintosh; Intel Mac OS X 10_12_1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/55.0.2883.95 Safari/537.36';
    }
    if (is_string($param['header'])) {
        foreach (explode("\n", $param['header']) as $v) {
            $header[] = trim($v);
        }
    } else if (is_array($param['header'])) {
        $header = array_merge($header, $param['header']);
    }
    curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
    // cookie keep
    $curloptCookie = '';
    $cookieData = $cookieFunc('get');
    if (is_string($param['cookie'])) {
        $curloptCookie .= $param['cookie'];
    } else if (is_array($param['cookie']) && is_array($cookieData)) {
        $cookieData = array_merge($cookieData, $param['cookie']);
    }
    if ($cookieData) {
        foreach ($cookieData as $k => $v) {
            $curloptCookie .= "$k=$v;";
        }
    }
    curl_setopt($ch, CURLOPT_COOKIE, $curloptCookie);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    // post
    if ($param['method'] == 'post') {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $param['data']);
    }
    // response
    curl_setopt($ch, CURLOPT_HEADER, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $header = trim(substr($response, 0, $headerSize));
    $body = trim(substr($response, $headerSize));
    curl_close($ch);
    // update cookie
    preg_match_all('/Set-Cookie:(.*?)\n/', $header, $matchesCookie);
    if (is_array($matchesCookie[1])) {
        foreach ($matchesCookie[1] as $setCookie) {
            foreach (explode(';', $setCookie) as $cookieStr) {
                @list($key, $value) = explode('=', trim($cookieStr));
                $cookieData[$key] = $value;
            }
        }
    }
    $cookieFunc('set', $cookieData);
    // return
    $return = $param['return'] == 'header' ? $header :
        ($param['return'] == 'all' ? [$header, $body] : $body);
    return $return;
}