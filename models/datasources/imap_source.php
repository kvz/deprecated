<?php
/**
 * Get emails in your app with cake like finds.
 *
 * Copyright (c) 2010 Carl Sutton ( dogmatic69 )
 * Copyright (c) 2011 Kevin van Zonneveld ( kvz )
 *
 * @filesource
 * @copyright Copyright (c) 2010 Carl Sutton ( dogmatic69 )
 * @copyright Copyright (c) 2011 Kevin van Zonneveld ( kvz )
 * @link http://www.infinitas-cms.org
 * @link https://github.com/kvz/cakephp-emails-plugin
 * @package libs
 * @subpackage libs.models.datasources.reader
 * @license http://www.opensource.org/licenses/mit-license.php The MIT License
 * @since 0.9a
 *
 * @author dogmatic69
 * @author kvz
 *
 * Modifications since 0.8a (when code was stripped from Infinitas):
 *   https://github.com/kvz/cakephp-emails-plugin/compare/10767bee59dd425ced5b97ae9604acf7f3c0d27a...master
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 */
class ImapSource extends DataSource {
    protected $_isConnected     = false;
    protected $_connectionString = null;
    protected $_connectionType   = '';
    
    protected $_defaultConfigs = array(
        'global' => array(
            'username' => false,
            'password' => false,
            'email' => false,
            'server' => 'localhost',
            'type' => 'imap',
            'ssl' => false,
            'retry' => 3,
            'error_handler' => 'php',
            'auto_mark_as' => array('seen'),
        ),
        'imap' => array(
            'port' => 143,
        ),
        'pop3' => array(
            'port' => 110,
        ),
    );

    public $marks = array(
        '\Seen',
        '\Answered',
        '\Flagged',
        '\Deleted',
        '\Draft',
    );

    public $config = array();
    public $driver = null;

    /**
     * Default array of field list for imap mailbox.
     *
     * @var array
     */
    protected $_schema = array(
        'id' => array('type' => 'string', 'default' => NULL, 'length' => 255,),
        'message_id' => array('type' => 'string', 'default' => NULL, 'length' => 255,),
        'email_number' => array('type' => 'integer', 'default' => NULL, 'length' => 15, 'key' => 'primary',),
        'to' => array('type' => 'string', 'default' => NULL, 'length' => 255,),
        'from' => array('type' => 'string', 'default' => NULL, 'length' => 255,),
        'reply_to' => array('type' => 'string', 'default' => NULL, 'length' => 255,),
        'sender' => array('type' => 'string', 'default' => NULL, 'length' => 255,),
        'subject' => array('type' => 'string', 'default' => NULL, 'length' => 255,),
        'body' => array('type' => 'text', 'default' => NULL,),
        'plainmsg' => array('type' => 'text', 'default' => NULL,),
        'slug' => array('type' => 'string', 'default' => NULL, 'length' => 255,),
        'size' => array('type' => 'string', 'default' => NULL, 'length' => 255,),
        'recent' => array('type' => 'boolean', 'default' => NULL, 'length' => 1,),
        'unread' => array('type' => 'boolean', 'default' => NULL, 'length' => 1,),
        'flagged' => array('type' => 'boolean', 'default' => NULL, 'length' => 1,),
        'answered' => array('type' => 'boolean', 'default' => NULL, 'length' => 1,),
        'draft' => array('type' => 'boolean', 'default' => NULL, 'length' => 1,),
        'deleted' => array('type' => 'boolean', 'default' => NULL, 'length' => 1,),
        'thread_count' => array('type' => 'integer', 'default' => NULL, 'length' => 15, 'key' => 'primary',),
        'attachments' => array('type' => 'text', 'default' => NULL,),
        'in_reply_to' => array('type' => 'string', 'default' => NULL, 'length' => 255,),
        'reference' => array('type' => 'string', 'default' => NULL, 'length' => 255,),
        'new' => array('type' => 'boolean', 'default' => NULL, 'length' => 1,),
        'created' => array('type' => 'datetime', 'default' => NULL,),
    );

