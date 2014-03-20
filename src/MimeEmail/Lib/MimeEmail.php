<?php
/**
 * This file is part of the CarteBlanche PHP framework
 * (c) Pierre Cassat and contributors
 * 
 * Sources <http://github.com/php-carteblanche/bundle-mailer>
 *
 * License Apache-2.0
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace MimeEmail\Lib;

use \Library\Converter\Html2Text;

/*
PHP Mail function :
bool mail ( string $to , string $subject , string $message [, string $additional_headers [, string $additional_parameters ]] )
*/

/* EXAMPLE */
/*
ini_set('display_errors','1'); error_reporting(E_ALL ^ E_NOTICE);
$txt_message = "Hello dude !\n\nMy line 1\nLine 2\nLine 3";
$txt_message_iso = "Hello dude !\n\nMy line 1 with special chars : é à\nLine 2\nLine 3";
$html_message = "Hello dude !\n\n<br /><table><tr><td><b>Line 1</b></td><td>Line 2</td><td>Line 3</tr></table>";
$html_message_iso = "Hello dude !\n\n<br /><table><tr><td><b>My line 1 with special chars : é à</b></td><td>Line 2</td><td>Line 3</tr></table>";
$test_adress_1 = 'piwi@ateliers-pierrot.fr';
$test_name_1 = 'Piero';
$test_name_1_iso = 'Piéro';
$test_adress_2 = 'pierre.cassat@gmail.com';
$test_name_2 = 'Piero2';
$test_adress_3 = 'pierrecassat@free.fr';
$test_name_3 = 'PieroFree';
$from = 'the_tester@system.fr';
$from_name = 'mister Tester';
$file = 'inc/lorem_ipsum.txt';
$subject = 'The subject of this test message ...';
$subject_iso = 'The subject of this test message with special chars : é à ...';

// Initialisation of the mail
	// standard simple mail
//$mail = new MimeEmail($from_name, $from, $test_adress_1, $subject, $txt_message);
	// mail with ISO chars
$mail = new MimeEmail($from_name, $from, $test_adress_1, $subject_iso, $txt_message_iso);

// Adding some emails
$mail->setCc($test_adress_2, 'yo');
$mail->setCc($test_adress_3, $test_name_3);

// This will send an error : this is not an email adress
//$mail->setCc('yuiuy');

// Loading HTML content
	// Simple HTML content
//$mail->setHtml($html_message);
	// ISO chars HTML content
$mail->setHtml($html_message_iso);

// Attah a file
$mail->setAttachment($file);

// Send mails with feedback
$ok = $mail->send(1);

// See the object
echo "<pre>"; var_export($mail); echo "</pre>";
exit;
*/

/**
 */
class MimeEmail
{

// --------------------
// Constants
// --------------------
	const MM_CLASSNAME = "MimeMail class";
	const MM_CLASSVERSION = "0.1";
	const BOUNDARY_OPENER = "--";
	const BOUNDARY_CLOSER = "--";
	const BOUNDARY_PREFIX = "=_MimeMailClass_";

// --------------------
// Statics
// --------------------
	static $sep_line = "\n";
	static $sep_address = ", ";
	static $sep_header_adds = "; ";

// --------------------
// Variables
// --------------------
	private $errors = array();
	private $infos = array();
	private $sent_messages = 0;

    protected $from;
    protected $to;
    protected $cc;
    protected $bcc;
    protected $attachment;
	protected $header;
	protected $subject;
	protected $text;
	protected $html;
	protected $id;
	protected $wordwrap_limit = 78;

	protected $registry = array(
		'headers' => array(
			'MIME-Version'=>'1.0',
			'Return-Path'=>false,
			'Reply-To'=>'no-reply@mail.class',
			'X-Sender'=>false,
			'X-Priority'=>'3',
		),
		'options' => array(
//			'charset' => 'iso-8859-1',
			'charset' => 'utf-8',
		),
		'sender_mailer' => "mime@mail.class",
		'boundary' => '',
		'boundary_ctt' => '',
		'message_type' => '',
	);

	/**
	 * Set to TRUE to not send the mails but test its contents
	 */
	protected $dry_run = false;

