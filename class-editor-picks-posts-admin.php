<?php
/**
 * Editor Picks Posts
 *
 * @package   Editor_Picks_Posts
 * @author    Daniella Valentin
 * @license   GPL-2.0+
 */

require_once(plugin_dir_path( __FILE__ ) . 'widgets/editor-picks-widget.php');

/**
 * NS Featured Posts Admin class.
 *
 * @package editor_picks_posts_Admin
 */
class editor_picks_posts_Admin
{

    /**
     * Instance of this class.
     *
     * @since    1.0.0
     *
     * @var      object
     */
    protected static $instance = null;

    /**
     * Slug of the plugin screen.
     *
     * @since    1.0.0
     *
     * @var      string
     */
    protected $plugin_screen_hook_suffix = null;

  	protected $options = array();

    /**
     * Initialize the plugin by loading admin scripts & styles and adding a
     * settings page and menu.
     *
     * @since     1.0.0
     */
    private function __construct()
    {

        /*
         * Call $plugin_slug from public plugin class.
         *
         */
        $plugin = editor_picks_posts::get_instance();
        $this->plugin_slug = $plugin->get_plugin_slug();
    		$this->options = $plugin->editor_picks_posts_get_options_array();

        // Add the options page and menu item.
        add_action('admin_menu', array($this, 'editor_picks_posts_add_plugin_admin_menu'));

        /*
         * Add an action link pointing to the options page.
         */
        $plugin_basename = plugin_basename(plugin_dir_path(__FILE__) . 'editor-picks-posts.php');
        add_filter('plugin_action_links_' . $plugin_basename, array($this, 'editor_picks_posts_add_action_links'));

        /*
         * Define custom functionality.
         */

        add_action( 'admin_init', array($this, 'editor_picks_posts_add_columns_head'));
        add_action( 'admin_init', array($this, 'plugin_register_settings'));
        add_action( 'admin_head', array( $this,'add_script_to_admin_head') );
        add_action( 'admin_head', array( $this,'add_style_to_admin_head') );
        add_action( 'wp_ajax_nsfeatured_posts', array( $this, 'epp_ajax_featured_post' ) );

        add_action( 'restrict_manage_posts', array( $this, 'epp_table_filtering' ) );
        add_filter( 'parse_query', array( $this, 'epp_query_filtering' ) );

        add_filter( 'pre_get_posts', array( $this, 'epp_filtering_query_for_listing' ) );

        add_action( 'widgets_init', array( $this, 'epp_custom_widgets' ) );

        // Metabox stuffs.
        add_action( 'add_meta_boxes', array( $this, 'add_featured_meta_boxes' ) );
        add_action( 'save_post', array( $this, 'epp_save_meta_box' ) );

    }

    /**
     * Return an instance of this class.
     *
     * @since     1.0.0
     *
     * @return    object    A single instance of this class.
     */
    public static function get_instance()
    {

        // If the single instance hasn't been set, set it now.
        if ( null == self::$instance )
        {
            self::$instance = new self;
        }

        return self::$instance;
    }

    /**
     * Register the administration menu for this plugin into the WordPress Dashboard menu.
     *
     * @since    1.0.0
     */
    public function editor_picks_posts_add_plugin_admin_menu()
    {

        /*
         * Add a settings page for this plugin to the Settings menu.
         */
        $this->plugin_screen_hook_suffix = add_options_page(
                __('NS Featured Posts', 'editor-picks-posts'), __('NS Featured Posts', 'editor-picks-posts'), 'manage_options', 'editor-picks-posts', array($this, 'display_plugin_admin_page')
        );
    }

    /**
     * Render the settings page for this plugin.
     *
     * @since    1.0.0
     */
    public function display_plugin_admin_page()
    {
        // Check that the user is allowed to update options
        if (!current_user_can('manage_options'))
        {
            wp_die('You do not have sufficient permissions to access this page.');
        }

        include_once( 'views/admin.php' );

    }

    /**
     * Add settings action link to the plugins page.
     *
     * @since    1.0.0
     */
    public function editor_picks_posts_add_action_links( $links ) {

    	return array_merge(
    		array(
    			'settings' => '<a href="' . esc_url( admin_url( 'options-general.php?page=' . $this->plugin_slug ) ) . '">' . __( 'Settings', 'editor-picks-posts' ) . '</a>'
    			),
    		$links
		);
    }

