<?php
/**
* advEmail.php
*
* Forked from geekMail to add extra features
* A standalone version of the CodeIgniter PHP Framework's Email library
*
* - Version 1.0
*
*
* This class is a standalone version of the Email library used in the CodeIgniter PHP Framework:
*
* - http://codeigniter.com/
*
*
* Most of the existing CodeIgniter documentation for the library should work, with the exception
* that the library won't be dynamically loaded from a CodeIgniter controller ("$this->load->..." and
* "$this->email->from..."):
*
* - http://codeigniter.com/user_guide/libraries/email.html
*
*
* Usage example:
* --------------
* <?php
*
*   require 'advEmail.php';
*
*   $advEmail = new advEmail();
*
*   $advEmail->setMailType('html');
*
*   $advEmail->from('noreply@mattclements.co.uk', 'Matt Clements');
*
*   $advEmail->to('matt@mattclements.co.uk');
*   //$advEmail->cc('matt@mattclements.co.uk');
*   //$advEmail->bcc('matt@mattclements.co.uk');
*
*   $advEmail->subject('Example subject');
*
*   $advEmail->message('Example message.');
*   $advEmail->set_alt_message('Text alternative email - manually set');
*
*   $advEmail->attach('/home/matt/file1.txt');
*   $advEmail->attach('/home/matt/file2.zip');
*
*   if (!$advEmail->send())
*   {
*     $errors = $advEmail->getDebugger();
*     print_r($errors);
*   }
*
* ?>
*
*
* Adapted by: Matt Clements
* matt@mattclements.co.uk
* http://www.mattclements.co.uk
*
* GeekMail developed by: Willem van Zyl
* willem@geekology.co.za
* http://www.geekology.co.za/blog/
*
*
* ---
* As with CodeIgniter itself, this code is free and Open Source, but
* EllisLab, Inc. (the creators of CodeIgniter) require that this license
* agreement be included in all adaptations:
*
* http://codeigniter.com/user_guide/license.html
* ---
*/

class advEmail {

  /**
   * Used as the User-Agent and X-Mailer headers' value.
   *
   * @var string
   */
  public $useragent = 'CodeIgniter';

  /**
   * Path to the Sendmail binary.
   *
   * @var string
   */
  public $mailpath  = '/usr/sbin/sendmail'; // Sendmail path

  /**
   * Which method to use for sending e-mails.
   *
   * @var string  'mail', 'sendmail' or 'smtp'
   */
  public $protocol  = 'mail';   // mail/sendmail/smtp

  /**
   * STMP Server host
   *
   * @var string
   */
  public $smtp_host = '';

  /**
   * SMTP Username
   *
   * @var string
   */
  public $smtp_user = '';

  /**
   * SMTP Password
   *
   * @var string
   */
  public $smtp_pass = '';

  /**
   * SMTP Server port
   *
   * @var int
   */
  public $smtp_port = 25;

  /**
   * SMTP connection timeout in seconds
   *
   * @var int
   */
  public $smtp_timeout  = 5;

  /**
   * SMTP persistent connection
   *
   * @var bool
   */
  public $smtp_keepalive  = FALSE;

  /**
   * SMTP Encryption
   *
   * @var string  empty, 'tls' or 'ssl'
   */
  public $smtp_crypto = '';

  /**
   * Whether to apply word-wrapping to the message body.
   *
   * @var bool
   */
  public $wordwrap  = TRUE;

  /**
   * Number of characters to wrap at.
   *
   * @see CI_Email::$wordwrap
   * @var int
   */
  public $wrapchars = 76;

  /**
   * Message format.
   *
   * @var string  'text' or 'html'
   */
  public $mailtype  = 'text';

  /**
   * Character set (default: utf-8)
   *
   * @var string
   */
  public $charset   = 'utf-8';

  /**
   * Multipart message
   *
   * @var string  'mixed' (in the body) or 'related' (separate)
   */
  public $multipart = 'mixed';    // "mixed" (in the body) or "related" (separate)

  /**
   * Alternative message (for HTML messages only)
   *
   * @var string
   */
  public $alt_message = '';

  /**
   * Whether to validate e-mail addresses.
   *
   * @var bool
   */
  public $validate  = FALSE;

  /**
   * X-Priority header value.
   *
   * @var int 1-5
   */
  public $priority  = 3;      // Default priority (1 - 5)

  /**
   * Newline character sequence.
   * Use "\r\n" to comply with RFC 822.
   *
   * @link  http://www.ietf.org/rfc/rfc822.txt
   * @var string  "\r\n" or "\n"
   */
  public $newline   = "\n";     // Default newline. "\r\n" or "\n" (Use "\r\n" to comply with RFC 822)

  /**
   * CRLF character sequence
   *
   * RFC 2045 specifies that for 'quoted-printable' encoding,
   * "\r\n" must be used. However, it appears that some servers
   * (even on the receiving end) don't handle it properly and
   * switching to "\n", while improper, is the only solution
   * that seems to work for all environments.
   *
   * @link  http://www.ietf.org/rfc/rfc822.txt
   * @var string
   */
  public $crlf    = "\n";

  /**
   * Whether to use Delivery Status Notification.
   *
   * @var bool
   */
  public $dsn   = FALSE;

  /**
   * Whether to send multipart alternatives.
   * Yahoo! doesn't seem to like these.
   *
   * @var bool
   */
  public $send_multipart  = TRUE;

  /**
   * Whether to send messages to BCC recipients in batches.
   *
   * @var bool
   */
  public $bcc_batch_mode  = FALSE;

  /**
   * BCC Batch max number size.
   *
   * @see CI_Email::$bcc_batch_mode
   * @var int
   */
  public $bcc_batch_size  = 200;

  // --------------------------------------------------------------------

  /**
   * Whether PHP is running in safe mode. Initialized by the class constructor.
   *
   * @var bool
   */
  protected $_safe_mode   = FALSE;

  /**
   * Subject header
   *
   * @var string
   */
  protected $_subject   = '';

  /**
   * Message body
   *
   * @var string
   */
  protected $_body    = '';

  /**
   * Final message body to be sent.
   *
   * @var string
   */
  protected $_finalbody   = '';

  /**
   * multipart/alternative boundary
   *
   * @var string
   */
  protected $_alt_boundary  = '';

  /**
   * Attachment boundary
   *
   * @var string
   */
  protected $_atc_boundary  = '';

  /**
   * Final headers to send
   *
   * @var string
   */
  protected $_header_str    = '';

  /**
   * SMTP Connection socket placeholder
   *
   * @var resource
   */
  protected $_smtp_connect  = '';

  /**
   * Mail encoding
   *
   * @var string  '8bit' or '7bit'
   */
  protected $_encoding    = '8bit';

  /**
   * Whether to perform SMTP authentication
   *
   * @var bool
   */
  protected $_smtp_auth   = FALSE;

  /**
   * Whether to send a Reply-To header
   *
   * @var bool
   */
  protected $_replyto_flag  = FALSE;

  /**
   * Debug messages
   *
   * @see CI_Email::print_debugger()
   * @var string
   */
  protected $_debug_msg   = array();

  /**
   * Recipients
   *
   * @var string[]
   */
  protected $_recipients    = array();

  /**
   * CC Recipients
   *
   * @var string[]
   */
  protected $_cc_array    = array();

  /**
   * BCC Recipients
   *
   * @var string[]
   */
  protected $_bcc_array   = array();

  /**
   * Message headers
   *
   * @var string[]
   */
  protected $_headers   = array();

  /**
   * Attachment data
   *
   * @var array
   */
  protected $_attachments   = array();

  /**
   * Valid $protocol values
   *
   * @see CI_Email::$protocol
   * @var string[]
   */
  protected $_protocols   = array('mail', 'sendmail', 'smtp');

  /**
   * Base charsets
   *
   * Character sets valid for 7-bit encoding,
   * excluding language suffix.
   *
   * @var string[]
   */
  protected $_base_charsets = array('us-ascii', 'iso-2022-');

  /**
   * Bit depths
   *
   * Valid mail encodings
   *
   * @see CI_Email::$_encoding
   * @var string[]
   */
  protected $_bit_depths    = array('7bit', '8bit');

  private $_log           = array();