	/**
	 * Set to TRUE to not send the mails but write them in a file
	 */
	protected $spool = false;

	/**
	 * The directory where to create spooled mails files
	 */
	protected $spool_dir;

	/**
	 * Redefine the sendmail executable path
	 */
	protected $sendmail_path = null;

	/**
	 * Set of clearing values
	 */
	static $clearings = array(
    	'from'=>'',
    	'to'=>array(),
    	'cc'=>array(),
    	'bcc'=>array(),
    	'attachment'=>array(),
		'header'=>'',
		'subject'=>'',
		'text'=>'',
		'html'=>'',
		'id'=>null
	);

// --------------------
// Construction
// --------------------

	/**
	 * Construction of a MimeEmail object
	 *
	 * @return object $this for chaining use
	 */
    public function __construct($from_name = '', $from_mail = null, $to = null, $subject = null, $message = null) 
    {
    	$this->_init();
		if (!is_null($from_mail) && $this->isEmail($from_mail)) self::setFrom($from_mail, $from_name);
		if (!is_null($to)) self::setTo($to);
		if (!is_null($subject)) self::setSubject($subject);
		if (!is_null($message)) self::setText($message);
		return $this;
    }

	/**
	 * First initialization of the object
	 */
	protected function _init()
	{
        if (defined('PHP_EOL')) {
            self::$sep_line = PHP_EOL;
        } else {
	        self::$sep_line = (strpos(PHP_OS, 'WIN') === false) ? "\n" : "\r\n";
        }
		foreach (self::$clearings as $var=>$val) {
			$this->$var = $val;
		}
    	if (is_null($this->id)) {
    		$this->id = uniqid(time());
    	}
		$this->setRegistry('X-Mailer', "PHP ".PHP_VERSION." - ".self::MM_CLASSNAME." ".self::MM_CLASSVERSION, 'headers');
        $this->setRegistry('boundary', $this->makeBoundary());
        $this->setRegistry('boundary_ctt', $this->makeBoundary());
	}

	/**
	 * Initialization before sending messages
	 */
	protected function _presendInit()
	{
		// Fournir si possible un Message-Id: conforme au RFC1036,
		// sinon SpamAssassin denoncera un MSGID_FROM_MTA_HEADER
		$sender_mailer = $this->getRegistry('sender_mailer');
		if ($this->isEmail($sender_mailer)) {
			preg_match('/(@\S+)/', $sender_mailer, $domain);
			$this->setRegistry('Message-Id', '<'.time().'_'.rand().'_'.md5($this->text).$domain[1].'>', 'headers');
		} 
		else $this->errors[] = "!! - Error in 'sender' address ($sender_mailer) - the message will probably be considered as a spam.";
		// Le type du message
		if (strlen($this->text) && strlen($this->html) && count($this->attachment))
			$this->setRegistry('message_type', 'multipart/mixed');
		elseif (strlen($this->text) && strlen($this->html) && !count($this->attachment)) {
			$this->setRegistry('message_type', 'multipart/alternative');
	        $this->setRegistry('boundary_ctt', $this->getRegistry('boundary'));
		}
		elseif (strlen($this->text) && !strlen($this->html) && !count($this->attachment))
			$this->setRegistry('message_type', 'text/plain');

		if (!empty($this->sendmail_path)) {
			ini_set('sendmail_path', $this->sendmail_path);
		}
	}

	/**
	 * Get the errors
	 *
	 * @param bool $echoable Do we have to return a string to echo ? (FALSE by default)
	 * @return misc The errors stack as an array by default, a string to display if $echoable=true
	 */
	public function getErrors($echoable = false)
	{
		if (true===$echoable) {
			return join("\n<br />", $this->errors);
		} else {
			return $this->errors;
		}
	}

	/**
	 * Get the informations
	 *
	 * @param bool $echoable Do we have to return a string to echo ? (FALSE by default)
	 * @return misc The errors stack as an array by default, a string to display if $echoable=true
	 */
	public function getInfos($echoable = false)
	{
		if (true===$echoable) {
			return join("\n<br />", $this->infos);
		} else {
			return $this->infos;
		}
	}