    public $columns = array(
        'primary_key' => array('name' => 'NOT NULL AUTO_INCREMENT'),
        'string' => array('name' => 'varchar', 'limit' => '255'),
        'text' => array('name' => 'text'),
        'integer' => array('name' => 'int', 'limit' => '11', 'formatter' => 'intval'),
        'float' => array('name' => 'float', 'formatter' => 'floatval'),
        'datetime' => array('name' => 'datetime', 'format' => 'Y-m-d H:i:s', 'formatter' => 'date'),
        'timestamp' => array('name' => 'timestamp', 'format' => 'Y-m-d H:i:s', 'formatter' => 'date'),
        'time' => array('name' => 'time', 'format' => 'H:i:s', 'formatter' => 'date'),
        'date' => array('name' => 'date', 'format' => 'Y-m-d', 'formatter' => 'date'),
        'binary' => array('name' => 'blob'),
        'boolean' => array('name' => 'tinyint', 'limit' => '1')
    );


    /**
     * __construct()
     *
     * @param mixed $config
     */
    function __construct ($config) {
        parent::__construct($config);

        if (!isset($config['type'])) {
            $type = $this->_defaultConfigs['global']['type'];
        } else {
            $type = $config['type'];
        }
        $newConfig = array_merge($this->_defaultConfigs['global'], $this->_defaultConfigs[$type], $this->config);
        $newConfig['email'] = !empty($newConfig['email']) ? $newConfig['email'] : $newConfig['username'];

        $this->config = $newConfig;
    }

    /**
     * describe the data
     *
     * @param mixed $Model
     * @return array the shcema of the model
     */
    public function describe ($Model) {
        return $this->_schema;
    }

    /**
     * listSources
     *
     * list the sources???
     *
     * @return array sources
     */
    public function listSources () {
        return array('listSources');
    }

    /**
     * Returns a query condition, or null if it wasn't found
     *
     * @param object $Model
     * @param array  $query
     * @param string $field
     * 
     * @return mixed or null
     */
    protected function _cond ($Model, $query, $field) {
        $keys = array(
            '`' . $Model->alias . '`.`' . $field . '`',
            $Model->alias . '.' . $field,
            $field,
        );

        foreach ($keys as $key) {
            if (null !== ($val = @$query['conditions'][$key])) {
                return $val;
            }
        }

        return null;
    }

    /**
     * Tranform search criteria from CakePHP -> Imap
     * Does AND, not OR
     *
     * Supported:
     *  FROM "string" - match messages with "string" in the From: field
     *  SEEN - match messages that have been read (the \\SEEN flag is set)
     *  UNSEEN - match messages which have not been read yet
     *
     * @todo: 
     *  A string, delimited by spaces, in which the following keywords are allowed. Any multi-word arguments (e.g. FROM "joey smith") must be quoted.
     *  ALL - return all messages matching the rest of the criteria
     *  ANSWERED - match messages with the \\ANSWERED flag set
     *  BCC "string" - match messages with "string" in the Bcc: field
     *  BEFORE "date" - match messages with Date: before "date"
     *  BODY "string" - match messages with "string" in the body of the message
     *  CC "string" - match messages with "string" in the Cc: field
     *  DELETED - match deleted messages
     *  FLAGGED - match messages with the \\FLAGGED (sometimes referred to as Important or Urgent) flag set
     *  KEYWORD "string" - match messages with "string" as a keyword
     *  NEW - match new messages
     *  OLD - match old messages
     *  ON "date" - match messages with Date: matching "date"
     *  RECENT - match messages with the \\RECENT flag set
     *  SINCE "date" - match messages with Date: after "date"
     *  SUBJECT "string" - match messages with "string" in the Subject:
     *  TEXT "string" - match messages with text "string"
     *  TO "string" - match messages with "string" in the To:
     *  UNANSWERED - match messages that have not been answered
     *  UNDELETED - match messages that are not deleted
     *  UNFLAGGED - match messages that are not flagged
     *  UNKEYWORD "string" - match messages that do not have the keyword "string"
     *
     * @param object $Model
     * @param array  $query
     *
     * @return array
     */
    protected function _makeSearch ($Model, $query) {
        $searchCriteria = array();

        // Special case. When somebody specifies primaryKey(s),
        // We don't have to do an actual search
        if (($id = $this->_cond($Model, $query, $Model->primaryKey))) {
            return $id;
        }

        // Normal search parameters
        if (($val = $this->_cond($Model, $query, 'unread'))) {
            $searchCriteria[] = $val ? 'UNSEEN' : 'SEEN';
        }
        if (($val = $this->_cond($Model, $query, 'from'))) {
            $searchCriteria[] = 'FROM "' . $val . '"';
        }

        return $searchCriteria;
    }

