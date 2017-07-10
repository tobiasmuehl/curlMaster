# curlMaster

Simple curl wrapper with response caching and cookie storage.

## Usage

```php
use peterkahl\curlMaster\curlMaster;

$curlm = new curlMaster;

# Specify location of CA certificate file
$curlm->ca_file = '/srv/certs/ca-bundle.crt';

# If you need to set User Agent...
$curlm->useragent = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.12; rv:55.0) Gecko/20100101 Firefox/55.0';

# Specify caching time in seconds to force override of any caching headers.
$curlm->ForcedCacheMaxAge = 3600;

# The URL you want to cURL
$response = $curlm->get_curl('https://github.com/');

$url        = $response['url'];
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
array(8) {
  ["url"]=>
  string(19) "https://github.com/"
  ["useragent"]=>
  string(82) "Mozilla/5.0 (Macintosh; Intel Mac OS X 10.12; rv:55.0) Gecko/20100101 Firefox/55.0"
  ["headers"]=>
  array(23) {
    ["status"]=>
    string(15) "HTTP/1.1 200 OK"
    ["date"]=>
    string(29) "Mon, 10 Jul 2017 01:34:45 GMT"
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
    string(99) "logged_in=no; domain=.github.com; path=/; expires=Fri, 10 Jul 2037 01:34:45 -0000; secure; HttpOnly"
    ["set-cookie-2"]=>
    string(277) "_gh_sess=eyJzZXNzaW9uX2lkIjoiNTdkOTYxMTk4YWJjMmFjMDUxYjQ0OThhNzk2MmY3NjgiLCJsYXN0X3JlYWRfZnJvbV9yZXBsaWNhcyI6MTQ5OTY1MDQ4NTE2OCwiX2NzcmZfdG9rZW4iOiJKc01pOHNmQ2dPRTZYTzZnT244cEU5VHZ2K3pKV2puamVtWlVoQW84NHAwPSJ9--4fde405b50324c9aaa72f01ef1f41a54b2db6fda; path=/; secure; HttpOnly"
    ["x-request-id"]=>
    string(32) "42b939977b0e7b6935e506691885610d"
    ["x-runtime"]=>
    string(8) "0.064948"
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
    string(8) "0.071398"
    ["content-encoding"]=>
    string(4) "gzip"
    ["vary-3"]=>
    string(15) "Accept-Encoding"
    ["x-github-request-id"]=>
    string(35) "0626:1FAF0:27D3566:3CDF102:5962D9B4"
  }
  ["body"]=>
  string(54932) "<!DOCTYPE html>
<html lang="en">
  <head>

  ... TRUNCATED

  </body>
</html>"
  ["filename"]=>
  string(58) "/CURL_RESPON-95e60e0de85c2363212e4714d376d2a64b03b6b4.3600"
  ["exectime"]=>
  string(8) "1.16 sec"
  ["cookiefile"]=>
  string(70) "/srv/cache/CURL_COOKIE-c2208abde9668e8e9815c3690855edd1e63abeac.604800"
  ["status"]=>
  string(3) "200"
  ["origin"]=>
  string(5) "cache"
}
*/
```

### Cache Purging:

Although the cache is being purged automatically, you may want to purge the cache yourself (e.g. as crontab job).

```php
use peterkahl\curlMaster\curlMaster;

$curlm = new curlMaster;

$curlm->PurgeCache();
```