  /**
   * $priority translations
   *
   * Actual values to send with the X-Priority header
   *
   * @var string[]
   */
  protected $_priorities = array(
    1 => '1 (Highest)',
    2 => '2 (High)',
    3 => '3 (Normal)',
    4 => '4 (Low)',
    5 => '5 (Lowest)'
  );

  // --------------------------------------------------------------------

  /**
   * Constructor - Sets Email Preferences
   *
   * The constructor can be passed an array of config values
   *
   * @param array $config = array()
   * @return  void
   */
  public function __construct(array $config = array())
  {
    //$this->charset = config_item('charset');

    if (count($config) > 0)
    {
      $this->initialize($config);
    }
    else
    {
      $this->_smtp_auth = ! ($this->smtp_user === '' && $this->smtp_pass === '');
    }

    $this->_safe_mode = ( ! $this->is_php('5.4') && ini_get('safe_mode'));
    $this->charset = strtoupper($this->charset);

    $this->_logMessage('debug', 'advEmail Class Initialized');
  }

    private function is_php($version)
    {
        static $_is_php;
        $version = (string) $version;

        if ( ! isset($_is_php[$version]))
        {
            $_is_php[$version] = version_compare(PHP_VERSION, $version, '>=');
        }

        return $_is_php[$version];
    }

    /**
    * Logging methods to store and retrieve messages.
    */
    private function _logMessage($type, $message)
    {
      //check that a valid log type was specified:
      switch ($type)
      {
        case 'debug': break;
        case 'error': break;
        case 'info':  break;
        default:      $type = 'debug';
      }
      //log the message:
      $this->_log[] = array('date'    => date('Y-m-d H:i:s'),
                            'type'    => $type,
                            'message' => $message
                           );
    }
    public function getLog()
    {
      return $this->_log;
    }

  // --------------------------------------------------------------------

  /**
   * Destructor - Releases Resources
   *
   * @return  void
   */
  public function __destruct()
  {
    if (is_resource($this->_smtp_connect))
    {
      $this->_send_command('quit');
    }
  }

  // --------------------------------------------------------------------

  /**
   * Initialize preferences
   *
   * @param array
   * @return  CI_Email
   */
  public function initialize($config = array())
  {
    foreach ($config as $key => $val)
    {
      if (isset($this->$key))
      {
        $method = 'set_'.$key;

        if (method_exists($this, $method))
        {
          $this->$method($val);
        }
        else
        {
          $this->$key = $val;
        }
      }
    }
    $this->clear();

    $this->_smtp_auth = ! ($this->smtp_user === '' && $this->smtp_pass === '');

    return $this;
  }

  // --------------------------------------------------------------------

  /**
   * Initialize the Email Data
   *
   * @param bool
   * @return  CI_Email
   */
  public function clear($clear_attachments = FALSE)
  {
    $this->_subject   = '';
    $this->_body    = '';
    $this->_finalbody = '';
    $this->_header_str  = '';
    $this->_replyto_flag  = FALSE;
    $this->_recipients  = array();
    $this->_cc_array  = array();
    $this->_bcc_array = array();
    $this->_headers   = array();
    $this->_debug_msg = array();

    $this->set_header('User-Agent', $this->useragent);
    $this->set_header('Date', $this->_set_date());

    if ($clear_attachments !== FALSE)
    {
      $this->_attachments = array();
    }

    return $this;
  }

  // --------------------------------------------------------------------

  /**
   * Set FROM
   *
   * @param string  $from
   * @param string  $name
   * @param string  $return_path = NULL Return-Path
   * @return  CI_Email
   */
  public function from($from, $name = '', $return_path = NULL)
  {
    if (preg_match('/\<(.*)\>/', $from, $match))
    {
      $from = $match[1];
    }

    if ($this->validate)
    {
      $this->validate_email($this->_str_to_array($from));
      if ($return_path)
      {
        $this->validate_email($this->_str_to_array($return_path));
      }
    }

    // prepare the display name
    if ($name !== '')
    {
      // only use Q encoding if there are characters that would require it
      if ( ! preg_match('/[\200-\377]/', $name))
      {
        // add slashes for non-printing characters, slashes, and double quotes, and surround it in double quotes
        $name = '"'.addcslashes($name, "\0..\37\177'\"\\").'"';
      }
      else
      {
        $name = $this->_prep_q_encoding($name);
      }
    }

    $this->set_header('From', $name.' <'.$from.'>');

    isset($return_path) OR $return_path = $from;
    $this->set_header('Return-Path', '<'.$return_path.'>');

    return $this;
  }

  // --------------------------------------------------------------------

  /**
   * Set Reply-to
   *
   * @param string
   * @param string
   * @return  CI_Email
   */
  public function reply_to($replyto, $name = '')
  {
    if (preg_match('/\<(.*)\>/', $replyto, $match))
    {
      $replyto = $match[1];
    }

    if ($this->validate)
    {
      $this->validate_email($this->_str_to_array($replyto));
    }

    if ($name === '')
    {
      $name = $replyto;
    }

    if (strpos($name, '"') !== 0)
    {
      $name = '"'.$name.'"';
    }

    $this->set_header('Reply-To', $name.' <'.$replyto.'>');
    $this->_replyto_flag = TRUE;

    return $this;
  }

  // --------------------------------------------------------------------

  /**
   * Set Recipients
   *
   * @param string
   * @return  CI_Email
   */
  public function to($to)
  {
    $to = $this->_str_to_array($to);
    $to = $this->clean_email($to);

    if ($this->validate)
    {
      $this->validate_email($to);
    }

    if ($this->_get_protocol() !== 'mail')
    {
      $this->set_header('To', implode(', ', $to));
    }

    $this->_recipients = $to;

    return $this;
  }

  // --------------------------------------------------------------------

  /**
   * Set CC
   *
   * @param string
   * @return  CI_Email
   */
  public function cc($cc)
  {
    $cc = $this->clean_email($this->_str_to_array($cc));

    if ($this->validate)
    {
      $this->validate_email($cc);
    }

    $this->set_header('Cc', implode(', ', $cc));

    if ($this->_get_protocol() === 'smtp')
    {
      $this->_cc_array = $cc;
    }

    return $this;
  }

  // --------------------------------------------------------------------

  /**
   * Set BCC
   *
   * @param string
   * @param string
   * @return  CI_Email
   */
  public function bcc($bcc, $limit = '')
  {
    if ($limit !== '' && is_numeric($limit))
    {
      $this->bcc_batch_mode = TRUE;
      $this->bcc_batch_size = $limit;
    }

    $bcc = $this->clean_email($this->_str_to_array($bcc));

    if ($this->validate)
    {
      $this->validate_email($bcc);
    }

    if ($this->_get_protocol() === 'smtp' OR ($this->bcc_batch_mode && count($bcc) > $this->bcc_batch_size))
    {
      $this->_bcc_array = $bcc;
    }
    else
    {
      $this->set_header('Bcc', implode(', ', $bcc));
    }

    return $this;
  }

  // --------------------------------------------------------------------

  /**
   * Set Email Subject
   *
   * @param string
   * @return  CI_Email
   */
  public function subject($subject)
  {
    $subject = $this->_prep_q_encoding($subject);
    $this->set_header('Subject', $subject);
    return $this;
  }

  // --------------------------------------------------------------------

  /**
   * Set Body
   *
   * @param string
   * @return  CI_Email
   */
  public function message($body)
  {
    $this->_body = rtrim(str_replace("\r", '', $body));

    /* strip slashes only if magic quotes is ON
       if we do it with magic quotes OFF, it strips real, user-inputted chars.

       NOTE: In PHP 5.4 get_magic_quotes_gpc() will always return 0 and
       it will probably not exist in future versions at all.
    */
    if ( ! $this->is_php('5.4') && get_magic_quotes_gpc())
    {
      $this->_body = stripslashes($this->_body);
    }

    return $this;
  }

  // --------------------------------------------------------------------

