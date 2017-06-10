# curlMaster

Simple curl wrapper. At the moment only method GET. Response headers are returned as an array.

## Usage

```php
use peterkahl\curlMaster\curlMaster;

$curlm = new curlMaster;
$response = $curlm->get_curl('http://some.url/blah.blah');
if (!empty($response)) {
  echo 'Headers:'. PHP_EOL . var_dump($response['headers']) . PHP_EOL . PHP_EOL;
  echo 'Body:'   . PHP_EOL . $response['body']              . PHP_EOL . PHP_EOL;
}
```

### When using HTTPS:
```php
use peterkahl\curlMaster\curlMaster;

$curlm = new curlMaster;

# Specify location of CA certificate file
$curlm->ca_file = '/srv/certs/ca-bundle.crt';

$response = $curlm->get_curl('https://some.secure.url/blah.blah');
if (!empty($response)) {
  echo 'Headers:'. PHP_EOL . var_dump($response['headers']) . PHP_EOL . PHP_EOL;
  echo 'Body:'   . PHP_EOL . $response['body']              . PHP_EOL . PHP_EOL;
}
```

### Response Caching:

Optionally, you can enable `Response Caching`. This is useful when you expect the same response for each request, when debugging, when you cUrl an API with request limit.

```php
use peterkahl\curlMaster\curlMaster;

$curlm = new curlMaster;

# Enable response caching
$curlm->CacheResponse = true;

# Specify cache directory
$curlm->CacheDir = '/var/www';

# Specify location of CA certificate file
$curlm->ca_file = '/srv/certs/ca-bundle.crt';

$response = $curlm->get_curl('https://some.secure.url/blah.blah');
if (!empty($response)) {
  echo 'Headers:'. PHP_EOL . var_dump($response['headers']) . PHP_EOL . PHP_EOL;
  echo 'Body:'   . PHP_EOL . $response['body']              . PHP_EOL . PHP_EOL;
}
```

### Cache Purging:

Although the cache is being purged automatically, you may purge cache yourself (e.g. as crontab job).

```php
use peterkahl\curlMaster\curlMaster;

$curlm = new curlMaster;

$curlm->PurgeCache();
```
