<?php
/**
 * SimplePie Add-on for Delicious tags
 *
 * Copyright (c) 2004-2007, Peet Sneekes
 * All rights reserved. License matches the current SimplePie license.
 */

if (!defined('SIMPLEPIE_NAMESPACE_DELICIOUS')) {
    define('SIMPLEPIE_NAMESPACE_DELICIOUS', 'http://purl.org/dc/elements/1.1/');
}

class SimplePie_Item_Delicious extends SimplePie_Item {

/**
 * @var bool is this a del.icio.us item?
 * @access private
 */
    var $is_delicious = true;

    // Overloading an existing method.

    function get_categories() {
        $categories = array();

        foreach ((array) $this->get_item_tags(SIMPLEPIE_NAMESPACE_ATOM_10, 'category') as $category) {
            $term = null;
            $scheme = null;
            $label = null;
            if (isset($category['attribs']['']['term'])) {
                $term = $this->sanitize($category['attribs']['']['term'], SIMPLEPIE_CONSTRUCT_TEXT);
            }
            if (isset($category['attribs']['']['scheme'])) {
                $scheme = $this->sanitize($category['attribs']['']['scheme'], SIMPLEPIE_CONSTRUCT_TEXT);
            }
            if (isset($category['attribs']['']['label'])) {
                $label = $this->sanitize($category['attribs']['']['label'], SIMPLEPIE_CONSTRUCT_TEXT);
            }
            $categories[] =& new $this->feed->category_class($term, $scheme, $label);
        }
        foreach ((array) $this->get_item_tags('', 'category') as $category) {
            $categories[] =& new $this->feed->category_class($this->sanitize($category['data'], SIMPLEPIE_CONSTRUCT_TEXT), null, null);
        }
        foreach ((array) $this->get_item_tags(SIMPLEPIE_NAMESPACE_DC_11, 'subject') as $category) {
            $exploded_categories = explode(' ', $this->sanitize($category['data'], SIMPLEPIE_CONSTRUCT_TEXT));
            foreach ((array) $exploded_categories as $exploded_category) {
                $categories[] =& new $this->feed->category_class($exploded_category, null, null);
            }

        }
        foreach ((array) $this->get_item_tags(SIMPLEPIE_NAMESPACE_DC_10, 'subject') as $category) {
            $categories[] =& new $this->feed->category_class($this->sanitize($category['data'], SIMPLEPIE_CONSTRUCT_TEXT), null, null);
        }

        if (!empty($categories)) {
            return SimplePie_Misc::array_unique($categories);
        }
        else {
            return null;
        }
    }
}

?>