    /**
     * Add columns to the listing.
     *
     * @since    1.0.0
     */
    function editor_picks_posts_add_columns_head(){
        foreach ( $this->options['epp_posttypes'] as $post_type => $val ) {
            add_filter('manage_edit-'.$post_type.'_columns', array( $this,'add_featured_column_heading'), 2);
            add_action('manage_'.$post_type.'_posts_custom_column', array( $this,'add_featured_column_content'), 10, 2);
        }
    }

    /**
     * Add heading in the featured column.
     *
     * @since    1.0.0
     */
    function add_featured_column_heading( $columns ){
        $columns['editor_picks_posts_col'] = __( 'Featured', 'editor-picks-posts' );
        return $columns;
    }

    /**
     * Add column content in the featured column.
     *
     * @since    1.0.0
     */
    function add_featured_column_content( $column, $id ){
        if ( $column == 'editor_picks_posts_col' ){
          $class = '';
          $ns_featured = get_post_meta( $id, '_is_ns_featured_post', true );
          $classes = array('editor_picks_posts_icon');
          if ('yes' == $ns_featured) {
              $classes[] = 'selected';
          }
          echo  '<a id="btn-post-featured_'.$id.'" class="'.implode(' ', $classes).'"></a>';
        }
    }

    /**
     * Function to handle AJAX request.
     *
     * @since    1.0.0
     */
    function epp_ajax_featured_post(){
        $ns_featured = $_POST['ns_featured'];
        $id = (int)$_POST['post'];
        if( !empty( $id ) && $ns_featured !== NULL ) {
            if ( $ns_featured == 'no' ){
                delete_post_meta( $id, "_is_ns_featured_post" );
            }
            else {
                update_post_meta( $id, "_is_ns_featured_post", 'yes' );
            }
        }
        wp_send_json_success();
    }

    /**
     * Add scripts in the admin head.
     *
     * @since    1.0.0
     */
    function add_script_to_admin_head(){
        global $pagenow;

        if ( 'edit.php' != $pagenow ) {
            return;
        }

        if ( current_user_can( 'unfiltered_html' ) ) {
            ?>
            <script type="text/javascript" language="javascript">
                jQuery(document).ready(function($){
                        jQuery('.editor_picks_posts_icon').click(function() {
                            var selected = 'yes';
                            if ( jQuery(this).hasClass( 'selected' ) ){
                                jQuery(this).removeClass( 'selected' );
                                selected = 'no';
                            } else { jQuery(this).addClass( 'selected' ); }
                            // get id
                            var tempID = jQuery(this).attr( 'id' );
                                tempID = tempID.split( '_' );
                            jQuery.post( ajaxurl, 'action=nsfeatured_posts&post='+tempID[1]+'&ns_featured='+selected );

                        });
                    });

            </script>
            <?php
        }
    }

