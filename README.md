# curlMaster

Simple curl wrapper. At the moment only method GET.

## Usage

```php
use peterkahl\curlMaster\curlMaster;

$curlm = new curlMaster;

# Specify location of CA certificate file
$curlm->ca_file = '/srv/certs/ca-bundle.crt';

# If you need to set User Agent...
$curlm->useragent = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.12; rv:56.0) Gecko/20100101 Firefox/56.0';

# Optional forced caching
$curlm->CacheResponse = true;

# Specify caching time in seconds
$curlm->CacheMaxAge   = 180;

# The URL you want to cURL
$response = $curlm->get_curl('https://github.com/');

$headers  = $response['headers'];
$body     = $response['body'];
$status   = $response['status'];
$filename = $response['filename'];

if ($status != '200') {
  throw new Exception('HTTP request failed with status '.$status);
}

var_dump($response);

/*
array(6) {
  ["url"]=>
  string(19) "https://github.com/"
  ["useragent"]=>
  string(82) "Mozilla/5.0 (Macintosh; Intel Mac OS X 10.12; rv:56.0) Gecko/20100101 Firefox/56.0"
  ["headers"]=>
  array(23) {
    ["status"]=>
    string(15) "HTTP/1.1 200 OK"
    ["date"]=>
    string(29) "Sat, 24 Jun 2017 23:21:39 GMT"
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
    string(99) "logged_in=no; domain=.github.com; path=/; expires=Wed, 24 Jun 2037 23:21:39 -0000; secure; HttpOnly"
    ["set-cookie-2"]=>
    string(277) "_gh_sess=eyJzZXNzaW9uX2lkIjoiNWJjNzc4YzFlYzdhYTVhNDc1MzM0OGY2ODI1OTE1YjciLCJsYXN0X3JlYWRfZnJvbV9yZXBsaWNhcyI6MTQ5ODM0NjQ5OTQyMCwiX2NzcmZfdG9rZW4iOiJLRG9DUXBjQzJyUVhzdHV3NmFKamR3aVlHUkgwYUhIRC8zTy9tdnRhcFA0PSJ9--0dd38afc9a0dbaed509e890ccdabef195efac16b; path=/; secure; HttpOnly"
    ["x-request-id"]=>
    string(32) "2b79471e821d261397c98996382fcf01"
    ["x-runtime"]=>
    string(8) "0.060952"
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
    string(8) "0.066299"
    ["content-encoding"]=>
    string(4) "gzip"
    ["vary-3"]=>
    string(15) "Accept-Encoding"
    ["x-github-request-id"]=>
    string(35) "B348:28EF0:620E5BB:9347860:594EF402"
  }
  ["body"]=>
  string(98950) "<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">

  ... TRUNCATED

  </body>
</html>"
  ["status"]=>
  string(3) "200"
  ["filename"]=>
  string(50) "/CURL-edea68cb9b64a35b80e914c7c49a9936f33c7b56.180"
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