	/**
	 * Set a registry entry
	 *
	 * @param string $var The entry name
	 * @param misc $val The entry value
	 * @param string $section A sub-section to search the entry
	 * @return null
	 */
	protected function setRegistry($var = null, $val = null, $section = false)
	{
		if (is_null($var)) return;
		if ($section) {
			if (!isset($this->registry[$section]))
				$this->registry[$section] = array();
			$this->registry[$section][$var] = $val;
		} else {
			$this->registry[$var] = $val;
		}
	}

	/**
	 * Get a registry entry
	 *
	 * @param string $var The entry name
	 * @param string $section A sub-section to search the entry
	 * @param misc $default The value returned if nothing had been found
	 * @return misc The value of the entry if found, $default otherwise
	 */
	protected function getRegistry($var = null, $section = false, $default = false)
	{
		if (is_null($var)) return;
		if ($section && isset($this->registry[$section])) {
			if (isset($this->registry[$section][$var])) {
				return $this->registry[$section][$var];
			} else {
				return $default;
			}
		}
		if (isset($this->registry[$var])) {
			return $this->registry[$var];
		}
		return $default;
	}

// --------------------
// Getters/Setters/Clearers
// --------------------

	/**
	 * Global getter
	 *
	 * @param string $what The name of the object variable to get
	 * @return misc The variable actual value
	 */
    public function get($what) 
    {
    	if (property_exists($this, $what)) {
    		return $this->$what;
    	}
    	return null;
    }
    
	/**
	 * Global variable clearer
	 *
	 * @param string $what The name of the object variable to clear
	 * @return object $this for chaining use
	 */
	public function clear($what)
	{
		$what = strtolower($what);
    	if (array_key_exists($what, self::$clearings)) {
			$this->$what = self::$clearings[$what];
		}
		return $this;
	}
	
	/**
	 * Make a dry run of the class : no mail will be sent
	 *
	 * @param bool $dry Activate dry run or not
	 * @return object $this for chaining use
	 */
    public function setDryRun($dry = true) 
    {
    	$this->dry_run = $dry;
    	return $this;
    }
    
	/**
	 * Activate emails spooling
	 *
	 * @param bool $spool Activate spool or not
	 * @return object $this for chaining use
	 */
    public function setSpool($spool = true) 
    {
    	$this->spool = $spool;
    	return $this;
    }
    
	/**
	 * Set the spooled mails directory
	 *
	 * @param string $dir The directory where to create spooled mails files
	 * @return object $this for chaining use
	 */
    public function setSpoolDir($dir) 
    {
    	if (!file_exists($dir)) {
    		if (!mkdir($dir)) {
    			throw new \Exception("Can not create emails spooling directory [$dir]!");
    		}
    	}
    	$this->spool_dir = $dir;
    	return $this;
    }
    
	/**
	 * Set From field
	 *
	 * @param string/array $mail The email address to add, or an array of name=>email pairs
	 * @param string/bool $name The name to show for the email address if there is just one
	 * @param bool $reply Set the "reply-to" to the same address ? (default is TRUE)
	 * @return object $this for chaining use
	 * @see \App\MimeEmail::checkPeopleArgs
	 */
    public function setFrom($mail = '', $name = null, $reply = true) 
    {
    	$mail = trim($mail);
    	if (strlen($mail) && $this->isEmail($mail)) {
    		$this->from = !empty($name) ? array($name=>$mail) : array($mail);
			$this->setRegistry('Return-Path', '<'.$mail.'>', 'headers');
			$this->setRegistry('X-Sender', $mail, 'headers');
			if ($reply) {
				$this->setReplyTo($mail,$name);
			}
    	}
    	return $this;
    }

	/**
	 * Set To field
	 *
	 * @param string/array $mail The email address to add, or an array of name=>email pairs
	 * @param string/bool $name The name to show for the email address if there is just one
	 * @return object $this for chaining use
	 * @see \App\MimeEmail::checkPeopleArgs
	 */
    public function setTo($mail = '', $name = null) 
    {
    	$this->to = $this->deduplicate(
    		array_merge($this->to, call_user_func_array(array($this, 'checkPeopleArgs'), func_get_args()))
    	);
    	return $this;
    }

