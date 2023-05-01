<?php
/* @var $bp BuddyPress */
$bp = buddypress();

//instanciating the template will do the heavy lifting with all the superglobal variables
/* @var $template BP_Group_Documents_Template */
$template = new BP_Group_Documents_Template();
?>
<div id="bp-group-documents">
	<?php do_action('template_notices') // (error/success feedback)          ?>
	<h3><?php
		echo get_option('bp_group_documents_nav_page_name') . ' ' . __('List' , 'bp-group-documents');
		?></h3>

	<?php //-----------------------------------------------------------------------LIST VIEW--        ?>
	<div class="item-list-tabs no-ajax" id="subnav" role="navigation">
		<?php if ( get_option('bp_group_documents_use_categories') ) { ?>
			<div id="bp-group-documents-categories">
				<form class="standard-form" id="bp-group-documents-category-form" method="get" action="<?php echo $template->action_link; ?>">
					&nbsp; <?php echo __('Category:' , 'bp-group-documents'); ?>
					<select name="category">
						<option value="" ><?php echo __('All' , 'bp-group-documents'); ?></option>
						<?php foreach ( $template->get_group_categories() as $category ) { ?>
							<option value="<?php echo $category->term_id; ?>" <?php
							if ( $template->category == $category->term_id ) {
								echo 'selected="selected"';
							}
							?>><?php echo $category->name; ?></option>
								<?php } ?>
					</select>
					<input type="submit" class="button" value="<?php echo __('Go' , 'bp-group-documents'); ?>" />
				</form>
			</div>
		<?php } ?>
		<div id="bp-group-documents-sorting">
			<form class="standard-form" id="bp-group-documents-sort-form" method="get" action="<?php echo $template->action_link; ?>">
				<?php _e('Order by:' , 'bp-group-documents'); ?>
				<select name="order" id="order">
					<option value="newest" <?php
					selected('newest' , $template->order);
					?>><?php _e('Newest' , 'bp-group-documents'); ?></option>
					<option value="alpha" <?php
					selected('alpha' , $template->order);
					?>><?php _e('Alphabetical' , 'bp-group-documents'); ?></option>
					<option value="popular" <?php
					selected('popular' , $template->order);
					?>><?php _e('Most Popular' , 'bp-group-documents'); ?></option>
				</select>
				<input type="submit" class="button" value="<?php _e('Go' , 'bp-group-documents'); ?>" />
			</form>
		</div>
		<?php
	
		if ( $template->document_list && (count($template->document_list) >= 1) ) { ?>
			<div class="pagination no-ajax">
				<div id="group-documents-page-count" class="pag-count">
					<?php $template->pagination_count(); ?>
				</div>
				<?php if ( $template->show_pagination() ) { ?>
					<div id="group-documents-page-links" class="pagination-links">
						<?php $template->pagination_links(); ?>
					</div>
				<?php } ?>
			</div>
		<?php } ?>
	</div> <!-- // subnav -->

	<?php //Show Add new button
	if ( $template->total_records > 4 ) {
		$template->show_add_new_button();
	}
	?>
	<?php
	if ( $template->document_list && (count($template->document_list) >= 1) ) {
		?>
		<ul id="bp-group-documents-list" class="item-list">
			<?php
			//loop through each document and display content along with admin options
			$count = 0;
			foreach ( $template->document_list as $document_params ) {
				$document = new BP_Group_Documents($document_params['id'] , $document_params);
				?>
				<li <?php
				if ( ++$count % 2 )
					echo 'class="alt"';
				?> >
						<?php
						if ( get_option('bp_group_documents_display_icons') ) {
							$document->icon();
						}
						?><a class="bp-group-documents-title" id="group-document-link-<?php echo $document->id; ?>" href="<?php $document->url(); ?>" target="_blank">
					<?php echo str_replace("\\" , "" , esc_html(stripslashes($document->name))); ?>
					<?php
					if ( get_option('bp_group_documents_display_file_size') ) {
						echo ' <span class="group-documents-filesize">(' . get_file_size($document) . ')</span>';
					}
					?></a> &nbsp;<div class="bp-group-documents-meta">
						<?php
						$document->categories();

						printf(__('Uploaded by %s on %s' , 'bp-group-documents') , bp_core_get_userlink($document->user_id) , date_i18n(get_option('date_format') , $document->created_ts));
						?>.
							<?php
							if ( get_option('bp_group_documents_display_download_count') ) {
								echo ' <span class="group-documents-download-count">' .
								$document->download_count . __(' downloads since then.' , 'bp-group-documents') .
								'</span>';
							}
							?>
					</div>
					<?php
					//show edit and delete options if user is privileged
					echo '<div class="admin-links">';
					if ( $document->current_user_can('edit') ) {
						$edit_link = wp_nonce_url($template->action_link . 'edit/' . $document->id , 'group-documents-edit-link') . '#edit-document-form';
						echo "<a href='$edit_link'>" . __('Edit' , 'bp-group-documents') . "</a> | ";
					}
					if ( $document->current_user_can('delete') ) {
						$delete_link = wp_nonce_url($template->action_link . 'delete/' . $document->id , 'group-documents-delete-link');
					    	echo "<a href='$delete_link' class='bp-group-documents-delete'>" . __( 'Delete', 'bp-group-documents' ) . "</a>";
					}
					echo '</div>';


					if ( BP_GROUP_DOCUMENTS_SHOW_DESCRIPTIONS && $document->description ) {
						echo '<span class="group-documents-description">' . wp_kses(stripslashes($document->description) , wp_kses_allowed_html('post')) . '</span>';
					}

					//eleni add this in order to display the Addthis button on 3/2/2011
					include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
					if ( is_plugin_active('buddypress-addthis-ls/bp-addthis-ls.php') ) {
						echo get_bp_addthis_ls_button($document->get_url() , $document->name);
					}
//   end eleni add

					echo '</li>';
				}
				?>
		</ul>

	<?php } else {
		?>
		<div id="message" class="info">
			<p><?php
				if ( $template->category > 0 ) {
					_e('There are no documents in the selected category.' , 'bp-group-documents');
				}
				else {
					_e('There are no documents uploaded for this group.' , 'bp-group-documents');
				}
				?></p>
		</div>
		<?php
	}
	//-------------------------------------------------------------------DETAIL VIEW--

	if ( $template->show_detail ) {
		if ( $template->operation == 'add' ) {
			?>
			<div id="bp-group-documents-upload-new">
			<?php } else { ?>
				<div id="bp-group-documents-edit"><a name="edit-document-form"></a>
				<?php } ?>

				<h3><?php echo $template->header ?></h3>

				<form method="post" id="bp-group-documents-form" class="standard-form" action="<?php echo $template->action_link; ?>" enctype="multipart/form-data">
					<input type="hidden" name="bp_group_documents_operation" value="<?php echo $template->operation; ?>" />
					<input type="hidden" name="bp_group_documents_id" value="<?php echo $template->id; ?>" />
					<?php if ( $template->operation == 'add' ) : ?>
						<input type="hidden" name="MAX_FILE_SIZE" value="<?php echo return_bytes(ini_get('post_max_size')); ?>" />
						<label class="bp-group-documents-file-label"><?php _e('Choose File:' , 'bp-group-documents'); ?></label>
						<input type="file" name="bp_group_documents_file" class="bp-group-documents-file" />
						<p class="bp-group-documents-valid-file-formats">
							<?php
							$valid_file_formats1 = get_option('bp_group_documents_valid_file_formats');
							_e('Valid File Formats' , 'bp-group-documents');
							echo ':<br />' . str_replace(',' , ', ' , $valid_file_formats1);
							?>
						</p>
					<?php else: ?>
						<label><?php _e('Document:' , 'bp-group-documents'); ?>:</label><span><?php echo $template->name; ?></span>

					<?php
					endif;
					if ( BP_GROUP_DOCUMENTS_FEATURED ) {
						?>
						<label class="bp-group-documents-featured-label"><?php _e('Featured Document' , 'bp-group-documents'); ?>: </label>
						<input type="checkbox" name="bp_group_documents_featured" class="bp-group-documents-featured" value="1" <?php if ( $template->featured ) { echo 'checked="checked"'; }?>/>
					<?php } ?>
					<div id="document-detail-clear" class="clear"></div>
					<div class="bp-group-documents-document-info">
						<label><?php _e('Display Name:' , 'bp-group-documents'); ?></label>
						<input type="text" name="bp_group_documents_name" id="bp-group-documents-name" value="<?php echo $template->name ?>" />
						<?php if ( BP_GROUP_DOCUMENTS_SHOW_DESCRIPTIONS ) { ?>
							<label><?php _e('Description:' , 'bp-group-documents'); ?></label>
							<?php
							if ( BP_GROUP_DOCUMENTS_ALLOW_WP_EDITOR ) :
								if ( function_exists('wp_editor') ) {
									wp_editor($template->description , 'bp_group_documents_description' , array(
										'media_buttons' => false ,
										'dfw' => false));
								} else {
									the_editor($template->description , 'bp_group_documents_description' , 'bp_group_documents_description' , false);
								}
							else:
								?>
								<textarea name="bp_group_documents_description" id="bp-group-documents-description" rows="5" cols="100"><?php echo esc_textarea($template->description); ?></textarea>
							<?php
							endif;
						}
						?>
					</div>

					<?php if ( get_option('bp_group_documents_use_categories') ) { ?>
						<div class="bp-group-documents-category-wrapper">
							<label><?php _e('Category:' , 'bp-group-documents'); ?></label>
							<?php
							$group_categories = $template->get_group_categories(false);
							if ( count($group_categories) > 0 ):
								?>
								<div class="bp-group-documents-category-list">
									<ul>
										<?php foreach ( $template->get_group_categories(false) as $category ) { ?>
											<li><input type="checkbox" name="bp_group_documents_categories[]" value="<?php echo $category->term_id; ?>" <?php
												if ( $template->doc_in_category($category->term_id) ) {
													echo 'checked="checked"';
												}
												?> /><?php echo $category->name; ?></li>
											<?php } ?>
									</ul>
								</div>
							<?php endif; ?>
							<input type="text" name="bp_group_documents_new_category" class="bp-group-documents-new-category" />
						</div><!-- .bp-group-documents-category-wrapper -->
					<?php } ?>
					<?php wp_nonce_field('bp_group_document_save_' . $template->operation , 'bp_group_document_save'); ?>
					<input type="submit" class="button" value="<?php _e('Save' , 'bp-group-documents'); ?>" />
				</form>
			</div><!--end #post-new-topic-->

			<?php if ( $template->operation == 'add' ) { ?>
				<a class="button" id="bp-group-documents-upload-button" href="" style="display:none;"><?php _e('Upload a New Document' , 'bp-group-documents'); ?></a>
				<?php
			}
		}
		?>

	</div><!--end #group-documents-->
