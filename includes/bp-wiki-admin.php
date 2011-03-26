<?php
// bp_wiki_admin()





// Checks for form submission, saves component settings and outputs admin screen HTML.





function bp_wiki_admin() {
	global $bp;
?>


	<div class="wrap">


		<h2><?php _e( 'Wiki Site-Admin', 'bp-wiki' ) ?></h2>


		<br />

		<?php if ( isset($updated) ) : ?><?php echo "<div id='message' class='updated fade'><p>" . __( 'Settings Updated.', 'bp-wiki' ) . "</p></div>" ?><?php endif; ?>


		<form action="<?php echo site_url() . '/wp-admin/admin.php?page=bp-wiki-settings' ?>" name="wiki-settings-form" id="wiki-settings-form" method="post">


			<table class="form-table">


				<tr valign="top">

					<th scope="row"><label for="target_uri"><?php _e( 'Option One', 'bp-wiki' ) ?></label></th>


					<td>

						<input name="wiki-setting-one" type="text" id="wiki-setting-one" value="<?php echo attribute_escape( $setting_one ); ?>" size="60" />


					</td>


				</tr>


					<th scope="row"><label for="target_uri"><?php _e( 'Option Two', 'bp-wiki' ) ?></label></th>


					<td>


						<input name="wiki-setting-two" type="text" id="wiki-setting-two" value="<?php echo attribute_escape( $setting_two ); ?>" size="60" />
						

					</td>


				</tr>


			</table>


			<p class="submit">


				<input type="submit" name="submit" value="<?php _e( 'Save Settings', 'bp-wiki' ) ?>"/>


			</p>


			<?php

			// This is very important, don't leave it out. 


			wp_nonce_field( 'wiki-settings' );

			?>


		</form>


	</div>


<?php
}


?>