<?php
/**
 * Description of VBlog
 *
 * @author kevin
 */
class VBlog extends Base {
    public $Posts;
    public $Source;
    public $Rpc;
    
    public function itemToPost($item) {
        $cat = $this->_options['default_category'];
        $post = array(
            'title' => $item['title'],
            'description' => $item['content'],
            'categories' => $cat,
            'custom_fields' => array(
                array(
                    'key' => 'sourceEpoch',
                    'value' => $item['epoch'],
                ),
                array(
                    'key' => 'sourceGuid',
                    'value' => $item['guid'],
                ),
                array(
                    'key' => 'sourcePermalink',
                    'value' => $item['permalink'],
                ),
            ),
            'post_status' => 'publish',
            'mt_keywords' => join(',', $item['tags']),
        );

        $mediaHTML = $this->includeMedia($item['permalink']);
        // fallback: just a link to content
        if (false === $mediaHTML) {
            $mediaHTML = sprintf('<a target="_blank" href="%s">%s</a>',
                        $item['permalink'], $item['permalink']);
        }

        $post['description'] = sprintf('<p>%s</p><p>%s</p>',
            $post['description'], $mediaHTML);

        return $post;
    }

    public function includeMedia($url) {
        $buf     = file_get_contents($url);
        $handler = false;
        
        if (preg_match_all('@http://www.youtube.com/watch\?v=([a-z0-9\-\_]+)@i', $buf, $m)) {
            $handler = 'youtube';
            $id      = $m[1][0];
        }

        switch ($handler) {
            case 'youtube':
                return sprintf('[youtube=http://www.youtube.com/watch?v=%s]', $id);
                break;
        }

        return false;
    }

    public function canPost($schema, $last, $cur) {
        if ($last >= $cur) {
            return false;
        }
        // last 14
        // cur  23
        if (!in_array($cur, $schema)) {
            return false;
        }

        return true;
    }

    public function run() {
        $LastPost = $this->Posts->last();

        if (!$LastPost) {
            $sourceEpoch = 0;
        } else {
            $sourceEpoch = $LastPost->sourceEpoch();
            $postHour    = $LastPost->date('G');

            if (!$this->canPost($this->_options['schema'], $postHour, date('G'))) {
                $this->info('Cant add anything now because of schedule.');
                return null;
            }
        }
        $items = $this->Source->items();
        $items = array_reverse($items);
        foreach ($items as $item) {
            if ($item['epoch'] <= $sourceEpoch) {
                $this->info('Skipped adding item %s with epoch %s cause blog is already at %s.', 
                    $item['title'], date('Y-m-d H:i:s', $item['epoch']), date('Y-m-d H:i:s', $sourceEpoch));
            } else {
                // Item doesn't exist.
                // add
                $post = $this->itemToPost($item);
                if (!($id = $this->Posts->add($post))) {
                    return $this->err('Something went wrong while adding item %s', $item['title']);
                } else {
                    // Break out of loop. Only add 1 item at a time
                    $this->info('Successfully added item %s', $item['title']);
                    return true;
                }
            } 
        }
        
        return true;
    }
}
?>