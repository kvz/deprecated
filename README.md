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

    <?php
    $ticketEmail = $this->TicketEmail->find('all', array(
        'conditions' => array(
            'unread' => 1,
            'from' => 'kevin@true.nl',
        ),
    ));