  /**
   * Assign file attachments
   *
   * @param string  $file Can be local path, URL or buffered content
   * @param string  $disposition = 'attachment'
   * @param string  $newname = NULL
   * @param string  $mime = ''
   * @return  CI_Email
   */
  public function attach($file, $disposition = '', $newname = NULL, $mime = '')
  {
    if ($mime === '')
    {
      if (strpos($file, '://') === FALSE && ! file_exists($file))
      {
        $this->_setErrorMessage('email_attachment_missing');
        return FALSE;
      }

      if ( ! $fp = @fopen($file, 'rb'))
      {
        $this->_setErrorMessage('email_attachment_unreadable');
        return FALSE;
      }

      $file_content = stream_get_contents($fp);
      $mime = $this->_mime_types(pathinfo($file, PATHINFO_EXTENSION));
      fclose($fp);
    }
    else
    {
      $file_content =& $file; // buffered file
    }

    $this->_attachments[] = array(
      'name'    => array($file, $newname),
      'disposition' => empty($disposition) ? 'attachment' : $disposition,  // Can also be 'inline'  Not sure if it matters
      'type'    => $mime,
      'content' => chunk_split(base64_encode($file_content))
    );

    return $this;
  }

  // --------------------------------------------------------------------

  /**
   * Set and return attachment Content-ID
   *
   * Useful for attached inline pictures
   *
   * @param string  $filename
   * @return  string
   */
  public function attachment_cid($filename)
  {
    if ($this->multipart !== 'related')
    {
      $this->multipart = 'related'; // Thunderbird need this for inline images
    }

    for ($i = 0, $c = count($this->_attachments); $i < $c; $i++)
    {
      if ($this->_attachments[$i]['name'][0] === $filename)
      {
        $this->_attachments[$i]['cid'] = uniqid(basename($this->_attachments[$i]['name'][0]).'@');
        return $this->_attachments[$i]['cid'];
      }
    }

    return FALSE;
  }

  // --------------------------------------------------------------------

  /**
   * Add a Header Item
   *
   * @param string
   * @param string
   * @return  CI_Email
   */
  public function set_header($header, $value)
  {
    $this->_headers[$header] = str_replace(array("\n", "\r"), '', $value);
    return $this;
  }

  // --------------------------------------------------------------------

  /**
   * Convert a String to an Array
   *
   * @param string
   * @return  array
   */
  protected function _str_to_array($email)
  {
    if ( ! is_array($email))
    {
      return (strpos($email, ',') !== FALSE)
        ? preg_split('/[\s,]/', $email, -1, PREG_SPLIT_NO_EMPTY)
        : (array) trim($email);
    }

    return $email;
  }

  // --------------------------------------------------------------------

  /**
   * Set Multipart Value
   *
   * @param string
   * @return  CI_Email
   */
  public function set_alt_message($str)
  {
    $this->alt_message = (string) $str;
    return $this;
  }

  // --------------------------------------------------------------------

  /**
   * Set Mailtype
   *
   * @param string
   * @return  CI_Email
   */
  public function setMailType($type = 'text')
  {
    $this->mailtype = ($type === 'html') ? 'html' : 'text';
    return $this;
  }

  // --------------------------------------------------------------------

  /**
   * Set Wordwrap
   *
   * @param bool
   * @return  CI_Email
   */
  public function set_wordwrap($wordwrap = TRUE)
  {
    $this->wordwrap = (bool) $wordwrap;
    return $this;
  }

  // --------------------------------------------------------------------

  /**
   * Set Protocol
   *
   * @param string
   * @return  CI_Email
   */
  public function set_protocol($protocol = 'mail')
  {
    $this->protocol = in_array($protocol, $this->_protocols, TRUE) ? strtolower($protocol) : 'mail';
    return $this;
  }

  // --------------------------------------------------------------------

  /**
   * Set Priority
   *
   * @param int
   * @return  CI_Email
   */
  public function set_priority($n = 3)
  {
    $this->priority = preg_match('/^[1-5]$/', $n) ? (int) $n : 3;
    return $this;
  }

  // --------------------------------------------------------------------

  /**
   * Set Newline Character
   *
   * @param string
   * @return  CI_Email
   */
  public function set_newline($newline = "\n")
  {
    $this->newline = in_array($newline, array("\n", "\r\n", "\r")) ? $newline : "\n";
    return $this;
  }

  // --------------------------------------------------------------------

  /**
   * Set CRLF
   *
   * @param string
   * @return  CI_Email
   */
  public function set_crlf($crlf = "\n")
  {
    $this->crlf = ($crlf !== "\n" && $crlf !== "\r\n" && $crlf !== "\r") ? "\n" : $crlf;
    return $this;
  }

  // --------------------------------------------------------------------

  /**
   * Set Message Boundary
   *
   * @return  void
   */
  protected function _set_boundaries()
  {
    $this->_alt_boundary = 'B_ALT_'.uniqid(''); // multipart/alternative
    $this->_atc_boundary = 'B_ATC_'.uniqid(''); // attachment boundary
  }

  // --------------------------------------------------------------------

  /**
   * Get the Message ID
   *
   * @return  string
   */
  protected function _get_message_id()
  {
    $from = str_replace(array('>', '<'), '', $this->_headers['Return-Path']);
    return '<'.uniqid('').strstr($from, '@').'>';
  }

  // --------------------------------------------------------------------

  /**
   * Get Mail Protocol
   *
   * @param bool
   * @return  mixed
   */
  protected function _get_protocol($return = TRUE)
  {
    $this->protocol = strtolower($this->protocol);
    in_array($this->protocol, $this->_protocols, TRUE) OR $this->protocol = 'mail';

    if ($return === TRUE)
    {
      return $this->protocol;
    }
  }

  // --------------------------------------------------------------------

  /**
   * Get Mail Encoding
   *
   * @param bool
   * @return  string
   */
  protected function _get_encoding($return = TRUE)
  {
    in_array($this->_encoding, $this->_bit_depths) OR $this->_encoding = '8bit';

    foreach ($this->_base_charsets as $charset)
    {
      if (strpos($charset, $this->charset) === 0)
      {
        $this->_encoding = '7bit';
      }
    }

    if ($return === TRUE)
    {
      return $this->_encoding;
    }
  }

  // --------------------------------------------------------------------

  /**
   * Get content type (text/html/attachment)
   *
   * @return  string
   */
  protected function _get_content_type()
  {
    if ($this->mailtype === 'html')
    {
      return (count($this->_attachments) === 0) ? 'html' : 'html-attach';
    }
    elseif  ($this->mailtype === 'text' && count($this->_attachments) > 0)
    {
      return 'plain-attach';
    }
    else
    {
      return 'plain';
    }
  }

  // --------------------------------------------------------------------

  /**
   * Set RFC 822 Date
   *
   * @return  string
   */
  protected function _set_date()
  {
    $timezone = date('Z');
    $operator = ($timezone[0] === '-') ? '-' : '+';
    $timezone = abs($timezone);
    $timezone = floor($timezone/3600) * 100 + ($timezone % 3600) / 60;

    return sprintf('%s %s%04d', date('D, j M Y H:i:s'), $operator, $timezone);
  }

  // --------------------------------------------------------------------

  /**
   * Mime message
   *
   * @return  string
   */
  protected function _get_mime_message()
  {
    return 'This is a multi-part message in MIME format.'.$this->newline.'Your email application may not support this format.';
  }

  // --------------------------------------------------------------------

  /**
   * Validate Email Address
   *
   * @param string
   * @return  bool
   */
  public function validate_email($email)
  {
    if ( ! is_array($email))
    {
      $this->_set_error_message('lang:email_must_be_array');
      return FALSE;
    }

    foreach ($email as $val)
    {
      if ( ! $this->valid_email($val))
      {
        $this->_set_error_message('lang:email_invalid_address', $val);
        return FALSE;
      }
    }

    return TRUE;
  }

  // --------------------------------------------------------------------

  /**
   * Email Validation
   *
   * @param string
   * @return  bool
   */
  public function valid_email($email)
  {
    if (function_exists('idn_to_ascii') && $atpos = strpos($email, '@'))
    {
      $email = substr($email, 0, ++$atpos).idn_to_ascii(substr($email, $atpos));
    }

    return (bool) filter_var($email, FILTER_VALIDATE_EMAIL);
  }

  // --------------------------------------------------------------------

  /**
   * Clean Extended Email Address: Joe Smith <joe@smith.com>
   *
   * @param string
   * @return  string
   */
  public function clean_email($email)
  {
    if ( ! is_array($email))
    {
      return preg_match('/\<(.*)\>/', $email, $match) ? $match[1] : $email;
    }

    $clean_email = array();

    foreach ($email as $addy)
    {
      $clean_email[] = preg_match('/\<(.*)\>/', $addy, $match) ? $match[1] : $addy;
    }

    return $clean_email;
  }

