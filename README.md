=== Editor Picks Posts ===

Clone of ns-featured-posts: https://wordpress.org/plugins/ns-featured-posts/

Use code below to fetch list of posts marked as "Editor Picks":

`$query = new WP_Query( array( 'meta_key' => '_is_editor_picks_post', 'meta_value' => 'yes' ) );`