	/**
	 * Set Cc field
	 *
	 * @param string/array $mail The email address to add, or an array of name=>email pairs
	 * @param string/bool $name The name to show for the email address if there is just one
	 * @return object $this for chaining use
	 * @see \App\MimeEmail::checkPeopleArgs
	 */
    public function setCc($mail = '', $name = null) 
    {
    	$this->cc = $this->deduplicate(
    		array_merge($this->cc, call_user_func_array(array($this, 'checkPeopleArgs'), func_get_args()))
    	);
    	return $this;
    }

	/**
	 * Set Bcc field
	 *
	 * @param string/array $mail The email address to add, or an array of name=>email pairs
	 * @param string/bool $name The name to show for the email address if there is just one
	 * @return object $this for chaining use
	 * @see \App\MimeEmail::checkPeopleArgs
	 */
    public function setBcc($mail = '', $name = null) 
    {
    	$this->bcc = $this->deduplicate(
    		array_merge($this->bcc, call_user_func_array(array($this, 'checkPeopleArgs'), func_get_args()))
    	);
    	return $this;
    }

	/**
	 * Set mail file attachment
	 *
	 * @param string/array $subject The file or files to attach
	 * @param bool $clear Clear a setted content first ? (default is to append a content)
	 * @return object $this for chaining use
	 */
    public function setAttachment($file = '', $clear = false) 
    {
    	if (true===$clear) {
    		$this->clear('text');
    	}
    	if (is_array($file)) {
    		foreach($file as $_f) {
	    		if (file_exists($_f)) {
					$this->attachment[] = $_f;
				}
    		}
    	} else {
	    	if (file_exists($file)) {
				$this->attachment[] = $file;
			}
		}
    	return $this;
    }

	/**
	 * Set mail object
	 *
	 * @param string $subject The subject content
	 * @param bool $clear Clear a setted content first ? (default is to append a content)
	 * @return object $this for chaining use
	 */
    public function setSubject($subject = '', $clear = false) 
    {
    	if (true===$clear) {
    		$this->clear('subject');
    	}
    	$this->subject = $subject;
    	return $this;
    }

	/**
	 * Set plain text version
	 * If $text='auto', the text version will be generated from the HTML content
	 *
	 * @param string $text The plain text content or keyword 'auto' to auto-generate it from the HTML content
	 * @param bool $clear Clear a setted content first ? (default is to append a content)
	 * @return object $this for chaining use
	 */
    public function setText($text = '', $clear = false)
    {
    	if (true===$clear) {
    		$this->clear('text');
    	}
    	if ('auto'==$text) {
    		if (!empty($this->html)) {
    			$html_content = preg_replace("/.*<body[^>]*>|<\/body>.*/si", "", $this->html);
			    $this->text .= $this->formatText( $this->html2text($html_content) );
    		}
    	} else {
		    $this->text .= $this->formatText($text);
    	}
    	return $this;
    }

	/**
	 * Set HTML version
	 *
	 * @param string $html The HTML content
	 * @param bool $clear Clear a setted content first ? (default is to append a content)
	 * @return object $this for chaining use
	 */
    public function setHtml($html = '', $clear = false) 
    {
    	if (true===$clear) {
    		$this->clear('text');
    	}
		$this->html .= $this->formatText($html, 'ascii');
    	return $this;
    }

	/**
	 * Set Reply-To header field
	 *
	 * @param string/array $mail The email address to add, or an array of name=>email pairs
	 * @param string/bool $name The name to show for the email address if there is just one
	 * @return object $this for chaining use
	 */
    public function setReplyTo($mail = '', $name = null) 
    {
    	if (strlen($mail) && $this->isEmail($mail)) {
    		if (!empty($name)) {
    			$_m = $this->mailTagger($mail, $name);
    		} else {
    			$_m = $mail;
    		}
			$this->setRegistry('Reply-To', $_m, 'headers');
    	}
    	return $this;
    }