  // --------------------------------------------------------------------

  /**
   * Build alternative plain text message
   *
   * Provides the raw message for use in plain-text headers of
   * HTML-formatted emails.
   * If the user hasn't specified his own alternative message
   * it creates one by stripping the HTML
   *
   * @return  string
   */
  protected function _get_alt_message()
  {
    if ( ! empty($this->alt_message))
    {
      return ($this->wordwrap)
        ? $this->word_wrap($this->alt_message, 76)
        : $this->alt_message;
    }

    $body = preg_match('/\<body.*?\>(.*)\<\/body\>/si', $this->_body, $match) ? $match[1] : $this->_body;
    $body = str_replace("\t", '', preg_replace('#<!--(.*)--\>#', '', trim(strip_tags($body))));

    for ($i = 20; $i >= 3; $i--)
    {
      $body = str_replace(str_repeat("\n", $i), "\n\n", $body);
    }

    // Reduce multiple spaces
    $body = preg_replace('| +|', ' ', $body);

    return ($this->wordwrap)
      ? $this->word_wrap($body, 76)
      : $body;
  }

  // --------------------------------------------------------------------

  /**
   * Word Wrap
   *
   * @param string
   * @param int line-length limit
   * @return  string
   */
  public function word_wrap($str, $charlim = NULL)
  {
    // Set the character limit, if not already present
    if (empty($charlim))
    {
      $charlim = empty($this->wrapchars) ? 76 : $this->wrapchars;
    }

    // Standardize newlines
    if (strpos($str, "\r") !== FALSE)
    {
      $str = str_replace(array("\r\n", "\r"), "\n", $str);
    }

    // Reduce multiple spaces at end of line
    $str = preg_replace('| +\n|', "\n", $str);

    // If the current word is surrounded by {unwrap} tags we'll
    // strip the entire chunk and replace it with a marker.
    $unwrap = array();
    if (preg_match_all('|\{unwrap\}(.+?)\{/unwrap\}|s', $str, $matches))
    {
      for ($i = 0, $c = count($matches[0]); $i < $c; $i++)
      {
        $unwrap[] = $matches[1][$i];
        $str = str_replace($matches[0][$i], '{{unwrapped'.$i.'}}', $str);
      }
    }

    // Use PHP's native function to do the initial wordwrap.
    // We set the cut flag to FALSE so that any individual words that are
    // too long get left alone. In the next step we'll deal with them.
    $str = wordwrap($str, $charlim, "\n", FALSE);

    // Split the string into individual lines of text and cycle through them
    $output = '';
    foreach (explode("\n", $str) as $line)
    {
      // Is the line within the allowed character count?
      // If so we'll join it to the output and continue
      if (mb_strlen($line) <= $charlim)
      {
        $output .= $line.$this->newline;
        continue;
      }

      $temp = '';
      do
      {
        // If the over-length word is a URL we won't wrap it
        if (preg_match('!\[url.+\]|://|www\.!', $line))
        {
          break;
        }

        // Trim the word down
        $temp .= mb_substr($line, 0, $charlim - 1);
        $line = mb_substr($line, $charlim - 1);
      }
      while (mb_strlen($line) > $charlim);

      // If $temp contains data it means we had to split up an over-length
      // word into smaller chunks so we'll add it back to our current line
      if ($temp !== '')
      {
        $output .= $temp.$this->newline;
      }

      $output .= $line.$this->newline;
    }

    // Put our markers back
    if (count($unwrap) > 0)
    {
      foreach ($unwrap as $key => $val)
      {
        $output = str_replace('{{unwrapped'.$key.'}}', $val, $output);
      }
    }

    return $output;
  }

  // --------------------------------------------------------------------

  /**
   * Build final headers
   *
   * @return  string
   */
  protected function _build_headers()
  {
    $this->set_header('X-Sender', $this->clean_email($this->_headers['From']));
    $this->set_header('X-Mailer', $this->useragent);
    $this->set_header('X-Priority', $this->_priorities[$this->priority]);
    $this->set_header('Message-ID', $this->_get_message_id());
    $this->set_header('Mime-Version', '1.0');
  }

  // --------------------------------------------------------------------

  /**
   * Write Headers as a string
   *
   * @return  void
   */
  protected function _write_headers()
  {
    if ($this->protocol === 'mail')
    {
      if (isset($this->_headers['Subject']))
      {
        $this->_subject = $this->_headers['Subject'];
        unset($this->_headers['Subject']);
      }
    }

    reset($this->_headers);
    $this->_header_str = '';

    foreach ($this->_headers as $key => $val)
    {
      $val = trim($val);

      if ($val !== '')
      {
        $this->_header_str .= $key.': '.$val.$this->newline;
      }
    }

    if ($this->_get_protocol() === 'mail')
    {
      $this->_header_str = rtrim($this->_header_str);
    }
  }

  // --------------------------------------------------------------------

  /**
   * Build Final Body and attachments
   *
   * @return  bool
   */
  protected function _build_message()
  {
    if ($this->wordwrap === TRUE && $this->mailtype !== 'html')
    {
      $this->_body = $this->word_wrap($this->_body);
    }

    $this->_set_boundaries();
    $this->_write_headers();

    $hdr = ($this->_get_protocol() === 'mail') ? $this->newline : '';
    $body = '';

    switch ($this->_get_content_type())
    {
      case 'plain' :

        $hdr .= 'Content-Type: text/plain; charset='.$this->charset.$this->newline
          .'Content-Transfer-Encoding: '.$this->_get_encoding();

        if ($this->_get_protocol() === 'mail')
        {
          $this->_header_str .= $hdr;
          $this->_finalbody = $this->_body;
        }
        else
        {
          $this->_finalbody = $hdr.$this->newline.$this->newline.$this->_body;
        }

        return;

      case 'html' :

        if ($this->send_multipart === FALSE)
        {
          $hdr .= 'Content-Type: text/html; charset='.$this->charset.$this->newline
            .'Content-Transfer-Encoding: quoted-printable';
        }
        else
        {
          $hdr .= 'Content-Type: multipart/alternative; boundary="'.$this->_alt_boundary.'"';

          $body .= $this->_get_mime_message().$this->newline.$this->newline
            .'--'.$this->_alt_boundary.$this->newline

            .'Content-Type: text/plain; charset='.$this->charset.$this->newline
            .'Content-Transfer-Encoding: '.$this->_get_encoding().$this->newline.$this->newline
            .$this->_get_alt_message().$this->newline.$this->newline.'--'.$this->_alt_boundary.$this->newline

            .'Content-Type: text/html; charset='.$this->charset.$this->newline
            .'Content-Transfer-Encoding: quoted-printable'.$this->newline.$this->newline;
        }

        $this->_finalbody = $body.$this->_prep_quoted_printable($this->_body).$this->newline.$this->newline;

        if ($this->_get_protocol() === 'mail')
        {
          $this->_header_str .= $hdr;
        }
        else
        {
          $this->_finalbody = $hdr.$this->newline.$this->newline.$this->_finalbody;
        }

        if ($this->send_multipart !== FALSE)
        {
          $this->_finalbody .= '--'.$this->_alt_boundary.'--';
        }

        return;

      case 'plain-attach' :

        $hdr .= 'Content-Type: multipart/'.$this->multipart.'; boundary="'.$this->_atc_boundary.'"';

        if ($this->_get_protocol() === 'mail')
        {
          $this->_header_str .= $hdr;
        }

        $body .= $this->_get_mime_message().$this->newline
          .$this->newline
          .'--'.$this->_atc_boundary.$this->newline
          .'Content-Type: text/plain; charset='.$this->charset.$this->newline
          .'Content-Transfer-Encoding: '.$this->_get_encoding().$this->newline
          .$this->newline
          .$this->_body.$this->newline.$this->newline;

      break;
      case 'html-attach' :

        $hdr .= 'Content-Type: multipart/'.$this->multipart.'; boundary="'.$this->_atc_boundary.'"';

        if ($this->_get_protocol() === 'mail')
        {
          $this->_header_str .= $hdr;
        }

        $body .= $this->_get_mime_message().$this->newline.$this->newline
          .'--'.$this->_atc_boundary.$this->newline

          .'Content-Type: multipart/alternative; boundary="'.$this->_alt_boundary.'"'.$this->newline.$this->newline
          .'--'.$this->_alt_boundary.$this->newline

          .'Content-Type: text/plain; charset='.$this->charset.$this->newline
          .'Content-Transfer-Encoding: '.$this->_get_encoding().$this->newline.$this->newline
          .$this->_get_alt_message().$this->newline.$this->newline.'--'.$this->_alt_boundary.$this->newline

          .'Content-Type: text/html; charset='.$this->charset.$this->newline
          .'Content-Transfer-Encoding: quoted-printable'.$this->newline.$this->newline

          .$this->_prep_quoted_printable($this->_body).$this->newline.$this->newline
          .'--'.$this->_alt_boundary.'--'.$this->newline.$this->newline;

      break;
    }

    $attachment = array();
    for ($i = 0, $c = count($this->_attachments), $z = 0; $i < $c; $i++)
    {
      $filename = $this->_attachments[$i]['name'][0];
      $basename = ($this->_attachments[$i]['name'][1] === NULL)
        ? basename($filename) : $this->_attachments[$i]['name'][1];

      $attachment[$z++] = '--'.$this->_atc_boundary.$this->newline
        .'Content-type: '.$this->_attachments[$i]['type'].'; '
        .'name="'.$basename.'"'.$this->newline
        .'Content-Disposition: '.$this->_attachments[$i]['disposition'].';'.$this->newline
        .'Content-Transfer-Encoding: base64'.$this->newline
        .(empty($this->_attachments[$i]['cid']) ? '' : 'Content-ID: <'.$this->_attachments[$i]['cid'].'>'.$this->newline);

      $attachment[$z++] = $this->_attachments[$i]['content'];
    }

    $body .= implode($this->newline, $attachment).$this->newline.'--'.$this->_atc_boundary.'--';
    $this->_finalbody = ($this->_get_protocol() === 'mail')
      ? $body
      : $hdr.$this->newline.$this->newline.$body;

    return TRUE;
  }