    /**
     * Tranform order criteria from CakePHP -> Imap
     *
     * For now always sorts on date descending.
     * @todo: Support the following sort parameters:
     *  SORTDATE - message Date
     *  SORTARRIVAL - arrival date
     *  SORTFROM - mailbox in first From address
     *  SORTSUBJECT - message subject
     *  SORTTO - mailbox in first To address
     *  SORTCC - mailbox in first cc address
     *  SORTSIZE - size of message in octets
     *
     * @param object $Model
     * @param array  $query
     * 
     * @return array
     */
    protected function _makeOrder($Model, $query) {

        // Tranform order criteria
        $orderReverse  = 1;
        $orderCriteria = SORTDATE;

        return array($orderReverse, $orderCriteria);
    }

    /**
     * read data
     *
     * this is the main method that reads data from the datasource and
     * formats it according to the request from the model.
     *
     * @param mixed $model the model that is requesting data
     * @param mixed $query the qurey that was sent
     *
     * @return the data requested by the model
     */
    public function read ($Model, $query) {
        if (!$this->connect($Model, $query)) {
            return $this->err($Model, 'Cannot connect to server');
        }

        $searchCriteria = $this->_makeSearch($Model, $query);

        if (is_numeric($searchCriteria) || Set::numeric($searchCriteria)) {
            // We already know the id, or list of ids
            $result = $searchCriteria;
            if (!is_array($result)) {
                $result = array($result);
            }
        } else {
            // Perform Search & Order. Returns list of ids
            list($orderReverse, $orderCriteria) = $this->_makeOrder($Model, $query);
            $result = imap_sort(
                $this->Stream,
                $orderCriteria,
                $orderReverse,
                0,
                join(' ', $searchCriteria)
            );
        }

        // Nothing was found
        if ($result === false) {
            return array();
        }

        // Trim resulting ids based on pagination / limitation
        if (@$query['start'] && @$query['end']) {
            $result = array_slice($result, @$query['start'], @$query['end'] - @$query['start']);
        } elseif (@$query['limit']) {
            $result = array_slice($result, @$query['start'] ? @$query['start'] : 0, @$query['limit']);
        } elseif ($Model->findQueryType === 'first') {
            $result = array_slice($result, 0, 1);
        }

        // Format output depending on findQueryType
        if ($Model->findQueryType === 'list') {
            return $result;
        } else if ($Model->findQueryType === 'count') {
            return array(
                array(
                    $Model->alias => array(
                        'count' => count($result),
                    ),
                ),
            );
        } else if ($Model->findQueryType === 'all' || $Model->findQueryType === 'first') {
            $mails = array();
            foreach ($result as $id) {
                if (($mail = $this->_getFormattedMail($Model, $id))) {
                    $mails[] = $mail;
                }
            }
            return $mails;
        }

        return $this->err(
            'Unknown find type %s for query %s',
            $Model->findQueryType,
            $query
        );
    }

    /**
     * no clue
     * @param <type> $Model
     * @param <type> $func
     * @param <type> $params
     * @return <type>
     */
    public function calculate ($Model, $func, $params = array()) {
        $params = (array) $params;
        switch (strtolower($func)) {
            case 'count':
                return 'count';
                break;
        }
    }