	/**
	 * Set Errors-To header field
	 *
	 * @param string/array $mail The email address to add, or an array of name=>email pairs
	 * @param string/bool $name The name to show for the email address if there is just one
	 * @return object $this for chaining use
	 */
    public function setErrorsTo($mail = '', $name = null) 
    {
    	if (strlen($mail) && $this->isEmail($mail)) {
    		if (!empty($name)) {
    			$_m = $this->mailTagger($mail, $name);
    		} else {
    			$_m = $mail;
    		}
			$this->setRegistry('Errors-To', $_m, 'headers');
    	}
    	return $this;
    }

	/**
	 * Set Disposition-Notification-To header field
	 *
	 * @param string/array $mail The email address to add, or an array of name=>email pairs
	 * @param string/bool $name The name to show for the email address if there is just one
	 * @return object $this for chaining use
	 */
    public function setDispositionNotificationTo($mail = '', $name = null) 
    {
    	if (strlen($mail) && $this->isEmail($mail)) {
    		if (!empty($name)) {
    			$_m = $this->mailTagger($mail, $name);
    		} else {
    			$_m = $mail;
    		}
			$this->setRegistry('Disposition-Notification-To', $_m, 'headers');
    	}
    	return $this;
    }

	/**
	 * Set Return-Receipt-To header field
	 *
	 * @param string/array $mail The email address to add, or an array of name=>email pairs
	 * @param string/bool $name The name to show for the email address if there is just one
	 * @return object $this for chaining use
	 */
    public function setReturnReceiptTo($mail = '', $name = null) 
    {
    	if (strlen($mail) && $this->isEmail($mail)) {
    		if (!empty($name)) {
    			$_m = $this->mailTagger($mail, $name);
    		} else {
    			$_m = $mail;
    		}
			$this->setRegistry('Return-Receipt-To', $_m, 'headers');
    	}
    	return $this;
    }

// --------------------
// Sending
// --------------------