  // --------------------------------------------------------------------

  /**
   * Prep Quoted Printable
   *
   * Prepares string for Quoted-Printable Content-Transfer-Encoding
   * Refer to RFC 2045 http://www.ietf.org/rfc/rfc2045.txt
   *
   * @param string
   * @return  string
   */
  protected function _prep_quoted_printable($str)
  {
    // We are intentionally wrapping so mail servers will encode characters
    // properly and MUAs will behave, so {unwrap} must go!
    $str = str_replace(array('{unwrap}', '{/unwrap}'), '', $str);

    // RFC 2045 specifies CRLF as "\r\n".
    // However, many developers choose to override that and violate
    // the RFC rules due to (apparently) a bug in MS Exchange,
    // which only works with "\n".
    if ($this->crlf === "\r\n")
    {
      if ($this->is_php('5.3'))
      {
        return quoted_printable_encode($str);
      }
      elseif (function_exists('imap_8bit'))
      {
        return imap_8bit($str);
      }
    }

    // Reduce multiple spaces & remove nulls
    $str = preg_replace(array('| +|', '/\x00+/'), array(' ', ''), $str);

    // Standardize newlines
    if (strpos($str, "\r") !== FALSE)
    {
      $str = str_replace(array("\r\n", "\r"), "\n", $str);
    }

    $escape = '=';
    $output = '';

    foreach (explode("\n", $str) as $line)
    {
      $length = strlen($line);
      $temp = '';

      // Loop through each character in the line to add soft-wrap
      // characters at the end of a line " =\r\n" and add the newly
      // processed line(s) to the output (see comment on $crlf class property)
      for ($i = 0; $i < $length; $i++)
      {
        // Grab the next character
        $char = $line[$i];
        $ascii = ord($char);

        // Convert spaces and tabs but only if it's the end of the line
        if ($i === ($length - 1) && ($ascii === 32 OR $ascii === 9))
        {
          $char = $escape.sprintf('%02s', dechex($ascii));
        }
        elseif ($ascii === 61) // encode = signs
        {
          $char = $escape.strtoupper(sprintf('%02s', dechex($ascii)));  // =3D
        }

        // If we're at the character limit, add the line to the output,
        // reset our temp variable, and keep on chuggin'
        if ((strlen($temp) + strlen($char)) >= 76)
        {
          $output .= $temp.$escape.$this->crlf;
          $temp = '';
        }

        // Add the character to our temporary line
        $temp .= $char;
      }

      // Add our completed line to the output
      $output .= $temp.$this->crlf;
    }

    // get rid of extra CRLF tacked onto the end
    return substr($output, 0, strlen($this->crlf) * -1);
  }

  // --------------------------------------------------------------------

  /**
   * Prep Q Encoding
   *
   * Performs "Q Encoding" on a string for use in email headers.
   * It's related but not identical to quoted-printable, so it has its
   * own method.
   *
   * @param string
   * @return  string
   */
  protected function _prep_q_encoding($str)
  {
    $str = str_replace(array("\r", "\n"), '', $str);

    if ($this->charset === 'UTF-8')
    {
      if (function_exists('mb_encode_mimeheader'))
      {
        return mb_encode_mimeheader($str, $this->charset, 'Q', $this->crlf);
      }
      elseif (function_exists('iconv'))
      {
        $output = @iconv_mime_encode('', $str,
          array(
            'scheme' => 'Q',
            'line-length' => 76,
            'input-charset' => $this->charset,
            'output-charset' => $this->charset,
            'line-break-chars' => $this->crlf
          )
        );

        // There are reports that iconv_mime_encode() might fail and return FALSE
        if ($output !== FALSE)
        {
          // iconv_mime_encode() will always put a header field name.
          // We've passed it an empty one, but it still prepends our
          // encoded string with ': ', so we need to strip it.
          return substr($output, 2);
        }

        $chars = iconv_strlen($str, 'UTF-8');
      }
    }

    // We might already have this set for UTF-8
    isset($chars) OR $chars = strlen($str);

    $output = '=?'.$this->charset.'?Q?';
    for ($i = 0, $length = strlen($output); $i < $chars; $i++)
    {
      $chr = ($this->charset === 'UTF-8' && function_exists('iconv'))
        ? '='.implode('=', str_split(strtoupper(bin2hex(iconv_substr($str, $i, 1, $this->charset))), 2))
        : '='.strtoupper(bin2hex($str[$i]));

      // RFC 2045 sets a limit of 76 characters per line.
      // We'll append ?= to the end of each line though.
      if ($length + ($l = strlen($chr)) > 74)
      {
        $output .= '?='.$this->crlf // EOL
          .' =?'.$this->charset.'?Q?'.$chr; // New line
        $length = 6 + strlen($this->charset) + $l; // Reset the length for the new line
      }
      else
      {
        $output .= $chr;
        $length += $l;
      }
    }

    // End the header
    return $output.'?=';
  }

  // --------------------------------------------------------------------

  /**
   * Send Email
   *
   * @param bool  $auto_clear = TRUE
   * @return  bool
   */
  public function send($auto_clear = TRUE)
  {
    if ( ! isset($this->_headers['From']))
    {
      $this->_set_error_message('lang:email_no_from');
      return FALSE;
    }

    if ($this->_replyto_flag === FALSE)
    {
      $this->reply_to($this->_headers['From']);
    }

    if ( ! isset($this->_recipients) && ! isset($this->_headers['To'])
      && ! isset($this->_bcc_array) && ! isset($this->_headers['Bcc'])
      && ! isset($this->_headers['Cc']))
    {
      $this->_set_error_message('lang:email_no_recipients');
      return FALSE;
    }

    $this->_build_headers();

    if ($this->bcc_batch_mode && count($this->_bcc_array) > $this->bcc_batch_size)
    {
      $result = $this->batch_bcc_send();

      if ($result && $auto_clear)
      {
        $this->clear();
      }

      return $result;
    }

    if ($this->_build_message() === FALSE)
    {
      return FALSE;
    }

    $result = $this->_spool_email();

    if ($result && $auto_clear)
    {
      $this->clear();
    }

    return $result;
  }

  // --------------------------------------------------------------------

