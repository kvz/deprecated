<?php
// Include the SimplePie library
require_once DIR_VBLOG."/vendors/simplepie.inc.php";

/**
 * Description of VBlog
 *
 * @author kevin
 */
class VBlog {
    public $feed;
    public $config;

    public function  __construct($config) {
        $this->config = $config;
        // Create a new SimplePie object
        $this->feed = new SimplePie();

        // Instead of only passing in one feed url, we'll pass in an array of three
        $this->feed->set_feed_url(array(
            array_keys($this->config['sources'])
        ));

        // Initialize the feed object
        $feed->enable_cache(false);
        $feed->set_cache_duration(3600);
        $feed->enable_order_by_date(true);
        $feed->set_item_limit(0);
        $feed->init();

        // This will work if all of the feeds accept the same settings.
        $feed->handle_content_type();

        if ($feed->error) {
            trigger_error($feed->error, E_USER_ERROR);
        }
    }

    public function run() {
        $cntUpdated = 0;
        $cntAdded   = 0;
        foreach ($feed->get_items() as $item) {
            $feed = $item->get_feed();

            $permaLink = $item->get_permalink();

            $info = array();
            $info["feed_category"]  = "bookmarks";
            $info["feed_title"]     = $feed->get_title();
            $info["feed_source"]    = feedSource($info["feed_title"]);
            $info["feed_permalink"] = $feed->get_permalink();
            $info["feed_favicon"]   = $feed->get_favicon();
            $info["permalink"]      = $permaLink;
            $info["guid"]           = $item->get_id();
            $info["language"]       = urlLanguage($info["permalink"]);
            $info["title"]          = htmlspecialchars_decode($item->get_title(), ENT_QUOTES);
            $info["content"]        = htmlspecialchars_decode($item->get_content(), ENT_QUOTES);
            $info["content"]        = str_replace("\r", "", $info["content"]);
            $info["content_head"]   = trim(strip_tags($info["content"]));
            if (strlen($info["content_head"]) > 252) {
                $info["content_head"] = substr($info["content_head"], 0, 252)."...";
            }
            $info["date"]           = $item->get_date('Y-m-d H:i:s');  //

            pr($info);
        }
    }
}
?>