    /**
     * Add styles in the admin head.
     *
     * @since    1.0.0
     */
    function add_style_to_admin_head(){

        global $pagenow;

        if ( 'edit.php' != $pagenow ) {
            return;
        }

        $img_url = plugins_url( 'images/featured.png' , __FILE__ );
        ?>
        <style>
            #editor_picks_posts_col, .column-editor_picks_posts_col{
                width:100px; text-align: center !important;
            }
            .editor_picks_posts_icon{
                display:block; height:24px; width:24px; margin:8px auto 0 auto; border:none;
                background: transparent url(<?php echo esc_url( $img_url ); ?>) 0 0 no-repeat; cursor:pointer;
            }
            .editor_picks_posts_icon.selected, .editor_picks_posts_icon:active{
                background-position:0 -24px;
            }
        </style>
        <?php
    }

    /**
     * Add meta box in posts.
     *
     * @since    1.1
     */
    function add_featured_meta_boxes(){

      global $typenow;
      $allowed = array();
      foreach ( $this->options['epp_posttypes'] as $post_type => $val ) {
          $allowed[] = $post_type;
      }
      if ( ! in_array($typenow,  $allowed )  ) {
          return;
      }
      $screens = $allowed;
      foreach ( $screens as $screen ) {
        add_meta_box(
          'epp_meta_box_featured',
          __( 'Featured', 'editor-picks-posts' ),
          array( $this, 'epp_meta_box_featured_callback' ),
          $screen,
          'side'
        );
      }

    }

    /**
     * Featured meta box callback.
     *
     * @since    1.0.0
     */
    function epp_meta_box_featured_callback( $post ){

      $is_ns_featured_post = get_post_meta( $post->ID, '_is_ns_featured_post', true );

      wp_nonce_field( plugin_basename( __FILE__ ), 'epp_featured_metabox_nonce' );
      ?>
      <p>
      <label>
	      <input type="hidden" name="epp_settings[make_this_featured]" value="0" />
	      <input type="checkbox" name="epp_settings[make_this_featured]" value="yes" <?php checked( $is_ns_featured_post, 'yes', true); ?> />
	      <span class="small"><?php _e( 'Check this to make featured.', 'editor-picks-posts' ); ?></span>
      </label>
      </p>
      <?php

    }

    function epp_save_meta_box( $post_id ){

      $allowed = array();
      foreach ( $this->options['epp_posttypes'] as $post_type => $val ) {
        $allowed[] = $post_type;
      }
      if ( ! in_array( get_post_type( $post_id ),  $allowed )  ) {
        return $post_id;
      }

      // Bail if we're doing an auto save
      if( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;

      // if our nonce isn't there, or we can't verify it, bail
      if ( ! isset( $_POST['epp_featured_metabox_nonce'] ) || ! wp_verify_nonce( $_POST['epp_featured_metabox_nonce'], plugin_basename( __FILE__ ) ) )
          return $post_id;

      // if our current user can't edit this post, bail
      if( ! current_user_can( 'edit_post' , $post_id ) )
        return $post_id;

      $featured_value = '';
      if ( isset( $_POST['epp_settings']['make_this_featured'] ) && 'yes' == $_POST['epp_settings']['make_this_featured'] ) {
        $featured_value = 'yes';
      }
      if ( 'yes' == $featured_value ) {
        update_post_meta( $post_id, '_is_ns_featured_post', $featured_value );
      }
      else{
        delete_post_meta( $post_id, '_is_ns_featured_post' );
      }
      return $post_id;

    }



    /**
     * Filtering dropdown in the post listing.
     *
     * @since    1.0.0
     */
    function epp_table_filtering(){
        global $wpdb, $typenow ;
        $allowed = array();
        foreach ( $this->options['epp_posttypes'] as $post_type => $val ) {
            $allowed[]= $post_type;
        }
        if ( ! in_array($typenow,  $allowed )  ) {
            return;
        }
        $selected_now = '';
        if ( isset( $_GET['filter-editor-picks-posts'] ) ) {
          $selected_now = esc_attr( $_GET['filter-editor-picks-posts'] );
        }
        echo '<select name="filter-editor-picks-posts" id="filter-editor-picks-posts">';
        echo '<option value="">'. __( 'Show All', 'editor-picks-posts' ) .'</option>';
        echo '<option value="yes" '.selected( $selected_now, 'yes', false ) .'>'. __( 'Featured', 'editor-picks-posts' ) .'</option>';
        echo '<option value="no" '.selected( $selected_now, 'no', false ) .'>'. __( 'Not Featured', 'editor-picks-posts' ) .'</option>';
        echo '</select>';
    }

    /**
     * Query filtering in the post listing.
     *
     * @since    1.0.0
     */
    function epp_query_filtering($query){

        global $pagenow;
        $qv = &$query->query_vars;
        if ( is_admin() && $pagenow == 'edit.php'){

            if ( ! isset( $qv['meta_query'] ) ) {
              $qv['meta_query'] = array();
            }

            if( !empty( $_GET['filter-editor-picks-posts'] ) ) {

                if ('yes' == $_GET['filter-editor-picks-posts'] ) {
                    $qv['meta_query'][] = array(
                       'key' => '_is_ns_featured_post',
                       'compare' => '=',
                       'value' => 'yes',
                    );
                } // end if yes

                if ('no' == $_GET['filter-editor-picks-posts'] ) {
                    $qv['meta_query'][] = array(
                       'key' => '_is_ns_featured_post',
                       'compare' => 'NOT EXISTS',
                       'value' => '',
                    );
                } // end if no

            } // end if not empty

            // for filter link
            if ( isset($_GET['post_status']) && 'epp' == $_GET['post_status']  ) {
                if ( isset($_GET['featured']) && 'yes' == $_GET['featured']  ) {

                    $qv['meta_query'][] = array(
                       'key' => '_is_ns_featured_post',
                       'compare' => '=',
                       'value' => 'yes',
                    );

                }
            }

        } // end if

    }

    /**
     * Adding filtering link
     */
    function epp_filtering_query_for_listing( $wp_query ){

        if( is_admin()) {
            $allowed_posttypes = array();
            foreach ( $this->options['epp_posttypes'] as $post_type => $val ) {
                $allowed_posttypes[]= $post_type;
            }
            if ( ! empty( $allowed_posttypes ) ) {
                foreach ( $allowed_posttypes as $val ) {
                    add_filter( 'views_edit-' . $val, array( $this,
                        'epp_add_views_link'
                    ));
                }
            }
        }
    }

    /**
     * Adding views link
     */
    function epp_add_views_link( $views ){

        $post_type = ( (isset($_GET['post_type']) && $_GET['post_type'] != "" ) ? $_GET['post_type'] : 'post');
        $count = $this->get_total_featured_count($post_type);
        $class = ( isset( $_GET['featured'] ) &&  $_GET['featured'] == 'yes' )  ? "current" : '';
        $args = array(
            'post_type'   => $post_type,
            'post_status' => 'epp',
            'featured'    => 'yes',
            );
        $url = esc_url( add_query_arg( $args,  admin_url('edit.php') ) );
        $views['featured'] = '<a href="' . $url . '" class="' . $class . '" >'
            .__('Featured','editor-picks-posts')
            .'<span class="count">'
            . ' ('.$count.') '
            .'</span>'
            .'</a>';

        return $views;
    }

    /**
     * Get total featured count
     */
    function get_total_featured_count( $post_type ) {
        $args = array(
			'post_type'      => $post_type,
			'posts_per_page' => -1,
			'meta_key'       => '_is_ns_featured_post',
			'meta_value'     => 'yes',
        );
        $postlist = get_posts( $args );
        return count( $postlist );
    }

    /**
     * epp Widgets
     */
    function epp_custom_widgets(){
        register_widget( 'epp_Featured_Post_Widget' );
    }

    /**
     * Register plugin settings
     */
    public function plugin_register_settings()
    {
      register_setting('epp-plugin-options-group', 'epp_plugin_options', array( $this, 'editor_picks_posts_plugin_options_validate') );

  		add_settings_section('main_settings', __( 'Plugin Settings', 'editor-picks-posts' ) , array($this, 'editor_picks_posts_plugin_section_text_callback'), 'editor-picks-posts-main');

  		add_settings_field('epp_posttypes', __( 'Enable Featured for', 'editor-picks-posts' ), array($this, 'epp_posttypes_callback'), 'editor-picks-posts-main', 'main_settings');


    }

	// Validate our options.
	function editor_picks_posts_plugin_options_validate($input) {

    if ( ! isset( $input['epp_posttypes'] ) ) {
      $input['epp_posttypes'] = array();
    }
		return $input;
	}

	function editor_picks_posts_plugin_section_text_callback() {
		return;
	}

	function epp_posttypes_callback() {
		?>
		<p>
			<label><input type="checkbox" name="epp_plugin_options[epp_posttypes][post]" value="1"
				<?php checked(isset($this -> options['epp_posttypes']['post']) && 1 == $this -> options['epp_posttypes']['post']); ?> /><?php _e("Post",  'editor-picks-posts' ); ?></label>
		</p>
		<p>
			<label><input type="checkbox" name="epp_plugin_options[epp_posttypes][page]" value="1"
			<?php checked(isset($this -> options['epp_posttypes']['page']) && 1 == $this -> options['epp_posttypes']['page']); ?> /><?php _e("Page",  'editor-picks-posts' ); ?></label>
		</p>
		<?php
		$args = array(
			'public'   => true,
			'_builtin' => false,
		);
		$post_types_custom = get_post_types( $args, 'objects' );

		if (!empty($post_types_custom)){
			foreach ($post_types_custom as $key => $ptype){
                $name = $ptype->labels->{'name'};
			?>
            <p>
              <label><input type="checkbox" name="epp_plugin_options[epp_posttypes][<?php echo $key; ?>]" value="1"
              	<?php checked( isset($this -> options['epp_posttypes'][$key]) && 1 == $this -> options['epp_posttypes'][$key]); ?> /><?php echo $name; ?></label>
            </p>

			<?php
			}
		}

	} // End function epp_posttypes_callback.

} // End class.