  /**
   * Batch Bcc Send. Sends groups of BCCs in batches
   *
   * @return  void
   */
  public function batch_bcc_send()
  {
    $float = $this->bcc_batch_size - 1;
    $set = '';
    $chunk = array();

    for ($i = 0, $c = count($this->_bcc_array); $i < $c; $i++)
    {
      if (isset($this->_bcc_array[$i]))
      {
        $set .= ', '.$this->_bcc_array[$i];
      }

      if ($i === $float)
      {
        $chunk[] = substr($set, 1);
        $float += $this->bcc_batch_size;
        $set = '';
      }

      if ($i === $c-1)
      {
        $chunk[] = substr($set, 1);
      }
    }

    for ($i = 0, $c = count($chunk); $i < $c; $i++)
    {
      unset($this->_headers['Bcc']);

      $bcc = $this->clean_email($this->_str_to_array($chunk[$i]));

      if ($this->protocol !== 'smtp')
      {
        $this->set_header('Bcc', implode(', ', $bcc));
      }
      else
      {
        $this->_bcc_array = $bcc;
      }

      if ($this->_build_message() === FALSE)
      {
        return FALSE;
      }

      $this->_spool_email();
    }
  }

  // --------------------------------------------------------------------

  /**
   * Unwrap special elements
   *
   * @return  void
   */
  protected function _unwrap_specials()
  {
    $this->_finalbody = preg_replace_callback('/\{unwrap\}(.*?)\{\/unwrap\}/si', array($this, '_remove_nl_callback'), $this->_finalbody);
  }

  // --------------------------------------------------------------------

  /**
   * Strip line-breaks via callback
   *
   * @param string  $matches
   * @return  string
   */
  protected function _remove_nl_callback($matches)
  {
    if (strpos($matches[1], "\r") !== FALSE OR strpos($matches[1], "\n") !== FALSE)
    {
      $matches[1] = str_replace(array("\r\n", "\r", "\n"), '', $matches[1]);
    }

    return $matches[1];
  }

  // --------------------------------------------------------------------

  /**
   * Spool mail to the mail server
   *
   * @return  bool
   */
  protected function _spool_email()
  {
    $this->_unwrap_specials();

    $method = '_send_with_'.$this->_get_protocol();
    if ( ! $this->$method())
    {
      $this->_set_error_message('lang:email_send_failure_'.($this->_get_protocol() === 'mail' ? 'phpmail' : $this->_get_protocol()));
      return FALSE;
    }

    $this->_set_error_message('lang:email_sent', $this->_get_protocol());
    return TRUE;
  }

  // --------------------------------------------------------------------

  /**
   * Send using mail()
   *
   * @return  bool
   */
  protected function _send_with_mail()
  {
    if (is_array($this->_recipients))
    {
      $this->_recipients = implode(', ', $this->_recipients);
    }

    if ($this->_safe_mode === TRUE)
    {
      return mail($this->_recipients, $this->_subject, $this->_finalbody, $this->_header_str);
    }
    else
    {
      // most documentation of sendmail using the "-f" flag lacks a space after it, however
      // we've encountered servers that seem to require it to be in place.
      return mail($this->_recipients, $this->_subject, $this->_finalbody, $this->_header_str, '-f '.$this->clean_email($this->_headers['Return-Path']));
    }
  }

  // --------------------------------------------------------------------

  /**
   * Send using Sendmail
   *
   * @return  bool
   */
  protected function _send_with_sendmail()
  {
    // is popen() enabled?
    if ( ! function_usable('popen')
      OR FALSE === ($fp = @popen(
            $this->mailpath.' -oi -f '.$this->clean_email($this->_headers['From'])
              .' -t -r '.$this->clean_email($this->_headers['Return-Path'])
            , 'w'))
    ) // server probably has popen disabled, so nothing we can do to get a verbose error.
    {
      return FALSE;
    }

    fputs($fp, $this->_header_str);
    fputs($fp, $this->_finalbody);

    $status = pclose($fp);

    if ($status !== 0)
    {
      $this->_set_error_message('lang:email_exit_status', $status);
      $this->_set_error_message('lang:email_no_socket');
      return FALSE;
    }

    return TRUE;
  }

  // --------------------------------------------------------------------

  /**
   * Send using SMTP
   *
   * @return  bool
   */
  protected function _send_with_smtp()
  {
    if ($this->smtp_host === '')
    {
      $this->_set_error_message('lang:email_no_hostname');
      return FALSE;
    }

    if ( ! $this->_smtp_connect() OR ! $this->_smtp_authenticate())
    {
      return FALSE;
    }

    $this->_send_command('from', $this->clean_email($this->_headers['From']));

    foreach ($this->_recipients as $val)
    {
      $this->_send_command('to', $val);
    }

    if (count($this->_cc_array) > 0)
    {
      foreach ($this->_cc_array as $val)
      {
        if ($val !== '')
        {
          $this->_send_command('to', $val);
        }
      }
    }

    if (count($this->_bcc_array) > 0)
    {
      foreach ($this->_bcc_array as $val)
      {
        if ($val !== '')
        {
          $this->_send_command('to', $val);
        }
      }
    }

    $this->_send_command('data');

    // perform dot transformation on any lines that begin with a dot
    $this->_send_data($this->_header_str.preg_replace('/^\./m', '..$1', $this->_finalbody));

    $this->_send_data('.');

    $reply = $this->_get_smtp_data();

    $this->_set_error_message($reply);

    if (strpos($reply, '250') !== 0)
    {
      $this->_set_error_message('lang:email_smtp_error', $reply);
      return FALSE;
    }

    if ($this->smtp_keepalive)
    {
      $this->_send_command('reset');
    }
    else
    {
      $this->_send_command('quit');
    }

    return TRUE;
  }

  // --------------------------------------------------------------------

  /**
   * SMTP Connect
   *
   * @return  string
   */
  protected function _smtp_connect()
  {
    if (is_resource($this->_smtp_connect))
    {
      return TRUE;
    }

    $ssl = ($this->smtp_crypto === 'ssl') ? 'ssl://' : '';

    $this->_smtp_connect = fsockopen($ssl.$this->smtp_host,
              $this->smtp_port,
              $errno,
              $errstr,
              $this->smtp_timeout);

    if ( ! is_resource($this->_smtp_connect))
    {
      $this->_set_error_message('lang:email_smtp_error', $errno.' '.$errstr);
      return FALSE;
    }

    stream_set_timeout($this->_smtp_connect, $this->smtp_timeout);
    $this->_set_error_message($this->_get_smtp_data());

    if ($this->smtp_crypto === 'tls')
    {
      $this->_send_command('hello');
      $this->_send_command('starttls');

      $crypto = stream_socket_enable_crypto($this->_smtp_connect, TRUE, STREAM_CRYPTO_METHOD_TLS_CLIENT);

      if ($crypto !== TRUE)
      {
        $this->_set_error_message('lang:email_smtp_error', $this->_get_smtp_data());
        return FALSE;
      }
    }

    return $this->_send_command('hello');
  }

  // --------------------------------------------------------------------

  /**
   * Send SMTP command
   *
   * @param string
   * @param string
   * @return  string
   */
  protected function _send_command($cmd, $data = '')
  {
    switch ($cmd)
    {
      case 'hello' :

            if ($this->_smtp_auth OR $this->_get_encoding() === '8bit')
            {
              $this->_send_data('EHLO '.$this->_get_hostname());
            }
            else
            {
              $this->_send_data('HELO '.$this->_get_hostname());
            }

            $resp = 250;
      break;
      case 'starttls' :

            $this->_send_data('STARTTLS');
            $resp = 220;
      break;
      case 'from' :

            $this->_send_data('MAIL FROM:<'.$data.'>');
            $resp = 250;
      break;
      case 'to' :

            if ($this->dsn)
            {
              $this->_send_data('RCPT TO:<'.$data.'> NOTIFY=SUCCESS,DELAY,FAILURE ORCPT=rfc822;'.$data);
            }
            else
            {
              $this->_send_data('RCPT TO:<'.$data.'>');
            }

            $resp = 250;
      break;
      case 'data' :

            $this->_send_data('DATA');
            $resp = 354;
      break;
      case 'reset':

            $this->_send_data('RSET');
            $resp = 250;
      break;
      case 'quit' :

            $this->_send_data('QUIT');
            $resp = 221;
      break;
    }

    $reply = $this->_get_smtp_data();

    $this->_debug_msg[] = '<pre>'.$cmd.': '.$reply.'</pre>';

    if ((int) substr($reply, 0, 3) !== $resp)
    {
      $this->_set_error_message('lang:email_smtp_error', $reply);
      return FALSE;
    }

    if ($cmd === 'quit')
    {
      fclose($this->_smtp_connect);
    }

    return TRUE;
  }

