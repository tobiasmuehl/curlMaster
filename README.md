#curlMaster
Simple curl wrapper. At the moment, only method GET.

##Usage

```php
require __DIR__.'/curlmaster.class.php';

$curlm = new curlmaster;
$response = $curlm->get_curl('http://some.url/blah.blah');
if (!empty($response)) {
  echo $response;
}
```