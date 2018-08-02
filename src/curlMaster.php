<?php
/**
 * Curl Master
 * A wrapper for the cURL extension with response caching and cookie storage.
 *
 * @version    2018-08-02 03:15:22 GMT
 * @author     Peter Kahl <https://github.com/peterkahl>
 * @copyright  2015-2018 Peter Kahl
 * @license    Apache License, Version 2.0
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *      <http://www.apache.org/licenses/LICENSE-2.0>
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace peterkahl\curlMaster;

use \Exception;
use \SplFileObject;

class curlMaster {

  /**
   * Version
   * @var string
   */
  const VERSION = '3.11';

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
  public $ForcedCacheMaxAge = 0;

  /**
   * Whether to enable cookies between sessions.
   * @var boolean
   */
  public $EnableCookies = true;

  /**
   * Maximum age of cached cookies (in seconds).
   * @var integer
   */
  public $CookieMaxAge = 604800;

  /**
   * Cache directory
   * @var string
   */
  public $CacheDir = '/srv/cache';

  /**
   * Filename (incl. path) of CA certificate
   * @var string
   * You may download and install on your server this Mozilla CA bundle
   * from this page:
   * <https://curl.haxx.se/docs/caextract.html>
   */
  public $ca_file;

  /**
   * User Agent
   * You can define your own user agent.
   * @var string
   */
  public $useragent   = '';

  /**
   * Time Out
   * @var integer
   */
  public $timeout_sec = 30;

  /**
   * Manage redirects.
   * @var integer ..... value 0  prevents following redirects
   *                    value >0 limits number of redirects to this number
   *
   */
  public $maxRedirects = 3;

  /**
   * Debug: If enabled (true), exception with details
   * will be thrown when error is encountered.
   * @var boolean
   */
  public $debug = false;

  /**
   * HTTP request headers (optional)
   * @var array
   * Example ... array('Connection: Close', 'X-API-Key: 7KgvBPUXh_XKQAMG');
   */
  public $headers = array();

  /**
   * The loop is used for DNS timeout.
   * @var integer
   */
  private $LoopCount = 0;

  /**
   * The loop is used for DNS timeout.
   * @var integer
   */
  private $LoopLimit = 20;

  /**
   * HTTP methods this library can handle.
   * @var array
   */
  private $Methods = array(
    'GET',
    'POST',
    'HEAD',
  );

  /**
   * Enable purging of outdated cache files on each request.
   * Disabled (false) by default.
   * If you don't purge the cache yourself (i.e. using crontab and
   * the method PurgeCache), you should change this to true.
   * @var boolean
   */
  public $PurgeEnableOnEachRequest = false;

  #===================================================================

  /**
   * Makes cURL Request
   * @param  string  $url
   * @param  string  $method
   * @param  array   $data
   * @param  boolean $forceReq ... Request to temote server will be made
   *                               regardless of existence of a cached file.
   * @return mixed
   * @throws Exception
   */
  public function Request($url, $method = 'GET', $data = array(), $forceReq = false) {
    $start = microtime(true);
    $this->LoopCount++;
    $postStr    = '';
    $cookieFile = '';
    $method     = strtoupper($method);
    #----
    if (!$this->ValidUrl($url)) {
      throw new Exception('Illegal value argument url');
    }
    #----
    if (!$this->ValidMethod($method)) {
      throw new Exception('Illegal value argument method');
    }
    #----
    if ($method == 'POST' && !is_array($data)) {
      throw new Exception('Method POST requires argument data to be array');
    }
    #----
    if (!is_integer($this->maxRedirects) || $this->maxRedirects < 0) {
      throw new Exception('Property maxRedirects must be integer >= 0');
    }
    #----
    if (empty($this->useragent)) {
      $this->useragent = 'Mozilla/5.0 (curlMaster/'. self::VERSION .'; +https://github.com/peterkahl/curlMaster)';
    }
    #----
    $filenameHash = sha1($method . $url . serialize($this->headers) . serialize($data) . $this->useragent);
    ########################################################
    if (!$forceReq && $this->ForcedCacheMaxAge >= 0) {
      if ($this->PurgeEnableOnEachRequest) {
        $this->PurgeCache();
      }
      foreach (glob($this->CacheDir .'/CURL_RESPON-*') as $cfile) {
        $temp = str_replace($this->CacheDir, '', $cfile);
        $temp = substr($temp, 13, 40);
        if ($filenameHash == $temp && file_exists($cfile)) {
          $str = $this->FileGetContents($cfile);
          $arr = unserialize($str);
          $arr['origin']   = 'cache';
          $arr['exectime'] = $this->Benchmark($start);
          return $arr;
        }
      }
    }
    ########################################################
    if (preg_match('/^https:/', $url)) {
      if (empty($this->ca_file)) {
        throw new Exception('Empty property ca_file');
      }
      if (!file_exists($this->ca_file)) {
        throw new Exception('Unable to read file '.$this->ca_file);
      }
    }
    #----
    $ch = curl_init($url);
    if ($ch == false) {
      return false;
    }
    #----
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST,  $method);
    if ($method == 'GET') {
      curl_setopt($ch, CURLOPT_HTTPGET,        true);
      curl_setopt($ch, CURLOPT_ENCODING,       '');                  # This will create the 'Accept-Encoding' header
    }
    elseif ($method == 'POST') {
      $postStr = $this->Array2string($data);
      curl_setopt($ch, CURLOPT_POST,           true);
      curl_setopt($ch, CURLOPT_POSTFIELDS,     $postStr);
      curl_setopt($ch, CURLOPT_ENCODING,       '');                  # This will create the 'Accept-Encoding' header
    }
    elseif ($method == 'HEAD') {
      curl_setopt($ch, CURLOPT_NOBODY,         true);
    }
    #----
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER,         true);                  # Include headers in response
    curl_setopt($ch, CURLOPT_FORBID_REUSE,   true);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $this->timeout_sec);
    curl_setopt($ch, CURLOPT_USERAGENT,      $this->useragent);      # This will create the 'User-Agent' header
    #----
    if ($this->maxRedirects == 0) {
      curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);               # Don't follow redirects
    }
    elseif ($this->maxRedirects > 0) {
      curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);                # Follow redirects
      curl_setopt($ch, CURLOPT_MAXREDIRS,      $this->maxRedirects); # Limit number of redirects
    }
    #----
    if ($this->EnableCookies) {
      $cookieFile = $this->GetCookieFileName($url);
      curl_setopt($ch, CURLOPT_COOKIEJAR,    $cookieFile);
      curl_setopt($ch, CURLOPT_COOKIEFILE,   $cookieFile);
    }
    #----
    if (!empty($this->headers)) {
      curl_setopt($ch, CURLOPT_HTTPHEADER,   $this->headers);
    }
    #----
    if (preg_match('/^https:/', $url)) {
      curl_setopt($ch, CURLOPT_SSLVERSION,      6);                  # TLSv1.2
      curl_setopt($ch, CURLOPT_SSL_VERIFYPEER,  true);
      curl_setopt($ch, CURLOPT_SSL_VERIFYHOST,  2);
      curl_setopt($ch, CURLOPT_CAINFO,          $this->ca_file);
      curl_setopt($ch, CURLOPT_SSL_CIPHER_LIST, 'AESGCM:!PSK');
    }
    #----
    $res = curl_exec($ch);
    $err = curl_errno($ch);
    #----
    if ($err == 0) { # Success
      curl_close($ch);
      $headers = $this->getHeaders($res);
      $body    = $this->getBody($res);
      $status  = preg_replace('/^HTTP\/\d\.\d\ (\d{3})\ .+$/', '\\1', $headers['status']);
      $arr = array(
        'url'        => $url,
        'method'     => $method,
        'req_data'   => $postStr,
        'useragent'  => $this->useragent,
        'headers'    => $headers,
        'body'       => $body,
        'filename'   => '',
        'exectime'   => '',
        'cookiefile' => $cookieFile,
        'status'     => $status,
        'origin'     => 'new',
        'error'      => $this->curlErrorCode($err),
      );
      ######################################################
      # Cache only if status 200
      if ($status == '200' && $this->ForcedCacheMaxAge >= 0) {
        $cacheTime = $this->ParseCachingHeader($headers);
        if (!empty($this->ForcedCacheMaxAge) && $this->ForcedCacheMaxAge > $cacheTime) {
          $cacheTime = $this->ForcedCacheMaxAge;
        }
        if ($cacheTime > 0) {
          $ext = (string) $cacheTime;
          $filename = '/CURL_RESPON-'. $filenameHash .'.'. $ext;
          $arr['filename'] = $filename;
          $arr['exectime'] = $this->Benchmark($start);
          $this->FilePutContents($this->CacheDir . $filename, serialize($arr));
          return $arr;
        }
      }
      ######################################################
      $arr['exectime'] = $this->Benchmark($start);
      return $arr;
    }
    elseif ($err == 6 && $this->LoopCount <= $this->LoopLimit) { # Couldn't resolve host
      usleep(500000);
      return $this->Request($url, $method, $data);
    }
    #----
    $info = curl_getinfo($ch);
    curl_close($ch);
    if (!$this->debug) {
      return array(
        'url'        => $url,
        'method'     => $method,
        'req_data'   => $postStr,
        'useragent'  => $this->useragent,
        'headers'    => '',
        'body'       => '',
        'filename'   => '',
        'exectime'   => $this->Benchmark($start),
        'cookiefile' => $cookieFile,
        'status'     => '',
        'origin'     => 'new',
        'error'      => $this->curlErrorCode($err),
      );
    }
    throw new Exception('CURL ERROR No. '.$err.'. Details are --'                 . PHP_EOL . PHP_EOL .
      str_pad('ERROR ',            22, '.', STR_PAD_RIGHT) .' '. $this->curlErrorCode($err) . PHP_EOL .
      str_pad('Loop Count ',       22, '.', STR_PAD_RIGHT) .' '. $this->LoopCount           . PHP_EOL .
      str_pad('URL ',              22, '.', STR_PAD_RIGHT) .' '. $info['url']               . PHP_EOL .
      str_pad('HTTP Code ',        22, '.', STR_PAD_RIGHT) .' '. $info['http_code']         . PHP_EOL .
      str_pad('Connect Time ',     22, '.', STR_PAD_RIGHT) .' '. $info['connect_time']      . PHP_EOL .
      str_pad('Total Time ',       22, '.', STR_PAD_RIGHT) .' '. $info['total_time']        . PHP_EOL .
      str_pad('Name Lookup Time ', 22, '.', STR_PAD_RIGHT) .' '. $info['namelookup_time']   . PHP_EOL .
      str_pad('PHP Version ',      22, '.', STR_PAD_RIGHT) .' '. $this->getPHPversion()     . PHP_EOL . PHP_EOL
    );
  }

  #===================================================================

  /**
   * Validates Method
   * @param  string $str
   * @return boolean
   */
  private function ValidMethod($str) {
    return in_array($str, $this->Methods);
  }

  #===================================================================

  /**
   * Validates URL string
   * @param  string $url
   * @return boolean
   */
  private function ValidUrl($url) {
    if (preg_match('#^https?://([a-zA-Z0-9]|[a-zA-Z0-9][a-zA-Z0-9\-]{0,61}[a-zA-Z0-9])(\.([a-zA-Z0-9]|[a-zA-Z0-9][a-zA-Z0-9\-]{0,61}[a-zA-Z0-9]))*(:\d{1,5})?/\S*$#', $url)) {
      return true;
    }
    return false;
  }

  #===================================================================

  /**
   * Converts array to string
   * @param  array  $arr
   * @param  string $glue
   * @return string
   * @throws Exception
   */
  private function Array2string($arr, $glue = '&') {
    if (!is_array($arr)) {
      throw new Exception('Argument arr must be array');;
    }
    $new = array();
    foreach ($arr as $k => $v) {
      $new[] = urlencode($k) .'='. urlencode($v);
    }
    return implode($glue, $new);
  }

  #===================================================================

  /**
   * Parses headers from string
   * @param  string $str
   * @return array
   */
  private function getHeaders($str) {
    $str = explode("\r\n\r\n", $str);
    $str = reset($str);
    $str = explode("\r\n", $str);
    $new = array();
    $s = 1;
    foreach ($str as $line) {
      $pos = strpos($line, ': ');
      if ($pos !== false) {
        $key = strtolower(substr($line, 0, $pos));
        if (!isset($new[$key])) {
          $new[$key] = preg_replace('/\s+/', ' ', trim(substr($line, $pos+1)));
        }
        else {
          $new[$key.'-'.$s] = preg_replace('/\s+/', ' ', trim(substr($line, $pos+1)));
          $s++;
        }
      }
      else {
        $new['status'] = $line; # HTTP/1.1 200 OK
      }
    }
    return $new;
  }

  #===================================================================

  /**
   * Parses body from string
   * @param  string $str
   * @return string
   */
  private function getBody($str) {
    $str = substr($str, strpos($str, "\r\n\r\n"));
    return trim($str);
  }

  #===================================================================

  /**
   * Parses the response headers and returns the number of seconds
   * that will be the maximum caching time of our cached file.
   * @param  array  $arr
   * @return integer
   */
  private function ParseCachingHeader($arr) {
    #--------------------------------------
    if (!empty($arr['cache-control'])) {
      if (strpos('no-cache', $arr['cache-control']) !== false) {
        return 0;
      }
      if (strpos('no-store', $arr['cache-control']) !== false) {
        return 0;
      }
      if (strpos('max-age=0', $arr['cache-control']) !== false) {
        return 0;
      }
      if (preg_match('/\bmax-age=(\d+),?\b/', $arr['cache-control'], $match)) {
        return (integer) $match[1];
      }
    }
    #--------------------------------------
    if (!empty($arr['expires'])) {
      if (preg_match('/^-\d+|0$/', $arr['expires'])) {
        return 0;
      }
      $epoch = strtotime($arr['expires']);
      $sec = $epoch - time();
      if ($sec > 0) {
        return $sec;
      }
      return 0;
    }
    #--------------------------------------
    return 0;
  }

  #===================================================================

  /**
   * Purge cache
   * Typical file names:
   *     cached response ..... /CURL_RESPON-35b0deaf2469fd1c803a7c721905d9d28d46e91b.86400
   *     cookie file ......... /CURL_COOKIE-35b0deaf2469fd1c803a7c721905d9d28d46e91b.86400
   * The extension signifies maximum age (caching time).
   */
  public function PurgeCache() {
    foreach (glob($this->CacheDir .'/CURL_*') as $filename) {
      $seconds = $this->MaxAge($filename);
      if (file_exists($filename) && @filemtime($filename) < (time() - $seconds)) {
        @unlink($filename);
      }
    }
  }

  #===================================================================

  /**
   * Returns name of a cookie file
   * @param  string $url
   * @return string
   */
  private function GetCookieFileName($url) {
    $temp = parse_url($url);
    $ext  = (string) $this->CookieMaxAge;
    return $this->CacheDir .'/CURL_COOKIE-'. sha1($temp['host']) .'.'. $ext;
  }

  #===================================================================

  /**
   * File extension signifies maximum age (caching time).
   * @param  string $str
   * @return integer
   * @throws Exception
   */
  private function MaxAge($str) {
    if (strpos($str, '.') === false) {
      throw new Exception('File has no extension');
    }
    $str = strrchr($str, '.');
    $str = substr($str, 1);
    return (integer) $str;
  }

  #===================================================================

  /**
   * Deletes a cache file
   * @param  string $filename
   */
  public function DeleteChacheFile($filename) {
    if (preg_match('/^\/CURL_RESPON-[0-9a-f]{40}\.\d+$/', $filename)) {
      $filename = $this->CacheDir . $filename;
      if (file_exists($filename)) {
        unlink($filename);
      }
    }
  }

  #===================================================================

  /**
   * Returns PHP Version
   * @return  string
   */
  private function getPHPversion() {
    $ver = phpversion();
    preg_match('/^(\d+\.\d+\.?\d*)/', $ver, $match);
    if (empty($match[1])) {
      return '';
    }
    return $match[1];
  }

  #===================================================================

  /**
   * Subtracts two floats and makes the result
   * human-readable
   * @param  float  $st
   * @return string
   */
  private function Benchmark($st) {
    $val = (microtime(true) - $st);
    if ($val >= 1) {
      return number_format($val, 2, '.', ',').' sec';
    }
    $val = $val * 1000;
    if ($val >= 1) {
      return number_format($val, 2, '.', ',').' msec';
    }
    $val = $val * 1000;
    return number_format($val, 2, '.', ',').' μsec';
  }

  #===================================================================

  /**
   * Saves string inside a file
   * @param  string  $file
   * @param  string  $str
   * @return boolean
   */
  private function FilePutContents($file, $str) {
    $fileObj = new SplFileObject($file, 'w');
    while (!$fileObj->flock(LOCK_EX)) {
      usleep(1);
    }
    $bytes = $fileObj->fwrite($str);
    $fileObj->flock(LOCK_UN);
    return $bytes;
  }

  #===================================================================

  /**
   * Retrieves contents of a file
   * @param  string  $file
   * @return string
   */
  private function FileGetContents($file) {
    $fileObj = new SplFileObject($file, 'r');
    while (!$fileObj->flock(LOCK_EX)) {
      usleep(1);
    }
    $str = $fileObj->fread($fileObj->getSize());
    $fileObj->flock(LOCK_UN);
    return $str;
  }

  #===================================================================

  /**
   * Human-readable error code
   * @param  integer $n
   * @return string
   */
  private function curlErrorCode($n) {
    $code = array (
      0  => 'CURLE_OK',
      1  => 'CURLE_UNSUPPORTED_PROTOCOL',
      2  => 'CURLE_FAILED_INIT',
      3  => 'CURLE_URL_MALFORMAT',
      4  => 'CURLE_NOT_BUILT_IN',
      5  => 'CURLE_COULDNT_RESOLVE_PROXY',
      6  => 'CURLE_COULDNT_RESOLVE_HOST',
      7  => 'CURLE_COULDNT_CONNECT',
      8  => 'CURLE_FTP_WEIRD_SERVER_REPLY',
      9  => 'CURLE_REMOTE_ACCESS_DENIED',
      10 => 'CURLE_FTP_ACCEPT_FAILED',
      11 => 'CURLE_FTP_WEIRD_PASS_REPLY',
      12 => 'CURLE_FTP_ACCEPT_TIMEOUT',
      13 => 'CURLE_FTP_WEIRD_PASV_REPLY',
      14 => 'CURLE_FTP_WEIRD_227_FORMAT',
      15 => 'CURLE_FTP_CANT_GET_HOST',
      17 => 'CURLE_FTP_COULDNT_SET_TYPE',
      18 => 'CURLE_PARTIAL_FILE',
      19 => 'CURLE_FTP_COULDNT_RETR_FILE',
      21 => 'CURLE_QUOTE_ERROR',
      22 => 'CURLE_HTTP_RETURNED_ERROR',
      23 => 'CURLE_WRITE_ERROR',
      25 => 'CURLE_UPLOAD_FAILED',
      26 => 'CURLE_READ_ERROR',
      27 => 'CURLE_OUT_OF_MEMORY',
      28 => 'CURLE_OPERATION_TIMEDOUT',
      30 => 'CURLE_FTP_PORT_FAILED',
      31 => 'CURLE_FTP_COULDNT_USE_REST',
      33 => 'CURLE_RANGE_ERROR',
      34 => 'CURLE_HTTP_POST_ERROR',
      35 => 'CURLE_SSL_CONNECT_ERROR',
      36 => 'CURLE_BAD_DOWNLOAD_RESUME',
      37 => 'CURLE_FILE_COULDNT_READ_FILE',
      38 => 'CURLE_LDAP_CANNOT_BIND',
      39 => 'CURLE_LDAP_SEARCH_FAILED',
      41 => 'CURLE_FUNCTION_NOT_FOUND',
      42 => 'CURLE_ABORTED_BY_CALLBACK',
      43 => 'CURLE_BAD_FUNCTION_ARGUMENT',
      45 => 'CURLE_INTERFACE_FAILED',
      47 => 'CURLE_TOO_MANY_REDIRECTS',
      48 => 'CURLE_UNKNOWN_OPTION',
      49 => 'CURLE_TELNET_OPTION_SYNTAX',
      51 => 'CURLE_PEER_FAILED_VERIFICATION',
      52 => 'CURLE_GOT_NOTHING',
      53 => 'CURLE_SSL_ENGINE_NOTFOUND',
      54 => 'CURLE_SSL_ENGINE_SETFAILED',
      55 => 'CURLE_SEND_ERROR',
      56 => 'CURLE_RECV_ERROR',
      58 => 'CURLE_SSL_CERTPROBLEM',
      59 => 'CURLE_SSL_CIPHER',
      60 => 'CURLE_SSL_CACERT',
      61 => 'CURLE_BAD_CONTENT_ENCODING',
      62 => 'CURLE_LDAP_INVALID_URL',
      63 => 'CURLE_FILESIZE_EXCEEDED',
      64 => 'CURLE_USE_SSL_FAILED',
      65 => 'CURLE_SEND_FAIL_REWIND',
      66 => 'CURLE_SSL_ENGINE_INITFAILED',
      67 => 'CURLE_LOGIN_DENIED',
      68 => 'CURLE_TFTP_NOTFOUND',
      69 => 'CURLE_TFTP_PERM',
      70 => 'CURLE_REMOTE_DISK_FULL',
      71 => 'CURLE_TFTP_ILLEGAL',
      72 => 'CURLE_TFTP_UNKNOWNID',
      73 => 'CURLE_REMOTE_FILE_EXISTS',
      74 => 'CURLE_TFTP_NOSUCHUSER',
      75 => 'CURLE_CONV_FAILED',
      76 => 'CURLE_CONV_REQD',
      77 => 'CURLE_SSL_CACERT_BADFILE',
      78 => 'CURLE_REMOTE_FILE_NOT_FOUND',
      79 => 'CURLE_SSH',
      80 => 'CURLE_SSL_SHUTDOWN_FAILED',
      81 => 'CURLE_AGAIN',
      82 => 'CURLE_SSL_CRL_BADFILE',
      83 => 'CURLE_SSL_ISSUER_ERROR',
      84 => 'CURLE_FTP_PRET_FAILED',
      85 => 'CURLE_RTSP_CSEQ_ERROR',
      86 => 'CURLE_RTSP_SESSION_ERROR',
      87 => 'CURLE_FTP_BAD_FILE_LIST',
      88 => 'CURLE_CHUNK_FAILED',
      89 => 'CURLE_NO_CONNECTION_AVAILABLE'
    );
    if (array_key_exists($n, $code)) {
      return $code[$n];
    }
    return '';
  }

  #===================================================================
}
