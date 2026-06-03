<?php
/**
 * Entry import block template.
 *
 * @since 1.10.1
 *
 * @var array  $forms             Array of available forms.
 * @var array  $supported_plugins Array of supported import plugins.
 * @var string $form_action_url   Form action URL.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>
<div class="wpforms-setting-row tools wpforms-entries-import-step1">
	<h4><?php esc_html_e( 'Import Entries', 'wpforms' ); ?></h4>
	<p><?php esc_html_e( 'Easily import entries from other form plugins or a CSV file.', 'wpforms' ); ?></p>

	<form method="post" enctype="multipart/form-data" action="<?php echo esc_url( $form_action_url ); ?>" id="wpforms-entries-import-form" autocomplete="off">

		<!-- Import Source Selection (Radio Buttons) -->
		<div class="wpforms-entries-import-source-radios">
			<label class="wpforms-entries-import-radio">
				<input type="radio" name="import_source" value="plugin" checked>
				<?php esc_html_e( 'From an Installed Plugin', 'wpforms' ); ?>
			</label>
			<label class="wpforms-entries-import-radio">
				<input type="radio" name="import_source" value="export_file">
				<?php esc_html_e( 'From a File', 'wpforms' ); ?>
			</label>
		</div>

		<div class="wpforms-entries-import-type">
			<!-- Plugin Import Section -->
			<div id="wpforms-entries-import-plugin-section" class="wpforms-entries-import-section">
				<div class="wpforms-entries-import-field-group">
					<span class="choicesjs-select-wrap">
						<select
							name="wpforms_entries_import_plugin"
							id="wpforms_entries_import_plugin"
							class="choicesjs-select"
							data-search="false"
							data-sorting="off"
						>
							<option value=""><?php esc_html_e( 'Select previous form plugin', 'wpforms' ); ?></option>
							<?php foreach ( $supported_plugins as $plugin_slug => $plugin_data ) : ?>
								<option value="<?php echo esc_attr( $plugin_slug ); ?>"><?php echo esc_html( $plugin_data['name'] ); ?></option>
							<?php endforeach; ?>
						</select>
					</span>
				</div>

				<div class="wpforms-entries-import-forms-row">
					<div class="wpforms-entries-import-field-half">
						<label for="wpforms_entries_import_source_form" class="wpforms-entries-import-field-label">
							<?php esc_html_e( 'Source Form', 'wpforms' ); ?>
						</label>
						<span class="choicesjs-select-wrap">
							<select
								name="wpforms_entries_import_source_form"
								id="wpforms_entries_import_source_form"
								class="choicesjs-select"
								data-search="true"
								disabled
							>
								<option value=""><?php esc_html_e( 'Select a source form', 'wpforms' ); ?></option>
							</select>
						</span>
					</div>

					<span class="wpforms-entries-import-arrow">&rarr;</span>

					<div class="wpforms-entries-import-field-half">
						<label for="wpforms_entries_import_destination_form_from_plugin" class="wpforms-entries-import-field-label">
							<?php esc_html_e( 'Destination Form', 'wpforms' ); ?>
						</label>
						<span class="choicesjs-select-wrap">
							<select
								name="wpforms_entries_import_destination_form_from_plugin"
								id="wpforms_entries_import_destination_form_from_plugin"
								class="choicesjs-select wpforms-entries-import-dest-form"
								data-search="true"
							>
								<option value=""><?php esc_html_e( 'Select a WPForms form', 'wpforms' ); ?></option>
								<?php foreach ( $forms as $form ) : ?>
									<option value="<?php echo absint( $form->ID ); ?>"><?php echo esc_html( $form->post_title ); ?></option>
								<?php endforeach; ?>
							</select>
						</span>
					</div>
				</div>
			</div>

			<!-- CSV Import Section -->
			<div id="wpforms-entries-import-export-file-section" class="wpforms-entries-import-section wpforms-hidden">
				<?php $max_upload_size = wp_max_upload_size(); ?>

				<div class="wpforms-entries-import-field-group">
					<div class="wpforms-file-upload">
						<input
							type="file"
							name="wpforms_entries_import_file"
							id="wpforms_entries_import_file"
							class="inputfile"
							data-multiple-caption="{count} <?php esc_attr_e( 'files selected', 'wpforms' ); ?>"
							data-max-size="<?php echo esc_attr( $max_upload_size ); ?>"
							accept=".csv"
							aria-label="<?php esc_attr_e( 'Upload CSV file', 'wpforms' ); ?>"
							aria-describedby="wpforms_entries_import_file_desc"
						/>
						<label for="wpforms_entries_import_file">
							<span class="fld" aria-hidden="true"><span class="placeholder"><?php esc_html_e( 'No file chosen', 'wpforms' ); ?></span></span>
							<strong class="wpforms-btn wpforms-btn-md wpforms-btn-light-grey" aria-hidden="true">
								<i class="fa fa-cloud-upload" aria-hidden="true"></i><?php esc_html_e( 'Choose a File', 'wpforms' ); ?>
							</strong>
						</label>
					</div>
					<p class="description" id="wpforms_entries_import_file_desc">
						<?php
						printf(
							/* translators: %s - maximum upload file size. */
							esc_html__( 'Maximum upload file size: %s.', 'wpforms' ),
							esc_html( size_format( $max_upload_size ) )
						);
						?>
					</p>
					<div class="wpforms-entries-import-file-notices"></div>
				</div>

				<div class="wpforms-entries-import-field-group">
					<label for="wpforms_entries_import_destination_form_export_file" class="wpforms-entries-import-field-label">
						<?php esc_html_e( 'Destination Form', 'wpforms' ); ?>
					</label>
					<span class="choicesjs-select-wrap" id="wpforms_entries_import_destination_form_export_file_wrap">
						<select
							name="wpforms_entries_import_destination_form_export_file"
							id="wpforms_entries_import_destination_form_export_file"
							class="choicesjs-select wpforms-entries-import-dest-form"
							data-search="true"
						>
							<option value="" placeholder><?php esc_html_e( 'Select a WPForms form', 'wpforms' ); ?></option>
							<?php foreach ( $forms as $form ) : ?>
								<option value="<?php echo absint( $form->ID ); ?>"><?php echo esc_html( $form->post_title ); ?></option>
							<?php endforeach; ?>
						</select>
					</span>
				</div>
			</div>
		</div>

		<input type="hidden" name="action" value="wpforms_entries_import_preview">
		<?php wp_nonce_field( 'wpforms-entry-import' ); ?>

		<div class="wpforms-entries-import-step1-notices"></div>

		<div class="wpforms-entries-import-actions wpforms-entries-import-step1-actions">
			<button type="button" name="submit_entries_import" id="wpforms_entries_import_submit" class="wpforms-btn wpforms-btn-md wpforms-btn-blue" disabled>
				<?php esc_html_e( 'Continue', 'wpforms' ); ?>
			</button>
		</div>

		<!-- Field Mapping Block (hidden by default) -->
		<div id="wpforms-entries-import-mapping-block" class="wpforms-entries-import-mapping-block wpforms-hidden">

			<div class="wpforms-entries-import-mapping-block-table">
				<h5><?php esc_html_e( 'Field Mapping', 'wpforms' ); ?></h5>
				<p class="wpforms-entries-import-description">
					<?php esc_html_e( 'Match fields between forms to ensure entry data imports correctly.', 'wpforms' ); ?>
				</p>

				<!-- Mapping Table -->
				<div class="wpforms-entries-import-mapping">
					<div class="wpforms-entries-import-mapping-header">
						<span class="wpforms-entries-import-col-source"><?php esc_html_e( 'Source Field', 'wpforms' ); ?></span>
						<span class="wpforms-entries-import-col-arrow"></span>
						<span class="wpforms-entries-import-col-destination"><?php esc_html_e( 'Destination Field', 'wpforms' ); ?></span>
					</div>
					<div id="wpforms-entries-import-mapping-body" class="wpforms-entries-import-mapping-body">
						<!-- Rows will be populated by JavaScript -->
					</div>
				</div>

				<!-- Mapping Status -->
				<div class="wpforms-entries-import-status">
					<i id="wpforms-entries-import-status-icon" class="fa fa-exclamation-triangle" aria-hidden="true"></i>
					<span id="wpforms-entries-import-mapped-count">0</span> <?php esc_html_e( 'of', 'wpforms' ); ?> <span id="wpforms-entries-import-total-fields">0</span> <?php esc_html_e( 'fields are mapped,', 'wpforms' ); ?> <span id="wpforms-entries-import-skipped-count">0</span> <?php esc_html_e( 'fields will be skipped during import.', 'wpforms' ); ?>
				</div>
			</div>

			<!-- Total Entries Info -->
			<div class="notice notice-info inline wpforms-entries-import-total">
				<p><span id="wpforms-entries-import-total-text"></span></p>
			</div>

			<div class="wpforms-entries-import-step2-notices"></div>

			<!-- Action Buttons -->
			<div class="wpforms-entries-import-actions">
				<button type="button" id="wpforms-entries-import-submit" class="wpforms-btn wpforms-btn-md wpforms-btn-orange" disabled>
					<?php esc_html_e( 'Import Entries', 'wpforms' ); ?>
				</button>
				<button type="button" id="wpforms-entries-import-cancel" class="wpforms-btn wpforms-btn-md wpforms-btn-light-grey">
					<?php esc_html_e( 'Cancel', 'wpforms' ); ?>
				</button>
			</div>
		</div>
	</form>

	<!-- Progress Modal (contains both progress and finished states) -->
	<div id="wpforms-entries-import-progress-modal" class="wpforms-entries-import-progress-modal wpforms-hidden">
		<div class="wpforms-entries-import-progress-modal-overlay"></div>
		<div class="wpforms-entries-import-progress-modal-content">
			<!-- Shared icon -->
			<div class="wpforms-entries-import-progress-modal-icon">
				<div class="wpforms-builder-overlay-content">
					<i class="spinner">
						<?php require WPFORMS_PLUGIN_DIR . 'assets/images/builder/loading-spinner.svg'; ?>
					</i>
					<i class="avatar"></i>
				</div>
			</div>

			<!-- Progress State -->
			<div class="wpforms-entries-import-state-progress">
				<h3 class="wpforms-entries-import-progress-modal-title"><?php esc_html_e( 'Just a Minute', 'wpforms' ); ?></h3>
				<p class="wpforms-entries-import-progress-modal-subtitle"><?php esc_html_e( 'Sit back and relax while we import your entries.', 'wpforms' ); ?></p>
				<div class="wpforms-entries-import-progress-modal-bar-container">
					<div id="wpforms-entries-import-progress-bar" class="wpforms-entries-import-progress-modal-bar"></div>
				</div>
				<p id="wpforms-entries-import-progress-text" class="wpforms-entries-import-progress-modal-text">
					<?php
					printf(
						/* translators: %1$s - current count, %2$s - total count. */
						esc_html__( '%1$s of %2$s entries imported', 'wpforms' ),
						'<span id="wpforms-entries-import-progress-current">0</span>',
						'<span id="wpforms-entries-import-progress-total">0</span>'
					);
					?>
				</p>
			</div>

			<!-- Finished State -->
			<div class="wpforms-entries-import-state-finished">
				<h3 class="wpforms-entries-import-progress-modal-title"><?php esc_html_e( 'Import Complete', 'wpforms' ); ?></h3>
				<p class="wpforms-entries-import-progress-modal-subtitle">
					<?php
					printf(
						/* translators: %1$s - number of entries, %2$s - form name with link. */
						esc_html__( '%1$s entries were imported to %2$s.', 'wpforms' ),
						'<span id="wpforms-entries-import-finished-count">0</span>',
						'<a href="#" target="_blank" id="wpforms-entries-import-finished-form-link"><span id="wpforms-entries-import-finished-form-name"></span></a>'
					);
					?>
				</p>
				<div class="wpforms-entries-import-progress-modal-stats">
					<div class="wpforms-entries-import-progress-modal-stat wpforms-entries-import-stat-total">
						<span id="wpforms-entries-import-stat-total" class="wpforms-entries-import-progress-modal-stat-value">0</span>
						<span id="wpforms-entries-import-stat-total-label" class="wpforms-entries-import-progress-modal-stat-label"><?php echo esc_html( _n( 'total entry', 'total entries', 0, 'wpforms' ) ); ?></span>
					</div>
					<div class="wpforms-entries-import-progress-modal-stat wpforms-entries-import-stat-success">
						<span id="wpforms-entries-import-stat-success" class="wpforms-entries-import-progress-modal-stat-value">0</span>
						<span id="wpforms-entries-import-stat-success-label"  class="wpforms-entries-import-progress-modal-stat-label"><?php echo esc_html( _n( 'imported entries', 'imported entries', 0, 'wpforms' ) ); ?></span>
					</div>
					<div class="wpforms-entries-import-progress-modal-stat wpforms-entries-import-stat-issues">
						<span id="wpforms-entries-import-stat-issues" class="wpforms-entries-import-progress-modal-stat-value">0</span>
						<span id="wpforms-entries-import-stat-issues-label" class="wpforms-entries-import-progress-modal-stat-label"><?php echo esc_html( _n( 'with issue', 'with issues', 0, 'wpforms' ) ); ?></span>
					</div>
				</div>
				<!-- Entries With Issues Block -->
				<div id="wpforms-entries-import-issues-block" class="wpforms-entries-import-issues-block">
					<div class="wpforms-entries-import-issues-header">
						<span class="wpforms-entries-import-issues-title"><?php esc_html_e( 'Entries With Issues', 'wpforms' ); ?></span>
						<div class="wpforms-entries-import-issues-header-actions">
							<button type="button" class="wpforms-entries-import-issues-toggle" aria-expanded="false">
								<i class="fa fa-chevron-right" aria-hidden="true"></i>
								<span class="screen-reader-text"><?php esc_html_e( 'Toggle issues list', 'wpforms' ); ?></span>
							</button>
						</div>
					</div>
					<div class="wpforms-entries-import-issues-content wpforms-hidden">
						<div id="wpforms-entries-import-issues-list" class="wpforms-entries-import-issues-list">
							<!-- Issues will be populated dynamically by JavaScript -->
						</div>
					</div>
				</div>
				<!-- Skipped Rows Block -->
				<div id="wpforms-entries-import-skipped-rows-block" class="wpforms-entries-import-issues-block wpforms-entries-import-skipped-rows-block">
					<div class="wpforms-entries-import-issues-header">
						<span class="wpforms-entries-import-issues-title"><?php esc_html_e( 'Skipped Rows', 'wpforms' ); ?></span>
						<div class="wpforms-entries-import-issues-header-actions">
							<a href="#" id="wpforms-entries-import-issues-download" class="wpforms-entries-import-issues-download"><?php esc_html_e( 'Download CSV', 'wpforms' ); ?></a>
							<button type="button" class="wpforms-entries-import-issues-toggle" aria-expanded="false">
								<i class="fa fa-chevron-right" aria-hidden="true"></i>
								<span class="screen-reader-text"><?php esc_html_e( 'Toggle skipped rows list', 'wpforms' ); ?></span>
							</button>
						</div>
					</div>
					<div class="wpforms-entries-import-issues-content wpforms-hidden">
						<div id="wpforms-entries-import-skipped-rows-list" class="wpforms-entries-import-issues-list">
							<!-- Skipped rows will be populated dynamically by JavaScript -->
						</div>
					</div>
				</div>
				<div class="wpforms-entries-import-progress-modal-actions">
					<a href="#" id="wpforms-entries-import-view-entries" class="wpforms-btn wpforms-btn-md wpforms-btn-orange"><?php esc_html_e( 'View Imported Entries', 'wpforms' ); ?></a>
					<a href="#" id="wpforms-entries-import-new-import" class="wpforms-btn wpforms-btn-md wpforms-btn-light-grey wpforms-entries-import-progress-modal-link"><?php esc_html_e( 'Start New Import', 'wpforms' ); ?></a>
				</div>
			</div>
		</div>
	</div>
</div>
