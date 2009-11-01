<?php
/**
 * Description of Rpc
 *
 * @author kevin
 */
class Rpc extends Base {
    public $Client;

    public function __setup() {
        $this->Client = new IXR_Client($this->_options['rpc_url']);
    }

    public function q($query, $content = null) {
        $r = $this->Client->query($query, '', $this->_options['rpc_user'],
            $this->_options['rpc_pass'], $content, false);
        if (!$r){
            return $this->err('An error occurred - %s:%s',
                $this->Client->getErrorCode(), $this->Client->getErrorMessage());
        }
        return $this->Client->getResponse();
    }
}
?>
