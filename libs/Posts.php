<?php
/**
 * Description of Posts
 * http://life.mysiteonline.org/archives/161-Automatic-Post-Creation-with-Wordpress,-PHP,-and-XML-RPC.html
 *
 * @author kevin
 */
class Posts extends Base {
    public $Rpc;
    public $Post;

    protected $_Posts = array();

    public function __setup() {
        $this->index();
    }

    public function add($post) {
        $this->Post->data($post);
        return $this->Post->save();
    }

    public function last($cache = true) {
        $this->_index($cache);
        
        foreach($this->_Posts as $Post) {
            if (!$Post->isTrash()) {
                return $Post;
            }
        }

        return null;
    }

    protected function _index($cache = true) {
        if (!$cache || empty($this->_Posts)) {
            $posts = $this->Rpc->q('metaWeblog.getRecentPosts');
            $this->_Posts = array();
            foreach($posts as $index=>$post) {
                $this->_Posts[$index] = new Post();
                $this->_Posts[$index]->data($post);
            }
        }
    }

    public function index($cache = true) {
        $this->_index($cache);
        return $this->_Posts;
    }

    public function statuslist() {
        return $this->Rpc->q('wp.getPostStatusList');;
    }
}
?>