	/**
	 * Messages sender
	 *
	 * @param bool $return_info Do we have to return an information about sending ?
	 * @return misc Object for method chaining by default, information string if $return_info=true
	 */
	public function send($return_info = false)
	{
		$this->_presendInit();
		$sending_errors=0;

		// From header
		$from = array();
		if (is_array($this->from) && count($this->from)>0) {
			while (is_null($this->from)==true) {
				foreach($this->from as $n=>$m) {
					$from = array($n=>$m);
				}
			}
			$this->header .= $this->listAddresses($this->from, 'from');
		}
		if (count($from)==0) {
			$this->errors[] = 'No sender setted!';
		}

        // To header
        if (count($this->to)>0) {
        	$this->header .= $this->listAddresses($this->to, 'to');
        }

        // CC header
        if (count($this->cc)>0) {
        	$this->header .= $this->listAddresses($this->cc, 'cc');
        }

        // BCC header
        if (count($this->bcc)>0) {
        	$this->header .= $this->listAddresses($this->bcc, 'bcc');
        }

        // Headers
        foreach($this->registry['headers'] as $entry=>$v_entry) {
        	if (isset($v_entry)) {
        		$this->header .= $this->headerTagger($entry,$v_entry).self::$sep_line;
        	}
        }
        $bound = 0;

		// Mail type
		$type = $this->getRegistry('message_type');
        if (!is_null($type) && $type!='text/plain') {
        	$bound = 1;
		    $this->header .= $this->headerTagger("Content-Type",$type, 
		    	array('boundary'=>$this->getRegistry('boundary'))).self::$sep_line;
	    	$this->header .= "This is a multi-part message in MIME format.".self::$sep_line;
		    if ($type == 'multipart/mixed') {
				$this->header .= self::$sep_line.self::BOUNDARY_OPENER.$this->getRegistry('boundary').self::$sep_line;
				$this->header .= $this->headerTagger("Content-Type","multipart/alternative", 
					array('boundary'=>$this->getRegistry('boundary_ctt'))).self::$sep_line;
			}
		}

	    // Text content
	    if (strlen($this->text)/* && !strlen($this->html)*/) {
	        if ($bound)  {
	        	$this->header .= self::$sep_line.self::BOUNDARY_OPENER.$this->getRegistry('boundary_ctt').self::$sep_line;
			    //ne prend pas les css en compte
//			    $this->header .= $this->headerTagger("Content-Transfer-Encoding", "7bit").self::$sep_line;
			    $this->header .= $this->headerTagger("Content-Transfer-Encoding", "8bit").self::$sep_line;
			    $this->header .= $this->headerTagger("Content-Type", "text/plain", 
			    	array('charset'=>$this->getRegistry('charset','options'))).self::$sep_line;
			}
    	    if (strlen($this->html)) {
        	    $this->header .= self::$sep_line.$this->text;
        	} else {
        	    $this->header .= trim($this->text, self::$sep_line);
        	}
        }

	    // HTML content
	    if (strlen($this->html)) {
	        if ($bound) {
	        	$this->header .= self::$sep_line.self::$sep_line.self::BOUNDARY_OPENER
	        		.$this->getRegistry('boundary_ctt').self::$sep_line;
	        }
		    // prend les css en compte
//		    $this->header .= $this->headerTagger("Content-Transfer-Encoding", "7bit").self::$sep_line;
		    $this->header .= $this->headerTagger("Content-Transfer-Encoding", "8bit").self::$sep_line;
//		    $this->header .= $this->headerTagger("Content-Transfer-Encoding", "quoted-printable").self::$sep_line;
		    $this->header .= $this->headerTagger("Content-Type", "text/html", 
		    	array('charset'=>$this->getRegistry('charset','options'))).self::$sep_line;
    	    $this->header .= self::$sep_line.trim($this->html, self::$sep_line);
        }
        if ($bound) {
        	$this->header .= self::$sep_line.self::BOUNDARY_OPENER
        		.$this->getRegistry('boundary_ctt').self::BOUNDARY_CLOSER.self::$sep_line;
        }

        // Attachments
        /* @todo what is max ? */
        $max = 10;
        if (count($this->attachment)>0) {
            for ($i=0;$i<$max;$i++) {
                if (isset($this->attachment[$i])) {
                    $file = fread(fopen($this->attachment[$i], "r"), filesize($this->attachment[$i]));
                    $filename = basename($this->attachment[$i]);
                    $this->header .= self::$sep_line.self::BOUNDARY_OPENER.$this->getRegistry('boundary').self::$sep_line;
                    $this->header .= $this->headerTagger("Content-Type",self::getMimeType($filename), 
                        array('name'=>$filename,'charset'=>$this->getRegistry('charset','options'))).self::$sep_line;
                    $this->header .= $this->headerTagger("Content-Transfer-Encoding","base64").self::$sep_line;
                    $this->header .= $this->headerTagger("Content-Disposition",'attachment', 
                        array('filename'=>$filename)).self::$sep_line;
                    $this->header .= $this->headerTagger("Content-Description",$filename).self::$sep_line;
                    $this->header .= self::$sep_line.chunk_split(base64_encode($file));
                    $file = $filename = "";
                }
            }
		    $this->header .= self::$sep_line.self::BOUNDARY_OPENER.$this->getRegistry('boundary').self::BOUNDARY_CLOSER.self::$sep_line;
        }

        // Then we send one by one
        if (false===$this->dry_run) {
			foreach($this->to as $set) {
				foreach($set as $name=>$mail) {
					if (true===$this->spool) {
						if (true===$this->spoolMessage($mail,$this->subject,'',$this->header)) {
							$this->infos[] = "OK - Spooling message to send to '$mail' ...";
						} else {
							$this->errors[] = "!! - Error while spooling message to send to '$mail' ...";
						}
					} else {
						if (false===mail($mail,$this->subject,'',$this->header)) {
							$this->errors[] = "!! - The message can not be sent to : '$mail' ...";
							$sending_errors++;
						} else {
							$this->infos[] = "OK - Message sent to '$mail' ...";
							$this->sent_messages++;
						}
					}
				}
			}
		} else {
	        $this->infos[] = "DryRun : no mail will be sent";
		}

		// errors ? infos ?
        if ($sending_errors>0) {
        	$msg = "Error - The message(s) can not been sent ... Check errors pile!";
        	$this->infos[] = $msg;
        	$this->errors[] = $msg;
        } else {
	        $msg = "OK - The message(s) have been sent ...";
    	    $this->infos[] = $msg;
        }

		// return
	    if (true===$return_info) {
	    	return $msg;
	    }
        return $this;
    }