    /**
     * connect to the mail server
     */
    public function connect ($Model, $query) {
        if ($this->_isConnected) {
            return true;
        }

        $this->_connectionType = $this->config['type'];

        switch ($this->config['type']) {
            case 'imap':
                $this->_connectionString = sprintf(
                    '{%s:%s%s%s}',
                    $this->config['server'],
                    $this->config['port'],
                    @$this->config['ssl'] ? '/ssl' : '',
                    @$this->config['connect'] ? '/' . @$this->config['connect'] : ''
                );
                break;

            case 'pop3':
                $this->_connectionString = sprintf(
                    '{%s:%s/pop3%s%s}',
                    $this->config['server'],
                    $this->config['port'],
                    @$this->config['ssl'] ? '/ssl' : '',
                    @$this->config['connect'] ? '/' . @$this->config['connect'] : ''
                );
                break;
        }

        try {
            $this->thread = null;
            $retries = 0;
            while (($retries++) < $this->config['retry'] && !$this->thread) {
                $this->Stream = imap_open($this->_connectionString, $this->config['username'], $this->config['password']);
                $this->thread = @imap_thread($this->Stream);
            }

            if (!$this->thread) {
                return $this->err(
                    'Unable to get imap_thread after %s retries. %s',
                    $retries,
                    imap_last_error()
                );
            }
        } catch (Exception $Exception) {
            return $this->err(
                'Unable to get imap_thread after %s retries. %s',
                 $retries,
                $Exception->getMessage() . ' ' . imap_last_error()
            );
        }

        return $this->_isConnected = true;
    }

    public function name ($data) {
        return $data;
    }

    public function sensible ($arguments) {
        if (is_object($arguments)) {
            return get_class($arguments);
        }
        if (!is_array($arguments)) {
            if (!is_numeric($arguments) && !is_bool($arguments)) {
                $arguments = "'".$arguments."'";
            }
            return $arguments;
        }
        $arr = array();
        foreach($arguments as $key=>$val) {
            if (is_array($val)) {
                $val = json_encode($val);
            } elseif (!is_numeric($val) && !is_bool($val)) {
                $val = "'".$val."'";
            }

            if (strlen($val) > 33) {
                $val = substr($val, 0, 30) . '...';
            }
            
            $arr[] = $key.': '.$val;
        }
        return join(', ', $arr);
    }

    public function err ($Model, $format, $arg1 = null, $arg2 = null, $arg3 = null) {
        $arguments = func_get_args();
        $Model     = array_shift($arguments);
        $format    = array_shift($arguments);

        $str = $format;
        if (count($arguments)) {
            foreach($arguments as $k => $v) {
                $arguments[$k] = $this->sensible($v);
            }
            $str = vsprintf($str, $arguments);
        }

        $this->error = $str;
        $Model->onError();
        
        if ($this->config['error_handler'] === 'php') {
            trigger_error($str, E_USER_ERROR);
        }

        return false;
    }
    
    public function lastError () {
        if (($lastError = $this->error)) {
            return $lastError;
        }
        if (($lastError = imap_last_error())) {
            $this->error = imap_errors();
            return $lastError;
        }
        return false;
    }

