# curlMaster
Simple curl wrapper. At the moment, only method GET.

## Usage

```php
use peterkahl\curlMaster\curlMaster;

$curlm = new curlMaster;
$response = $curlm->get_curl('http://some.url/blah.blah');
if (!empty($response)) {
  echo $response;
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
  echo $response;
}
```
