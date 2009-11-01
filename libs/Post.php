<?php
/**
 * Description of Post
 *
 * @author kevin
 */
class Post extends Base {
    public $Rpc;
    public $_data;
    public $_customFields;
    
    public function data($post = null) {
        if (is_array($post)) {
            $this->_data = $post;

            foreach ($this->_data['custom_fields'] as $i => $fieldData) {
                $this->_customFields[$fieldData['key']] = $fieldData['value'];
            }
        }
        return $this->_data;
    }

    public function save() {
        if (empty($this->_data['postid'])) {
            return $this->Rpc->q('metaWeblog.newPost', $this->_data);
        } else {
            // Update
            return $this->err('Update not implemented yet');
        }
    }

    public function date($format = 'Y-m-d H:i:s') {
        $dc = $this->_data['dateCreated'];
        $time = mktime(
            $dc->hour,
            $dc->minute,
            $dc->second,
            $dc->month,
            $dc->day,
            $dc->year);


        return date($format, $time);
    }

    public function isTrash() {
        if (empty($this->_data)) {
            return $this->err('No post selected');
        }

        return ($this->_data['post_status'] === 'trash');
    }
    
    public function sourceEpoch() {
        if (empty($this->_data)) {
            return $this->err('No post selected');
        }

        return @$this->_customFields['sourceEpoch'];
    }
}
?>
