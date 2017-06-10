# curlMaster

Simple curl wrapper. At the moment, only method GET.

## Usage

```php
use peterkahl\curlMaster\curlMaster;

$curlm = new curlMaster;
$response = $curlm->get_curl('http://some.url/blah.blah');
if (!empty($response)) {
  echo 'Headers:'. PHP_EOL . $response['headers'] . PHP_EOL . PHP_EOL;
  echo 'Body:'   . PHP_EOL . $response['body']    . PHP_EOL . PHP_EOL;
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
  echo 'Headers:'. PHP_EOL . $response['headers'] . PHP_EOL . PHP_EOL;
  echo 'Body:'   . PHP_EOL . $response['body']    . PHP_EOL . PHP_EOL;
}
```

### Response Caching:
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
  echo 'Headers:'. PHP_EOL . $response['headers'] . PHP_EOL . PHP_EOL;
  echo 'Body:'   . PHP_EOL . $response['body']    . PHP_EOL . PHP_EOL;
}
```
