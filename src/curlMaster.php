<?php
/**
 * Curl Master
 *
 * @version    0.6 (2017-05-19 03:09:00 GMT)
 * @author     Peter Kahl <peter.kahl@colossalmind.com>
 * @since      2015-08-07
 * @copyright  2015-2017 Peter Kahl
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

class curlMaster {

  /**
   * Version
   * @var string
   */
  const VERSION = '0.6';

  /**
   * Filename (incl. path) of CA certificate
   * @var string
   * You may download and install on your server this Mozilla CA bundle
   * from this page:
   * <https://curl.haxx.se/docs/caextract.html>
   */
  public $ca_file;

  public $useragent;

  public $timeout_sec = 30;

  /**
   * Debug: If enabled (true), exception with details
   * will be thrown when error is encountered.
   * @var boolean
   */
  public $debug = false;

  /**
   * HTTP headers (optional)
   * @var array
   * Example ... array('Connection: Close');
   */
  public $headers;

  /**
   * The loop is used for DNS timeout.
   * @var integer
   */
  private $loop_count;

  /**
   * The loop is used for DNS timeout.
   * @var integer
   */
  private $loop_limit = 20;

  #===================================================================

  public function get_curl($url) {
    if (empty($this->loop_count)) {
      $this->loop_count = 0;
    }
    $this->loop_count++;
    #----
    if (!$this->validateUrl($url)) {
      throw new Exception('Illegal value argument url');
    }
    $ch = curl_init($url);
    if ($ch == false) {
      return false;
    }
    #----
    if (is_array($this->headers)) {
      $this->headers = array_merge($this->headers, array('Connection: Close'));
    }
    else {
      $this->headers = array('Connection: Close');
    }
    #----
    if (empty($this->useragent)) {
      $this->useragent = 'Mozilla/5.0 (curlMaster/'.self::VERSION.'; +https://github.com/peterkahl/curlMaster)';
    }
    #----
    curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
    curl_setopt($ch, CURLOPT_HTTPGET, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FORBID_REUSE, true);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $this->timeout_sec);
    curl_setopt($ch, CURLOPT_ENCODING , '');
    curl_setopt($ch, CURLOPT_HTTPHEADER, $this->headers);
    curl_setopt($ch, CURLOPT_USERAGENT, $this->useragent);
    #----
    if (preg_match('/^https:/', $url)) {
      if (empty($this->ca_file)) {
        throw new Exception('Empty property ca_file');
      }
      if (!file_exists($this->ca_file)) {
        throw new Exception('Unable to read file '.$this->ca_file);
      }
      curl_setopt($ch, CURLOPT_SSLVERSION, 6); # TLSv1.2
      curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
      curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
      curl_setopt($ch, CURLOPT_CAINFO, $this->ca_file);
    }
    #----
    $res = curl_exec($ch);
    $err = curl_errno($ch);
    #----
    if ($err == 0) { # Success
      curl_close($ch);
      return $res;
    }
    if ($err == 6 && $this->loop_count <= $this->loop_limit) { # Couldn't resolve host
      sleep(1);
      return $this->get_curl($url);
    }
    #----
    $info = curl_getinfo($ch);
    curl_close($ch);
    if (!$this->debug) {
      return false;
    }
    throw new Exception('CURL ERROR No. '.$err.'. Details are --'                              . PHP_EOL . PHP_EOL .
    str_pad('ERROR ',              22, '.', STR_PAD_RIGHT) .' '. $this->curlErrorCode($err)    . PHP_EOL .
    str_pad('Loop Count ',         22, '.', STR_PAD_RIGHT) .' '. $this->loop_count             . PHP_EOL .
    str_pad('URL ',                22, '.', STR_PAD_RIGHT) .' '. $info['url']                  . PHP_EOL .
    str_pad('HTTP Code ',          22, '.', STR_PAD_RIGHT) .' '. $info['http_code']            . PHP_EOL .
    str_pad('Connect Time ',       22, '.', STR_PAD_RIGHT) .' '. $info['connect_time']         . PHP_EOL .
    str_pad('Total Time ',         22, '.', STR_PAD_RIGHT) .' '. $info['total_time']           . PHP_EOL .
    str_pad('Name Lookup Time ',   22, '.', STR_PAD_RIGHT) .' '. $info['namelookup_time']      . PHP_EOL
    );
  }

  #===================================================================

  private function curlErrorCode($n) {
    $code = array (
      0 => 'CURLE_OK',
      1 => 'CURLE_UNSUPPORTED_PROTOCOL',
      2 => 'CURLE_FAILED_INIT',
      3 => 'CURLE_URL_MALFORMAT',
      4 => 'CURLE_NOT_BUILT_IN',
      5 => 'CURLE_COULDNT_RESOLVE_PROXY',
      6 => 'CURLE_COULDNT_RESOLVE_HOST',
      7 => 'CURLE_COULDNT_CONNECT',
      8 => 'CURLE_FTP_WEIRD_SERVER_REPLY',
      9 => 'CURLE_REMOTE_ACCESS_DENIED',
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
    throw new Exception('Invalid argument n='.$n);
  }

  #===================================================================

  private function validateUrl($url) {
    if (preg_match('#^https?://([a-zA-Z0-9]|[a-zA-Z0-9][a-zA-Z0-9\-]{0,61}[a-zA-Z0-9])(\.([a-zA-Z0-9]|[a-zA-Z0-9][a-zA-Z0-9\-]{0,61}[a-zA-Z0-9]))*(:\d{1,5})?/\S*$#', $url)) {
      return true;
    }
    return false;
  }

  #===================================================================
}