	/**
	 * Messages spooler : prepare the whole content and write it in a file
	 *
	 * @param bool $return_info Do we have to return an information about sending ?
	 * @return misc Object for method chaining by default, information string if $return_info=true
	 */
	public function spool($return_info = false)
	{
		$this->setSpool(true);
		return $this->send($return_info);
	}
	
// --------------------
// RFC 2822 builder
// --------------------

	/**
	 * Build a person string compliant to RFC2822
	 *
	 * @param string $mail The person's email address
	 * @param string $name The person's name if so
	 * @return string The generated tag
	 */
	protected function mailTagger($mail = '', $name = null)
	{
		return( (!is_int($name) ? "\"".$name."\" <" : '').$mail.(!is_int($name) ? ">" : '') );
	}

	/**
	 * Build a list of name=>email pairs compliant to RFC2822
	 *
	 * @param array $list A list of name=>email pairs
	 * @param string $type The type of the field
	 * @return string The generated list
	 */
	protected function listAddresses($list = array(), $type = 'to')
	{
		$str = ucfirst( strtolower($type) ).': ';
		foreach($list as $name=>$mail) {
			if (is_string($mail)) {
				$str .= $this->mailTagger($mail,$name).self::$sep_address;
			} elseif (is_array($mail)) {
				foreach($mail as $subname=>$submail) {
					$str .= $this->mailTagger($submail,$subname).self::$sep_address;
				}
			}
		}
		return(trim($str, self::$sep_address).self::$sep_line);
    }

	/**
	 * Build a mail header tag compliant to RFC2822
	 *
	 * @param string $name The name of the tag
	 * @param string $value The value of the tag
	 * @param array $adds A variable=>value pairs to add to the tag string
	 * @return string The generated header tag string
	 */
	protected function headerTagger($name = '', $value = '', $adds = array())
	{
		$str = $name.': '.$value;
		if (count($adds)) {
			foreach($adds as $n=>$v) {
				$str .= self::$sep_header_adds.($n=='boundary' ? "\n\t" : '').$n."=\"".$v."\"";
			}
		}
		return(trim($str, self::$sep_header_adds));
    }

// --------------------
// Utilities
// --------------------

	protected function spoolMessage($to = null, $subject = null, $message = null, $additional_headers = null, $additional_parameters = null)
	{
		$c = new \Lib\Cache;
		return $c->cacheFile( rtrim($this->spool_dir, '/').'/'.$this->id, serialize($this), false );
	}

	/**
	 * Search the MIME type of a file
	 *
	 * @param string $filename The filename to check
	 * @return string The associated MIME type
	 */
	protected function getMimeType($filename = '')
	{
		$ext = strtolower(substr($filename, strrpos($filename, '.')));
		switch ($ext) {
			case '.jpeg': case '.jpg': $mimetype = 'image/jpeg'; break;
			case '.gif': $mimetype = 'image/gif'; break;
			case '.png': $mimetype = 'image/png'; break;
			case '.txt': $mimetype = 'text/plain'; break;
			case '.html': case '.htm': $mimetype = 'text/html'; break;
			case '.zip': $mimetype = 'application/x-zip-compressed'; break;
			default: $mimetype = 'application/octet-stream';
		}
		return $mimetype;
	}

	/**
	 * Build a boundary value
	 *
	 * @return string The generated boundary
	 */
	protected function makeBoundary()
	{
        return self::BOUNDARY_PREFIX.md5(uniqid(time())).'.'.$this->id;
	}

