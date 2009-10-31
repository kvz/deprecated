<?php
/**
 * Description of VBlog
 *
 * @author kevin
 */
class VBlog extends Base{
    public $feed;
    
    /**
     * Options are passed to every Class contstructor
     * and hence available troughout the entire system.
     *
     * To avoid collission, That's why they're prefixed with scope.
     *
     * @param array $options
     */
    public function  __construct($options = array()) {
        $this->className = get_class($this);

        // Take care of recursion when there are ++++ levels of inheritance
        parent::__construct($options);
        // Get direct parent defined options
        $parentVars    = @get_class_vars(@get_parent_class(__CLASS__));
        // Override with own defined options
        $this->_options = $this->merge((array)@$parentVars['_options'], $this->_options);
        // Override with own instance options
        $this->_options = $this->merge($this->_options, $options);

        $this->_initFeed();
        $this->rpc = new IXR_Client($this->_options['rpc_url']);
    }

    protected function _initFeed() {
        // Create a new SimplePie object
        $this->feed = new SimplePie();

        // Instead of only passing in one feed url, we'll pass in an array of three
        $this->feed->set_feed_url(array_keys($this->_options['sources']));
        $this->feed->enable_cache(false);
        $this->feed->set_cache_duration(3600);
        $this->feed->enable_order_by_date(true);
        $this->feed->set_item_limit(0);
        $this->feed->set_item_class('SimplePie_Item_Delicious');

        $this->feed->init();

        // This will work if all of the feeds accept the same settings.
        $this->feed->handle_content_type();

        if ($this->feed->error) {
            return $this->err($this->feed->error);
        }

        return $this->feed;
    }

    public function items($tagFilter = false) {
        $items = array();

        foreach ($this->feed->get_items() as $item) {
            $C    = $item->get_categories();
            $tags = array();
            foreach($C as $i=>$obj) {
                $tags[$obj->get_term()] = $obj->get_term();
            }

            $info = array(
                'permalink' => $item->get_permalink(),
                'guid' => $item->get_id(),
                'title' => $item->get_title(),
                'content' => $item->get_content(),
                'date' => $item->get_date('Y-m-d H:i:s'),
                'tags' => $tags,
            );

            $this->_clean($info);

            if (!$tagFilter || isset($tags[$tagFilter])) {
                $items[] = $info;
            }
        }
        
        return $items;
    }

    protected function _clean(&$arr) {
        foreach ($arr as $k=>&$v) {
            if (is_array($v)) {
                $this->_clean($v);
            } else {
                $v = htmlspecialchars_decode($v, ENT_QUOTES);
                $v = str_replace("\r", "", $v);
            }
        }
    }

    public function posts() {
        $response = $this->_rpcQ('wp.getRecentPost');

        return $response;
    }

    public function post() {
        if (!($response = $this->_rpcQ('wp.getCategories', ''))) {
            return false;
        }
        
        $content['title'] = 'Test Draft Entry using API';
        $content['categories'] = array($response[1]['categoryName']);
        $content['description'] = '<p>Hello World!</p>';

        $this->_rpcQ('metaWeblog.newPost', $content);
    }

    protected function _rpcQ($query, $content = null) {
        //  Wordpress API:
        //
        //   1. wp.getUsersBlogs
        //   2. wp.getPage
        //   3. wp.getPages
        //   4. wp.newPage
        //   5. wp.deletePage
        //   6. wp.editPage
        //   7. wp.getPageList
        //   8. wp.getAuthors
        //   9. wp.getCategories
        //  10. wp.getTags
        //  11. wp.newCategory
        //  12. wp.deleteCategory
        //  13. wp.suggestCategories
        //  14. wp.uploadFile
        //  15. wp.getCommentCount
        //  16. wp.getPostStatusList
        //  17. wp.getPageStatusList
        //  18. wp.getPageTemplates
        //  19. wp.getOptions
        //  20. wp.setOptions
        //  21. wp.getComment
        //  22. wp.getComments
        //  23. wp.deleteComment
        //  24. wp.editComment
        //  25. wp.newComment
        //  26. wp.getCommentStatusList


        // http://life.mysiteonline.org/archives/161-Automatic-Post-Creation-with-Wordpress,-PHP,-and-XML-RPC.html

        if (!$this->rpc->query($query, '',
                $this->_options['rpc_user'], $this->_options['rpc_pass'], 
                $content, false) 
        ){
            return $this->err('An error occurred - %s:%s',
                $this->rpc->getErrorCode(), $this->rpc->getErrorMessage());
        }
        return $this->rpc->getResponse();    //with Wordpress, will report the ID of the new post
    }

    public function run() {

        #$items = $this->items();
        $posts = $this->posts();

        prd($posts);
    }
}
?>