    /**
     * get the basic details like sender and reciver with flags like attatchments etc
     *
     * @param int $msg_number the number of the message
     * @return array empty on error/nothing or array of formatted details
     */
    protected function _getFormattedMail ($Model, $msg_number) {
        $Mail      = imap_headerinfo($this->Stream, $msg_number);
        if (empty($Mail->message_id)) {
            return $this->err(
                $Model,
                'Unable to find mail with message number: %s',
                $msg_number
            );
        }
        $Structure = imap_fetchstructure($this->Stream, $Mail->Msgno);

        $toName      = isset($Mail->to[0]->personal) ? $Mail->to[0]->personal : $Mail->to[0]->mailbox;
        $fromName    = isset($Mail->from[0]->personal) ? $Mail->from[0]->personal : $Mail->from[0]->mailbox;
        $replyToName = isset($Mail->reply_to[0]->personal) ? $Mail->reply_to[0]->personal : $Mail->reply_to[0]->mailbox;

        if (isset($Mail->sender)) {
            $senderName = isset($Mail->sender[0]->personal) ? $Mail->sender[0]->personal : $Mail->sender[0]->mailbox;
        } else {
            $senderName          = $fromName;
            $Mail->sender        = $Mail->from;
            $Mail->senderaddress = $Mail->fromaddress;
        }


        $return[$Model->alias] = array(
            'id' => $this->_getId($Mail->Msgno),
            'message_id' => $Mail->message_id,
            'email_number' => $Mail->Msgno,
            'to' => printf(
                '"%s" <%s>',
                $toName,
                $Mail->toaddress
            ),
            'from' => sprintf(
                '"%s" <%s>',
                $fromName,
                sprintf('%s@%s', $Mail->from[0]->mailbox, $Mail->from[0]->host)
            ),
            'reply_to' => sprintf(
                '"%s" <%s>',
                $replyToName,
                sprintf('%s@%s', $Mail->reply_to[0]->mailbox, $Mail->reply_to[0]->host)
            ),
            'sender' => sprintf(
                '"%s" <%s>',
                $replyToName,
                sprintf('%s@%s', $Mail->sender[0]->mailbox, $Mail->sender[0]->host)
            ),
            'subject' => htmlspecialchars($Mail->subject),
            'body' => $this->_getPart($msg_number, 'TEXT/HTML', $Structure),
            'plainmsg' => $this->_getPart($msg_number, 'TEXT/PLAIN', $Structure),
            'slug' => Inflector::slug($Mail->subject, '-'),
            'size' => $Mail->Size,
            'recent' => $Mail->Recent,
            'unread' => (int)(bool)trim($Mail->Unseen),
            'flagged' => (int)(bool)trim($Mail->Flagged),
            'answered' => $Mail->Answered,
            'draft' => $Mail->Draft,
            'deleted' => $Mail->Deleted,
            'thread_count' => $this->_getThreadCount($Mail),
            'attachments' => json_encode($this->_attachment($Mail->Msgno, $Structure)),
            'in_reply_to' => isset($Mail->in_reply_to) ? $Mail->in_reply_to : false,
            'reference' => isset($Mail->references) ? $Mail->references : false,
            'new' => !isset($Mail->in_reply_to) ? true : false,
            'created' => $Mail->date
        );

        foreach ($this->marks as $mark) {
            if (!in_array($mark, $this->config['auto_mark_as'])) {
                if ($mark === '\Seen') {
                    // imap_fetchbody() should be flagging it as "seen" already.
                    // but we can undo it:
                    if (!imap_clearflag_full($this->Stream, $msg_number, $mark)) {
                        $this->err('Unable to unmark %s as %s', $msg_number, $mark);
                    }
                }
            } else {
                if (!imap_setflag_full($this->Stream, $msg_number, $mark)) {
                    $this->err('Unable to mark %s as %s', $msg_number, $mark);
                }
            }
        }

        return $return;
    }

    /**
     * Get any attachments for the current message, images, documents etc
     *
     * @param <type> $structure
     * @param <type> $msg_number
     * @return <type>
     */
    protected function _getAttachments ($structure, $msg_number) {
        $attachments = array();
        if (isset($structure->parts) && count($structure->parts)) {
            for ($i = 0; $i < count($structure->parts); $i++) {
                $attachment = array(
                    'message_id' => $msg_number,
                    'is_attachment' => false,
                    'filename' => '',
                    'mime_type' => '',
                    'type' => '',
                    'name' => '',
                    'size' => 0,
                    'attachment' => ''
                );

                if ($structure->parts[$i]->ifdparameters) {
                    foreach ($structure->parts[$i]->dparameters as $object) {
                        if (strtolower($object->attribute) == 'filename') {
                            $attachment['is_attachment'] = true;
                            $attachment['filename'] = $object->value;
                        }
                    }
                }

                if ($structure->parts[$i]->ifparameters) {
                    foreach ($structure->parts[$i]->parameters as $object) {
                        if (strtolower($object->attribute) == 'name') {
                            $attachment['is_attachment'] = true;
                            $attachment['name'] = $object->value;
                        }
                    }
                }
                if ($attachment['is_attachment']) {
                    $attachment['attachment'] = imap_fetchbody($this->Stream, $msg_number, ($i+1));
                    if ($structure->parts[$i]->encoding == 3) { // 3 = BASE64
                        $attachment['format'] = 'base64';
                    } elseif ($structure->parts[$i]->encoding == 4) { // 4 = QUOTED-PRINTABLE
                        $attachment['attachment'] = quoted_printable_decode($attachment['attachment']);
                        //$attachment['format'] = 'base64';
                    }

                    $attachment['type']      = strtolower($structure->parts[$i]->subtype);
                    $attachment['mime_type'] = $this->_getMimeType($structure->parts[$i]);
                    $attachment['size']      = $structure->parts[$i]->bytes;

                    $attachments[] = $attachment;
                }
            }
        }

        return $attachments;
    }




