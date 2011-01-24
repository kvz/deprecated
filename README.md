Imap datasource for CakePHP. Ripped from Infinitas. 
[Changes since then](https://github.com/kvz/cakephp-emails-plugin/compare/10767bee59dd425ced5b97ae9604acf7f3c0d27a...master)

Install
============

 - Unpack / Clone / Copy so that my files are at `app/plugins/emails/*`

Config
========

 - Edit your `database.php` file like so:

    <?php
    class DATABASE_CONFIG {
        // ... your normal database config here ...

        // Imap email connection
        public $emailTicket = array(
            'datasource' => 'Emails.Imap',
            'server' => 'imap.example.com',
            'connect' => 'imap/tls/novalidate-cert',
            'username' => 'tickets',
            'password' => 'xxxxxxxxxx',
            'port' => '143',
            'ssl' => true,
            'encoding' => 'UTF-8',
            'error_handler' => 'php',
            'auto_mark_as' => array(
                '\Seen',
                // '\Answered',
                // '\Flagged',
                // '\Deleted',
                // '\Draft',
            ),
        );
    }


Implement
===========

    <?php
    class TicketEmail extends AppModel {
        // Important:
        public $useDbConfig = 'emailTicket';
        public $useTable    = false;

        // Whatever:
        public $displayField = 'subject';
        public $limit        = 10;

        // Semi-important:
        // You want to use the datasource schema, and still be able to set
        // $useTable to false. So we override Cake's schema with that exception:
        function schema ($field = false) {
            if (!is_array($this->_schema) || $field === true) {
                $db =& ConnectionManager::getDataSource($this->useDbConfig);
                $db->cacheSources = ($this->cacheSources && $db->cacheSources);
                $this->_schema = $db->describe($this, $field);
            }
            if (is_string($field)) {
                if (isset($this->_schema[$field])) {
                    return $this->_schema[$field];
                } else {
                    return null;
                }
            }
            return $this->_schema;
        }
    }


Integrate
===========

Here are a couple of supported examples:

    <?php
    $ticketEmails = $this->TicketEmail->find('all', array(
        'conditions' => array(
            'answered' => 0,
            'deleted' => 0,
            'draft' => 0,
            'flagged' => 0,
            'recent' => 0,
            'seen' => 0,

            'from' => 'kevin@true.nl',
        ),
    ));
    print_r(compact('ticketEmails'));

    $ticketEmail = $this->TicketEmail->find('first', array(
        'conditions' => array(
            'id' => 21879,
        ),
    ));
    print_r(compact('ticketEmail'));

    $this->TicketEmail->id = 21878;
    $subject = $this->TicketEmail->field('subject');
    print(compact('subject'));
    

