# curlMaster

A wrapper for the cURL extension with response caching and cookie storage, with request methods GET, POST, HEAD.

## Usage example, method GET:

```php
use peterkahl\curlMaster\curlMaster;

$curlm = new curlMaster;

# Set the cache directory
$curlm->CacheDir = '/var/www/cache';

# If you want to use SSL/TLS, you need to set the location of CA certificate file.
# You may download and install on your server this Mozilla CA bundle
# from this page: <https://curl.haxx.se/docs/caextract.html>
$curlm->ca_file = '/srv/certs/ca-bundle.crt';

# If you need to set User Agent...
$curlm->useragent = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.12; rv:55.0) Gecko/20100101 Firefox/55.0';

/**
 * Caching control & Maximum age of forced cache (in seconds).
 *
 * All responses are cached, but when this value is > 0, caching
 * will be forced regardless of the response headers.
 * Forced caching is useful when you expect the same response for each
 * request or when:
 *   -- debugging
 *   -- you cURL an API with request limit
 *
 * @var integer .... value 0 disables forced caching while header-dependent caching is still on
 *                   value >0 enables forced caching and overrides header-dependent caching
 *                   value <0 disables caching altogether (example -1)
 *
 */
$curlm->ForcedCacheMaxAge = 3600;

/**
 * Enable purging of outdated cache files on each request.
 * Disabled (false) by default.
 * If you don't purge the cache yourself (i.e. using crontab and
 * the method PurgeCache), you should change this to true.
 * @var boolean
 */
public $curlm->PurgeEnableOnEachRequest = false;

# The URL you want to cURL (method GET)
$response = $curlm->Request('https://github.com/');

$url        = $response['url'];
$metod      = $response['method'];
$req_data   = $response['req_data'];
$useragent  = $response['useragent'];
$headers    = $response['headers'];
$body       = $response['body'];
$filename   = $response['filename'];
$cookiefile = $response['cookiefile'];
$exectime   = $response['exectime'];
$status     = $response['status'];
$origin     = $response['origin'];

if ($status != '200') {
  throw new Exception('HTTP request failed with status '.$status);
}

var_dump($response);

/*
array(11) {
  ["url"]=>
  string(19) "https://github.com/"
  ["method"]=>
  string(3) "GET"
  ["req_data"]=>
  string(0) ""
  ["useragent"]=>
  string(82) "Mozilla/5.0 (Macintosh; Intel Mac OS X 10.12; rv:55.0) Gecko/20100101 Firefox/55.0"
  ["headers"]=>
  array(22) {
    ["status"]=>
    string(15) "HTTP/1.1 200 OK"
    ["date"]=>
    string(29) "Fri, 14 Jul 2017 09:20:27 GMT"
    ["content-type"]=>
    string(24) "text/html; charset=utf-8"
    ["transfer-encoding"]=>
    string(7) "chunked"
    ["server"]=>
    string(10) "GitHub.com"
    ["status-1"]=>
    string(6) "200 OK"
    ["cache-control"]=>
    string(8) "no-cache"
    ["vary"]=>
    string(6) "X-PJAX"
    ["x-ua-compatible"]=>
    string(16) "IE=Edge,chrome=1"
    ["set-cookie"]=>
    string(277) "_gh_sess=eyJzZXNzaW9uX2lkIjoiMjhjOWU4NzU0ZmEwMWM3NGJlMjBlMjc1ZGNkOWM5MWEiLCJsYXN0X3JlYWRfZnJvbV9yZXBsaWNhcyI6MTUwMDAyNDAyNjkzNSwiX2NzcmZfdG9rZW4iOiJrM05uanNJTHN1dk1xUWc3NHNUYi9LZ2RPMjNrZHJvazkwU1F2VXhHYkdvPSJ9--d0cc7dd9efbce8aaa918b9d632b7b8b6f31f6b2b; path=/; secure; HttpOnly"
    ["x-request-id"]=>
    string(32) "f29e7ea183a2e1a87757c5abd05f6a9a"
    ["x-runtime"]=>
    string(8) "0.177033"
    ["content-security-policy"]=>
    string(770) "default-src 'none'; base-uri 'self'; child-src render.githubusercontent.com; connect-src 'self' uploads.github.com status.github.com collector.githubapp.com api.github.com www.google-analytics.com github-cloud.s3.amazonaws.com github-production-repository-file-5c1aeb.s3.amazonaws.com github-production-upload-manifest-file-7fdce7.s3.amazonaws.com github-production-user-asset-6210df.s3.amazonaws.com wss://live.github.com; font-src assets-cdn.github.com; form-action 'self' github.com gist.github.com; frame-ancestors 'none'; img-src 'self' data: assets-cdn.github.com identicons.github.com collector.githubapp.com github-cloud.s3.amazonaws.com *.githubusercontent.com; media-src 'none'; script-src assets-cdn.github.com; style-src 'unsafe-inline' assets-cdn.github.com"
    ["strict-transport-security"]=>
    string(44) "max-age=31536000; includeSubdomains; preload"
    ["public-key-pins"]=>
    string(447) "max-age=5184000; pin-sha256="WoiWRyIOVNa9ihaBciRSC7XHjliYS9VwUGOIud4PB18="; pin-sha256="RRM1dGqnDFsCJXBTHky16vi1obOlCgFFn/yOhI/y+ho="; pin-sha256="k2v657xBsOVe1PQRwOsHsw3bsGT2VzIqz5K+59sNQws="; pin-sha256="K87oWBWM9UZfyddvDfoxL+8lpNyoUB2ptGtn0fv6G2Q="; pin-sha256="IQBnNBEiFuhj+8x6X8XLgh01V9Ic5/V3IRQLNFFc7v4="; pin-sha256="iie1VXtL7HzAMF+/PVPR9xzT80kQxdZeJ+zduCB3uj0="; pin-sha256="LvRiGEjRqfzurezaWuj8Wie2gyHMrW5Q06LspMnox7A="; includeSubDomains"
    ["x-content-type-options"]=>
    string(7) "nosniff"
    ["x-frame-options"]=>
    string(4) "deny"
    ["x-xss-protection"]=>
    string(13) "1; mode=block"
    ["x-runtime-rack"]=>
    string(8) "0.185461"
    ["content-encoding"]=>
    string(4) "gzip"
    ["vary-2"]=>
    string(15) "Accept-Encoding"
    ["x-github-request-id"]=>
    string(33) "823A:1208D:846845:C90F8D:59688CDA"
  }
  ["body"]=>
  string(54914) "<!DOCTYPE html>
<html lang="en">
  <head>

  ... TRUNCATED

  </body>
</html>"
  ["filename"]=>
  string(58) "/CURL_RESPON-7784acb958509aaf4c6de4d5293f297cf324df86.3600"
  ["exectime"]=>
  string(8) "2.13 sec"
  ["cookiefile"]=>
  string(70) "/srv/cache/CURL_COOKIE-c2208abde9668e8e9815c3690855edd1e63abeac.604800"
  ["status"]=>
  string(3) "200"
  ["origin"]=>
  string(3) "new"
}
*/
```

## Usage example, method POST:

```php
use peterkahl\curlMaster\curlMaster;

$curlm = new curlMaster;

# Set the cache directory
$curlm->CacheDir = '/mycachedirectory';

# If you want to use SSL/TLS, you need to set the location of CA certificate file.
$curlm->ca_file = '/srv/certs/ca-bundle.crt';

# If you need to set User Agent...
$curlm->useragent = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.12; rv:55.0) Gecko/20100101 Firefox/55.0';

$curlm->ForcedCacheMaxAge = 3600;

$data = array(
  'user' => 'admin',
  'pwd'  => 'oracle',
);

# The URL you want to cURL
$response = $curlm->Request('https://whatever.anything/login', 'POST', $data);

```

## Cache Purging:

Although the cache is being purged automatically on each request (GET, POST, HEAD), an alternative is to purge the cache yourself, e.g. as crontab job using the available method `PurgeCache()`.

```php
use peterkahl\curlMaster\curlMaster;

$curlm = new curlMaster;

# Set the cache directory
$curlm->CacheDir = '/mycachedirectory';

$curlm->PurgeCache();
```
