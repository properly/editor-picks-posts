<?php
/**
 * Represents the view for the administration dashboard.
 *
 * @package   Editor_Picks_Posts
 * @author    Daniella Valentin
 * @license   GPL-2.0+
 */

?>
<div class="wrap">


  <h2><?php echo esc_html( get_admin_page_title() ); ?></h2>

  <div id="poststuff">

    <div id="post-body" class="metabox-holder columns-2">

      <!-- main content -->
      <div id="post-body-content">

        <div class="meta-box-sortables ui-sortable">

          <div class="postbox">


            <div class="inside">


				<form action="options.php" method="post">
				<?php settings_fields( 'epp-plugin-options-group' ); ?>
				<?php do_settings_sections( 'editor-picks-posts-main' ); ?>


				<?php submit_button( __( 'Save Changes', 'editor-picks-posts' ) ); ?>
				</form>



            </div> <!-- .inside -->

          </div> <!-- .postbox -->

        </div> <!-- .meta-box-sortables .ui-sortable -->

      </div> <!-- post-body-content -->

    </div> <!-- #post-body .metabox-holder .columns-2 -->

    <br class="clear">
  </div> <!-- #poststuff -->

</div> <!-- .wrap -->