  // --------------------------------------------------------------------

  /**
   * SMTP Authenticate
   *
   * @return  bool
   */
  protected function _smtp_authenticate()
  {
    if ( ! $this->_smtp_auth)
    {
      return TRUE;
    }

    if ($this->smtp_user === '' && $this->smtp_pass === '')
    {
      $this->_set_error_message('lang:email_no_smtp_unpw');
      return FALSE;
    }

    $this->_send_data('AUTH LOGIN');

    $reply = $this->_get_smtp_data();

    if (strpos($reply, '503') === 0)  // Already authenticated
    {
      return TRUE;
    }
    elseif (strpos($reply, '334') !== 0)
    {
      $this->_set_error_message('lang:email_failed_smtp_login', $reply);
      return FALSE;
    }

    $this->_send_data(base64_encode($this->smtp_user));

    $reply = $this->_get_smtp_data();

    if (strpos($reply, '334') !== 0)
    {
      $this->_set_error_message('lang:email_smtp_auth_un', $reply);
      return FALSE;
    }

    $this->_send_data(base64_encode($this->smtp_pass));

    $reply = $this->_get_smtp_data();

    if (strpos($reply, '235') !== 0)
    {
      $this->_set_error_message('lang:email_smtp_auth_pw', $reply);
      return FALSE;
    }

    return TRUE;
  }

  // --------------------------------------------------------------------

  /**
   * Send SMTP data
   *
   * @param string  $data
   * @return  bool
   */
  protected function _send_data($data)
  {
    $data .= $this->newline;
    for ($written = $timestamp = 0, $length = strlen($data); $written < $length; $written += $result)
    {
      if (($result = fwrite($this->_smtp_connect, substr($data, $written))) === FALSE)
      {
        break;
      }
      // See https://bugs.php.net/bug.php?id=39598 and http://php.net/manual/en/function.fwrite.php#96951
      elseif ($result === 0)
      {
        if ($timestamp === 0)
        {
          $timestamp = time();
        }
        elseif ($timestamp < (time() - $this->smtp_timeout))
        {
          $result = FALSE;
          break;
        }

        usleep(250000);
        continue;
      }
      else
      {
        $timestamp = 0;
      }
    }

    if ($result === FALSE)
    {
      $this->_set_error_message('lang:email_smtp_data_failure', $data);
      return FALSE;
    }

    return TRUE;
  }

  // --------------------------------------------------------------------

  /**
   * Get SMTP data
   *
   * @return  string
   */
  protected function _get_smtp_data()
  {
    $data = '';

    while ($str = fgets($this->_smtp_connect, 512))
    {
      $data .= $str;

      if ($str[3] === ' ')
      {
        break;
      }
    }

    return $data;
  }

  // --------------------------------------------------------------------

  /**
   * Get Hostname
   *
   * There are only two legal types of hostname - either a fully
   * qualified domain name (eg: "mail.example.com") or an IP literal
   * (eg: "[1.2.3.4]").
   *
   * @link  https://tools.ietf.org/html/rfc5321#section-2.3.5
   * @link  http://cbl.abuseat.org/namingproblems.html
   * @return  string
   */
  protected function _get_hostname()
  {
    if (isset($_SERVER['SERVER_NAME']))
    {
      return $_SERVER['SERVER_NAME'];
    }

    return isset($_SERVER['SERVER_ADDR']) ? '['.$_SERVER['SERVER_ADDR'].']' : '[127.0.0.1]';
  }

  // --------------------------------------------------------------------

  /**
   * Get Debug Message
   *
   * @param array $include  List of raw data chunks to include in the output
   *          Valid options are: 'headers', 'subject', 'body'
   * @return  string
   */
  public function print_debugger($include = array('headers', 'subject', 'body'))
  {
    $msg = '';

    if (count($this->_debug_msg) > 0)
    {
      foreach ($this->_debug_msg as $val)
      {
        $msg .= $val;
      }
    }

    // Determine which parts of our raw data needs to be printed
    $raw_data = '';
    is_array($include) OR $include = array($include);

    if (in_array('headers', $include, TRUE))
    {
      $raw_data = htmlspecialchars($this->_header_str)."\n";
    }

    if (in_array('subject', $include, TRUE))
    {
      $raw_data .= htmlspecialchars($this->_subject)."\n";
    }

    if (in_array('body', $include, TRUE))
    {
      $raw_data .= htmlspecialchars($this->_finalbody);
    }

    return $msg.($raw_data === '' ? '' : '<pre>'.$raw_data.'</pre>');
  }

  // --------------------------------------------------------------------

  /**
   * Set Message
   *
   * @param string  $msg
   * @param string  $val = ''
   * @return  void
   */
  protected function _set_error_message($msg, $val = '')
  {
    /*if (sscanf($msg, 'lang:%s', $line) !== 1 OR FALSE === ($line = $CI->lang->line($line)))
    {
      $this->_debug_msg[] = str_replace('%s', $val, $msg).'<br />';
    }
    else
    {
      $this->_debug_msg[] = str_replace('%s', $val, $line).'<br />';
    }*/
    //TODO: Replace
  }

  // --------------------------------------------------------------------

