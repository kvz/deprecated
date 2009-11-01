<?php
/**
 * Description of Source
 *
 * @author kevin
 */
class Source extends Base {
    public $SimplePie = true;
    public function __setup() {
        // Create a new SimplePie object
        $this->SimplePie = new SimplePie();

        // Instead of only passing in one feed url, we'll pass in an array of three
        $this->SimplePie->set_feed_url(array_keys($this->_options['sources']));
        $this->SimplePie->enable_cache(false);
        $this->SimplePie->set_cache_duration(3600);
        $this->SimplePie->enable_order_by_date(true);
        $this->SimplePie->set_item_limit(0);
        $this->SimplePie->set_item_class('SimplePie_Item_Delicious');

        $this->SimplePie->init();

        // This will work if all of the feeds accept the same settings.
        $this->SimplePie->handle_content_type();

        if ($this->SimplePie->error) {
            return $this->err($this->SimplePie->error);
        }
    }

    public function items($tagFilter = false) {
        $items = array();

        foreach ($this->SimplePie->get_items() as $item) {
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
                'epoch' => $item->get_date('U'),
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
}
?>