    /**
     * get a numeric id for use in the code
     *
     * @param string $uuid in the format <.*@.*> from the email
     *
     * @return mixed on imap its the unique id (int) and for others its a base64_encoded string
     */
    protected function _getId ($uuid, $id = null) {
        if (is_numeric($uuid)) {
            return $uuid;
        }

        if ($this->_connectionType === 'pop3') {
            if (!$id) {
                return $this->err('Cant translate this pop3 id: %s to a number', $uuid);
            }
        }

        return imap_uid($this->Stream, $uuid);
    }

    /**
     * used to check / get the attachements in an email.
     *
     * @param object $Structure the structure of the email
     * @param bool $count count them (true), or get them (false)
     *
     * @return mixed, int for check (number of attachements) / array of attachements
     */
    protected function _attachment ($message_id, $Structure, $count = true) {
        $has = 0;
        $attachments = array();
        if (isset($Structure->parts)) {
            foreach ($Structure->parts as $partOfPart) {
                if ($count) {
                    $has += $this->_attachment($message_id, $partOfPart, $count) == true ? 1 : 0;
                } else {
                    $attachment = $this->_attachment($message_id, $partOfPart, $count);
                    if (!empty($attachment)) {
                        $attachments[] = $attachment;
                    }
                }
            }
        } else {
            if (isset($Structure->disposition)) {
                if (strtolower($Structure->disposition) == 'attachment') {
                    if ($count) {
                        return true;
                    } else {
                        return array(
                            'type' => $Structure->type,
                            'subtype' => $Structure->subtype,
                            'file' => $Structure->dparameters[0]->value,
                            'size' => $Structure->bytes
                        );
                    }
                }
            }
        }

        if ($count) {
            return (int)$has;
        }

        return $attachments;
    }

    protected function _getMimeType ($Structure) {
        $primary_mime_type = array('TEXT', 'MULTIPART', 'MESSAGE', 'APPLICATION', 'AUDIO', 'IMAGE', 'VIDEO', 'OTHER');
        if ($Structure->subtype) {
            return $primary_mime_type[(int) $Structure->type] . '/' . $Structure->subtype;
        }

        return 'TEXT/PLAIN';
    }

    protected function _getPart ($msg_number, $mime_type, $Structure = null, $part_number = false) {
        $prefix = null;
        if (!$Structure) {
            return false;
        }

        if ($mime_type == $this->_getMimeType($Structure)) {
            $part_number = $part_number > 0 ? $part_number : 1;

            return imap_fetchbody($this->Stream, $msg_number, $part_number);
        }

        /* multipart */
        if ($Structure->type == 1) {
            foreach ($Structure->parts as $index => $SubStructure) {
                if ($part_number) {
                    $prefix = $part_number . '.';
                }

                $data = $this->_getPart($msg_number, $mime_type, $SubStructure, $prefix . ($index + 1));
                if ($data) {
                    return quoted_printable_decode($data);
                }
            }
        }
    }

    /**
     * Figure out how many emails there are in the thread for this mail.
     *
     * @param object $Mail the imap header of the mail
     * @return int the number of mails in the thred
     */
    protected function _getThreadCount ($Mail) {
        if (isset($Mail->reference) || isset($Mail->in_reply_to)) {
            return '?';
        }

        return 0;
    }
}