	/**
	 * Format a text with a special encoding
	 *
	 * @param string $txt The text to format
	 * @param string $type The type of the encoding : 'plain' or 'ascii'
	 * @param bool $spaces Replace all spaces with underscores or not (default is FALSE)
	 * @return string The transformed text
	 */
	protected function formatText($txt = '', $type = 'plain', $spaces = false)
	{
		switch($type) {
			case 'ascii' :
				$_txt = '';
				if ($spaces==true) $txt = str_replace(' ', '_', $txt);
				for($i=0; $i<strlen($txt);$i++) $_txt .= $this->charAscii($txt[$i]);
				$txt = $_txt;
				break;
			default : break;
		}

		$formated='';		
		foreach(explode("\n", $txt) as $_line) {
			$_line = trim($_line);
			if (strlen($_line)>$this->wordwrap_limit) {
			    $_line = wordwrap($_line, $this->wordwrap_limit, self::$sep_line);
			}
			if (strlen($_line)) $formated .= $_line.self::$sep_line;
		}
	    return $formated;
	}

	/**
	 * Make a basic substitution in the object body
	 *
	 * @param string $search The string to search
	 * @param string $replace The string to use for replacement
	 * @return string The generated body
	 */
    protected function substitution($search, $replace)
    {
		$this->body = str_replace($search, $replace, $this->body);
		return $this->body;
	}

	/**
	 * Converts HTML to plain text
	 *
	 * @param string $str The HTML content to transform
	 * @return string The associated plain text version
	 */
	protected function html2text($str) 
	{
		return Html2Text::convert($str);
	}

	/**
	 * De-duplicate a set of name=>email pairs to let each email just once
	 *
	 * @param array $array The array to de-duplicate
	 * @return array The de-duplicated array
	 */
	protected function deduplicate($array)
	{
		if (empty($array)) return $array;
		$known=array();
		foreach($array as $_index=>$entry) {
			if (is_array($entry)) {
				foreach($entry as $i=>$_email) {
					if (!in_array($_email, $known)) {
						$known[] = $_email;
					} else {
						unset($array[$_index]);
					}
				}
			} elseif (is_string($entry)) {
				if (!in_array($entry, $known)) {
					$known[] = $entry;
				} else {
					unset($array[$_index]);
				}
			}
		}
		return $array;
	}

	/**
	 * Clean and build a set of name=>email pairs :
	 * ( 'my@email.address', 'my name' )
	 * ( array( 'my name'=>'my@email.address' ) )
	 * ( array( 'my name'=>'my@email.address', 'another name'=>'another@email.address' ) )
	 * ( array( 'my name'=>'my@email.address', 'another@email.address' ) )
	 */	
	protected function checkPeopleArgs()
	{
		$args = func_get_args();
		if (empty($args)) return array();

		// 1 only email
		if (count($args)==1 && is_string($args[0]) && $this->isEmail($args[0])) {
			return array( $args[0] );
		}

		// 2 args and 2nd is not an email
		if (
			count($args)==2 && 
			(isset($args[0]) && true===$this->isEmail($args[0])) && 
			(isset($args[1]) && false===$this->isEmail($args[1]))
		) {
			return array( array( $args[1]=>$args[0] ) );
		}

		// a set of name=>email pairs
		if (count($args)==1) $args = $args[0];
		$result=array();
		foreach($args as $name=>$email) {
			if (is_string($name) && true===$this->isEmail($email)) {
				$result[] = array( $name=>$email );
			} elseif (is_numeric($name) && true===$this->isEmail($email)) {
				$result[] = $email;
			}
		}		
		return $result;
	}

	/**
	 * Returns the ASCII equivalent of a character
	 *
	 * @param string $char The character to test
	 * @return string The ASCII valid character
	 */
	protected function charAscii($char)
	{
		if ( $this->isAscii($char) ) return $char;
		$char = htmlentities($char);
		return $char;
	}

	/**
	 * ASCII validator
	 *
	 * @param string $str The content to test
	 * @return bool TRUE if some ASCII characters seems to be in $string
	 */
	protected function isAscii($string) 
	{
		return !strlen(preg_replace(',[\x09\x0A\x0D\x20-\x7E],sS','', $string));
	}

	/**
	 * Check if an email address is valid
	 *
	 * @param string $str The email address to check
	 * @return bool TRUE if it seems to be an email address
	 */
	protected function isEmail($str = '')
	{
		$v = new \Validator\EmailValidator();
		return $v->validate( $str );
	}

}

// Endfile