  protected function _get_mime_types()
  {
    return array(
        'hqx'   =>  array('application/mac-binhex40', 'application/mac-binhex', 'application/x-binhex40', 'application/x-mac-binhex40'),
        'cpt'   =>  'application/mac-compactpro',
        'csv'   =>  array('text/x-comma-separated-values', 'text/comma-separated-values', 'application/octet-stream', 'application/vnd.ms-excel', 'application/x-csv', 'text/x-csv', 'text/csv', 'application/csv', 'application/excel', 'application/vnd.msexcel', 'text/plain'),
        'bin'   =>  array('application/macbinary', 'application/mac-binary', 'application/octet-stream', 'application/x-binary', 'application/x-macbinary'),
        'dms'   =>  'application/octet-stream',
        'lha'   =>  'application/octet-stream',
        'lzh'   =>  'application/octet-stream',
        'exe'   =>  array('application/octet-stream', 'application/x-msdownload'),
        'class' =>  'application/octet-stream',
        'psd'   =>  array('application/x-photoshop', 'image/vnd.adobe.photoshop'),
        'so'    =>  'application/octet-stream',
        'sea'   =>  'application/octet-stream',
        'dll'   =>  'application/octet-stream',
        'oda'   =>  'application/oda',
        'pdf'   =>  array('application/pdf', 'application/force-download', 'application/x-download', 'binary/octet-stream'),
        'ai'    =>  array('application/pdf', 'application/postscript'),
        'eps'   =>  'application/postscript',
        'ps'    =>  'application/postscript',
        'smi'   =>  'application/smil',
        'smil'  =>  'application/smil',
        'mif'   =>  'application/vnd.mif',
        'xls'   =>  array('application/vnd.ms-excel', 'application/msexcel', 'application/x-msexcel', 'application/x-ms-excel', 'application/x-excel', 'application/x-dos_ms_excel', 'application/xls', 'application/x-xls', 'application/excel', 'application/download', 'application/vnd.ms-office', 'application/msword'),
        'ppt'   =>  array('application/powerpoint', 'application/vnd.ms-powerpoint', 'application/vnd.ms-office', 'application/msword'),
        'pptx'  =>  array('application/vnd.openxmlformats-officedocument.presentationml.presentation', 'application/x-zip', 'application/zip'),
        'wbxml' =>  'application/wbxml',
        'wmlc'  =>  'application/wmlc',
        'dcr'   =>  'application/x-director',
        'dir'   =>  'application/x-director',
        'dxr'   =>  'application/x-director',
        'dvi'   =>  'application/x-dvi',
        'gtar'  =>  'application/x-gtar',
        'gz'    =>  'application/x-gzip',
        'gzip'  =>  'application/x-gzip',
        'php'   =>  array('application/x-httpd-php', 'application/php', 'application/x-php', 'text/php', 'text/x-php', 'application/x-httpd-php-source'),
        'php4'  =>  'application/x-httpd-php',
        'php3'  =>  'application/x-httpd-php',
        'phtml' =>  'application/x-httpd-php',
        'phps'  =>  'application/x-httpd-php-source',
        'js'    =>  array('application/x-javascript', 'text/plain'),
        'swf'   =>  'application/x-shockwave-flash',
        'sit'   =>  'application/x-stuffit',
        'tar'   =>  'application/x-tar',
        'tgz'   =>  array('application/x-tar', 'application/x-gzip-compressed'),
        'z' =>  'application/x-compress',
        'xhtml' =>  'application/xhtml+xml',
        'xht'   =>  'application/xhtml+xml',
        'zip'   =>  array('application/x-zip', 'application/zip', 'application/x-zip-compressed', 'application/s-compressed', 'multipart/x-zip'),
        'rar'   =>  array('application/x-rar', 'application/rar', 'application/x-rar-compressed'),
        'mid'   =>  'audio/midi',
        'midi'  =>  'audio/midi',
        'mpga'  =>  'audio/mpeg',
        'mp2'   =>  'audio/mpeg',
        'mp3'   =>  array('audio/mpeg', 'audio/mpg', 'audio/mpeg3', 'audio/mp3'),
        'aif'   =>  array('audio/x-aiff', 'audio/aiff'),
        'aiff'  =>  array('audio/x-aiff', 'audio/aiff'),
        'aifc'  =>  'audio/x-aiff',
        'ram'   =>  'audio/x-pn-realaudio',
        'rm'    =>  'audio/x-pn-realaudio',
        'rpm'   =>  'audio/x-pn-realaudio-plugin',
        'ra'    =>  'audio/x-realaudio',
        'rv'    =>  'video/vnd.rn-realvideo',
        'wav'   =>  array('audio/x-wav', 'audio/wave', 'audio/wav'),
        'bmp'   =>  array('image/bmp', 'image/x-bmp', 'image/x-bitmap', 'image/x-xbitmap', 'image/x-win-bitmap', 'image/x-windows-bmp', 'image/ms-bmp', 'image/x-ms-bmp', 'application/bmp', 'application/x-bmp', 'application/x-win-bitmap'),
        'gif'   =>  'image/gif',
        'jpeg'  =>  array('image/jpeg', 'image/pjpeg'),
        'jpg'   =>  array('image/jpeg', 'image/pjpeg'),
        'jpe'   =>  array('image/jpeg', 'image/pjpeg'),
        'png'   =>  array('image/png',  'image/x-png'),
        'tiff'  =>  'image/tiff',
        'tif'   =>  'image/tiff',
        'css'   =>  array('text/css', 'text/plain'),
        'html'  =>  array('text/html', 'text/plain'),
        'htm'   =>  array('text/html', 'text/plain'),
        'shtml' =>  array('text/html', 'text/plain'),
        'txt'   =>  'text/plain',
        'text'  =>  'text/plain',
        'log'   =>  array('text/plain', 'text/x-log'),
        'rtx'   =>  'text/richtext',
        'rtf'   =>  'text/rtf',
        'xml'   =>  array('application/xml', 'text/xml', 'text/plain'),
        'xsl'   =>  array('application/xml', 'text/xsl', 'text/xml'),
        'mpeg'  =>  'video/mpeg',
        'mpg'   =>  'video/mpeg',
        'mpe'   =>  'video/mpeg',
        'qt'    =>  'video/quicktime',
        'mov'   =>  'video/quicktime',
        'avi'   =>  array('video/x-msvideo', 'video/msvideo', 'video/avi', 'application/x-troff-msvideo'),
        'movie' =>  'video/x-sgi-movie',
        'doc'   =>  array('application/msword', 'application/vnd.ms-office'),
        'docx'  =>  array('application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/zip', 'application/msword', 'application/x-zip'),
        'dot'   =>  array('application/msword', 'application/vnd.ms-office'),
        'dotx'  =>  array('application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/zip', 'application/msword'),
        'xlsx'  =>  array('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/zip', 'application/vnd.ms-excel', 'application/msword', 'application/x-zip'),
        'word'  =>  array('application/msword', 'application/octet-stream'),
        'xl'    =>  'application/excel',
        'eml'   =>  'message/rfc822',
        'json'  =>  array('application/json', 'text/json'),
        'pem'   =>  array('application/x-x509-user-cert', 'application/x-pem-file', 'application/octet-stream'),
        'p10'   =>  array('application/x-pkcs10', 'application/pkcs10'),
        'p12'   =>  'application/x-pkcs12',
        'p7a'   =>  'application/x-pkcs7-signature',
        'p7c'   =>  array('application/pkcs7-mime', 'application/x-pkcs7-mime'),
        'p7m'   =>  array('application/pkcs7-mime', 'application/x-pkcs7-mime'),
        'p7r'   =>  'application/x-pkcs7-certreqresp',
        'p7s'   =>  'application/pkcs7-signature',
        'crt'   =>  array('application/x-x509-ca-cert', 'application/x-x509-user-cert', 'application/pkix-cert'),
        'crl'   =>  array('application/pkix-crl', 'application/pkcs-crl'),
        'der'   =>  'application/x-x509-ca-cert',
        'kdb'   =>  'application/octet-stream',
        'pgp'   =>  'application/pgp',
        'gpg'   =>  'application/gpg-keys',
        'sst'   =>  'application/octet-stream',
        'csr'   =>  'application/octet-stream',
        'rsa'   =>  'application/x-pkcs7',
        'cer'   =>  array('application/pkix-cert', 'application/x-x509-ca-cert'),
        '3g2'   =>  'video/3gpp2',
        '3gp'   =>  array('video/3gp', 'video/3gpp'),
        'mp4'   =>  'video/mp4',
        'm4a'   =>  'audio/x-m4a',
        'f4v'   =>  'video/mp4',
        'webm'  =>  'video/webm',
        'aac'   =>  'audio/x-acc',
        'm4u'   =>  'application/vnd.mpegurl',
        'm3u'   =>  'text/plain',
        'xspf'  =>  'application/xspf+xml',
        'vlc'   =>  'application/videolan',
        'wmv'   =>  array('video/x-ms-wmv', 'video/x-ms-asf'),
        'au'    =>  'audio/x-au',
        'ac3'   =>  'audio/ac3',
        'flac'  =>  'audio/x-flac',
        'ogg'   =>  'audio/ogg',
        'kmz'   =>  array('application/vnd.google-earth.kmz', 'application/zip', 'application/x-zip'),
        'kml'   =>  array('application/vnd.google-earth.kml+xml', 'application/xml', 'text/xml'),
        'ics'   =>  'text/calendar',
        'ical'  =>  'text/calendar',
        'zsh'   =>  'text/x-scriptzsh',
        '7zip'  =>  array('application/x-compressed', 'application/x-zip-compressed', 'application/zip', 'multipart/x-zip'),
        'cdr'   =>  array('application/cdr', 'application/coreldraw', 'application/x-cdr', 'application/x-coreldraw', 'image/cdr', 'image/x-cdr', 'zz-application/zz-winassoc-cdr'),
        'wma'   =>  array('audio/x-ms-wma', 'video/x-ms-asf'),
        'jar'   =>  array('application/java-archive', 'application/x-java-application', 'application/x-jar', 'application/x-compressed'),
        'svg'   =>  array('image/svg+xml', 'application/xml', 'text/xml'),
        'vcf'   =>  'text/x-vcard',
        'srt'   =>  array('text/srt', 'text/plain'),
        'vtt'   =>  array('text/vtt', 'text/plain'),
        'ico'   =>  array('image/x-icon', 'image/x-ico', 'image/vnd.microsoft.icon')
    );
  }

  /**
   * Mime Types
   *
   * @param string
   * @return  string
   */
  protected function _mime_types($ext = '')
  {
    $ext = strtolower($ext);

    $mimes = $this->_get_mime_types();

    if (isset($mimes[$ext]))
    {
      return is_array($mimes[$ext])
        ? current($mimes[$ext])
        : $mimes[$ext];
    }

    return 'application/x-unknown-content-type';
  }

}
