<?php

namespace WPDataAccess\Global_Search {

	use WPDataAccess\Connection\WPDADB;
	use WPDataAccess\Data_Dictionary\WPDA_Dictionary_Lists;
	use WPDataAccess\Data_Dictionary\WPDA_List_Columns_Cache;
	use WPDataAccess\WPDA;

	class WPDA_Global_Search {

		const NONCE_SEED = 'wpda-global-search-and-replace-';

		protected $databases = array();

		public function __construct() {
			$dbs = WPDA_Dictionary_Lists::get_db_schemas();
			foreach ( $dbs as $db ) {
				$this->databases[] = $db['schema_name'];
			}
		}

		public function show() {
			$this->css();
			$this->js();

			$esc_attr = 'esc_attr';
			?>
			<div class="wrap">
				<h1 class="wp-heading-inline">
					<span style="vertical-align: text-top">Search & Replace</span>
				</h1>

				<div id="tabs">
					<ul>
						<li><a href="#tabs-1">Search</a></li>
						<li><a href="#tabs-2">Replace</a></li>
					</ul>
					<div id="tabs-1">
						<div>
							<input type="text" id="searchbox" placeholder="Search for..." onblur="jQuery('#replace_searchbox').val(jQuery(this).val())" />
							<label>
								<input type="checkbox" id="searchbox-case" onblur="jQuery('#replace_searchbox-case').prop('checked', jQuery(this).is(':checked'))" />Case-sensitive
							</label>
							<button type="button" class="button button-primary" onclick="startSearch()">SEARCH</button>
						</div>
					</div>
					<div id="tabs-2">
						<div>
							<label for="replace_searchbox">
								Search
							</label>
							<input type="text" id="replace_searchbox" placeholder="Search for..." onblur="jQuery('#searchbox').val(jQuery(this).val())" />
							<label>
								<input type="checkbox" id="replace_searchbox-case" onblur="jQuery('#searchbox-case').prop('checked', jQuery(this).is(':checked'))" />Case-sensitive
							</label>
						</div>
						<div>
							<label for="replace_replacebox">
								Replace
							</label>
							<input type="text" id="replace_replacebox" placeholder="Replace with..." />
							<button type="button" class="button button-primary" onclick="startReplace()">SEARCH</button>
						</div>
					</div>
				</div>

				<fieldset class="wpda_fieldset">
					<legend>
						Select databases and tables
					</legend>
					<div id="selectionFrame">
						<div id="selectionDatabases">
							<div class="selectionFrameHeader">
								Available databases
								<i class="fas fa-database"></i>
							</div>
							<div class="selectionFrameBody">
								<?php
								global $wpdb;
								foreach ( $this->databases as $database ) {
									$dbname = $database === $wpdb->dbname ? "WordPress database ({$esc_attr( $database )})" : esc_attr( $database );
									$dbs = <<< EOL
										<div class="selectionFrameBodyElement"
											 onclick="selectSchema('{$esc_attr( $database )}')"
											 id="{$esc_attr( $database )}"
										>
											<input type="checkbox" id="chk_{$esc_attr( $database )}" />
											{$dbname}
										</div>
EOL;
									echo $dbs;
								}
								?>
							</div>
							<div class="selectionFrameFooter">
								<i class="fas fa-info-circle"></i> Click on a database name to select tables
							</div>
						</div>
						<div id="selectionTables">
							<div class="selectionFrameHeader">
								<span>
									<label>
										<input type="checkbox"
											   title="Select all/Unselect all"
											   class="wpda_tooltip"
											   id="toggleAllTables"
											   onclick="toggleAllTables()"
										/>
										Available tables
									</label>
									<label style="padding-left:10px;display:none;" id="showWordPressTables">
										<input type="checkbox"
											   title="Select all/Unselect all"
											   class="wpda_tooltip"
											   id="toggleWordPressTables"
											   onclick="toggleWordPressTables()"
										/>
										WordPress tables
									</label>
								</span>
								<i class="fas fa-table"></i>
							</div>
							<div class="selectionFrameBody">
								<div class="selectionFrameBodySpinner">
									<div>
										<i class="fa fa-spinner fa-spin"></i>
										<br/><br/>
										<span class="blink_me">
											Loading tables...
										</span>
									</div>
								</div>
							</div>
							<div class="selectionFrameFooter">
								<i class="fas fa-info-circle"></i> Select tables to be searched
							</div>
						</div>
					</div>
				</fieldset>

				<fieldset id="wpda_searchresults_panel" class="wpda_fieldset" style="display:none">
					<legend>
						Search results
					</legend>

					<div id="wpda_searchresults"></div>
					<div class="selectionFrameFooter">
						<i class="fas fa-info-circle"></i> <span id="totalMatches">0</span> total rows
					</div>
				</fieldset>
			</div>
			<?php
		}

		private function css() {
			?>
			<style>
                :root {
                    --height: 30vh;
                }

                fieldset {
					margin-top: 10px;
				}

                #searchbox {
                    width: 100%;
                }

                #selectionFrame {
                    position: relative;
                    height: calc(var(--height) + 115px);
					overflow: hidden;
                }
				#selectionDatabases,
                #selectionTables {
					position: absolute;
					top: 0;
                    width: calc(50% - 5px);
				}
                #selectionTables {
					right: 0;
				}
				.selectionFrameHeader {
                    position: relative;
					padding: 18px 16px;
					background-color: #acacac;
					font-weight: bold;
					border-radius: 5px 5px 0 0;
				}
                .selectionFrameHeader i.fas {
					display: inline-block;
                    position: absolute;
					top: 22px;
					right: 20px;
				}
				.selectionFrameBody {
                    height: var(--height);
					overflow-y: scroll;
                    border-left: 1px solid #acacac;
                    border-right: 1px solid #acacac;
                }
                .selectionFrameBody > div:nth-child(even) {
                    background-color: #fff;
                }
                .selectionFrameBody > div {
                    padding: 18px 16px;
                }
                .selectionFrameBody > div:not(.selectionFrameBodySpinner):hover {
					background-color: rgba(255, 255, 0, 0.5);
					font-weight: bold;
                }
				.selectionFrameBodyElement {
					cursor: pointer;
				}
				.selectionFrameFooter {
                    padding: 18px 16px;
                    background-color: #acacac;
                    border-radius: 0 0 5px 5px;
                }
                .selectionFrameFooter i.fas {
					margin-right: 10px;
                }
				.databaseSelected {
                    background-color: rgba(255, 255, 0) !important;
                    font-weight: bold;
				}
				.selectionFrameBodySpinner {
                    height: calc(var(--height) - 55px);
                    display: flex;
                    justify-content: center;
                    align-items: center;
				}
                .selectionFrameBodySpinner > div {
                    text-align: center;
                }
                .selectionFrameBodySpinner i {
                    font-size: 300%;
                }
                .blink_me {
                    animation: blinker 1s linear infinite;
                }
                @keyframes blinker {
                    50% {
                        opacity: 0;
                    }
                }

				#wpda_searchstart {
					text-align: end;
					margin-top: 2px;
				}

				#wpda_searchresults .wpdaSchemaOutput {
                    background-color: #acacac;
                    padding: 18px 16px;
				}
                #wpda_searchresults .wpdaTableOutput {
                    padding: 18px 16px 18px 36px;
                    border-left: 1px solid #acacac;
                    border-right: 1px solid #acacac;
                    display: flex;
                    justify-content: space-between;
					align-items: center;
                }
                #wpda_searchresults .wpdaTableOutput:nth-child(even) {
                    background-color: #fff;
                }
                #wpda_searchresults .wpdaSchemaOutput:first-child {
                    border-radius: 5px 5px 0 0;
					position: relative;
				}
                #wpda_searchresults .wpdaTableOutput span {
					line-height: 30px;
                }
                #wpda_searchresults .wpdaTableOutput button {
                    line-height: 28px;
					margin-left: 5px;
                }
                #wpda_searchresults .wpdaSchemaOutput i.fas,
                #wpda_searchresults .wpdaTableOutput i.fas {
                    margin-right: 10px;
                }
                #wpda_searchresults .wpdaTableOutput:hover {
                    background-color: rgba(255, 255, 0, 0.5);
                    font-weight: bold;
                }
                #wpda_searchresults .wpdaTableOutput a {
					text-decoration: none;
                }
                .match {
					display: inline-block;
                    width: 5px;
                    height: 0;
                    border-right: 15px solid transparent;
					vertical-align: sub;
                }
				.matches {
                    border-top: 8px solid transparent;
                    border-right: 15px solid #555;
                    border-bottom: 8px solid transparent;
				}
				.disableLink {
                    color: unset;
					pointer-events: none;
				}
				#wpda_searchresults .wpdaSchemaOutput label {
                    line-height: 30px;
                    vertical-align: initial;
                    position: absolute;
                    right: 12px;
                    top: 13px;
				}
				.communication_error {
                    cursor: pointer;
				}
                #tabs {
                    padding: 0;
                    background: none;
                    border-width: 0;
                }
                #tabs .ui-tabs-nav {
                    padding-left: 0;
                    background: transparent;
                    border-width: 0 0 1px 0;
                    border-color: #ccd0d4;
                    -moz-border-radius: 0;
                    -webkit-border-radius: 0;
                    border-radius: 0;
                }
                #tabs .ui-tabs-panel {
                    background: #fff;
                    border-width: 0 1px 1px 1px;
                    border-color: #ccd0d4;
                }
				.ui-tabs-anchor {
					font-weight: bold;
				}
                #tabs-1,
                #tabs-2 {
					margin: 0;
					padding: 10px;
				}
                #tabs-1 label {
					text-align: center;
                }
                #tabs-1 > div {
                    display: grid;
                    grid-template-columns: auto 120px 110px;
					grid-column-gap: 5px;
                    align-items: center;
				}
                #tabs-2 > div:first-child {
                    display: grid;
                    grid-template-columns: 65px auto 120px;
                    align-items: center;
					margin-bottom: 5px;
                }
                #tabs-2 > div:last-child {
                    display: grid;
                    grid-template-columns: 65px auto 110px;
                    align-items: center;
                }
                #tabs-2 > div:last-child input[type=text] {
                    margin-right: 10px;
                }
                #tabs-2 label {
					padding-left: 10px;
                }
				.wpda_tooltip_sar_css {
					max-width: 600px;
                    background: black;
                    color: white;
                    border: 2px solid white;
                    font: bold 10px "Helvetica Neue", Sans-Serif;
                    box-shadow: 0 0 7px black;
				}
			</style>
			<?php
		}

		private function js() {
			global $wpdb;
			$wp_database = $wpdb->dbname;
			?>
			<script>
				var wpdaDatabases = <?php echo json_encode( $this->databases ); ?>;
				var wpDatabase = "<?php echo esc_attr( $wp_database ); ?>";
				var wpdaTables = [];
				var wpTables = <?php echo json_encode( array_keys( WPDA::get_wp_tables() ) ); ?>;
				var schemasProcessed = 0;
				var totalMatches = 0;
				var replaceAllSelectedTables = false;

				function updateTotalMatches(n) {
					totalMatches += n;
					jQuery("#totalMatches").html(totalMatches);
				}

                function cleanId(id) {
                    return id.replaceAll(".", "\\.").replaceAll(":", "\\:")
                }

				function showTableResults(schemaName, tableName, rows) {
					jQuery(cleanId(`#result_${schemaName}_${tableName}`)).html(rows + " rows");
					jQuery(cleanId(`#result_${schemaName}_${tableName}`)).closest("a").find(".linkSpinner").hide();

					if (parseInt(rows)!==NaN && parseInt(rows)>0) {
						jQuery(cleanId(`#result_${schemaName}_${tableName}`)).closest("div").find(".match").addClass("matches");
						updateTotalMatches(parseInt(rows));
						jQuery(cleanId(`#result_${schemaName}_${tableName}`)).closest("a").removeClass("disableLink");
						jQuery(cleanId(`#view_${schemaName}_${tableName}`)).closest("span").find("button").show();
					}
				}

				function submitForm(schemaName, tableName) {
					if (jQuery("#matchesInNewTab").is(":checked")) {
						jQuery(cleanId(`#view_${schemaName}_${tableName}`)).attr("target", "_blank");
					} else {
						jQuery(cleanId(`#view_${schemaName}_${tableName}`)).removeAttr("target");
					}

					jQuery(cleanId(`#view_${schemaName}_${tableName}`)).submit();
				}

				function showTableOutput(schemaName, tableName, addReplaceButtons, searchString, searchCase, replaceString) {
					let button = addReplaceButtons ?
						`<button type="button" class="button button-secondary replace-table-button" style="display:none" onclick="replace('${schemaName}', '${tableName}', '${searchString}', '${searchCase}', '${replaceString}')">Replace</button>` : '<span></span>';


					jQuery("#wpda_searchresults").append(`
						<div class="wpdaTableOutput">
							<span>
								<i class="fas fa-table"></i>
								${tableName}
							</span>
							<span>
								<form id="view_${schemaName}_${tableName}" action="?page=wpda" method="post" style="display:none">
									<input type="hidden" name="wpdaschema_name" value="${schemaName}" />
									<input type="hidden" name="table_name" value="${tableName}" />
									<input type="hidden" name="wpda_s" value="${searchString}" />
									<input type="hidden" name="wpda_c" value="${searchCase}" />
                                    <input type="hidden" name="explorer" value="OLD" />
								</form>
								<a href="javascript:submitForm('${schemaName}', '${tableName}')" class="disableLink">
									<span class="linkSpinner">
										<i class="fa fa-spinner fa-spin"></i>
										Searching...
									</span>
									<span id="result_${schemaName}_${tableName}"></span>
								</a>
								${button}
								<span class="match"></span>
							</span>
						</div>
					`);
				}

				function showSchemaOutput(schemaName, i, addReplaceButtons, searchString, replaceString) {
					let selection = "";
					let button = addReplaceButtons ?
						`<button type="button" class="button button-secondary" onclick="replaceAll('${searchString}', '${replaceString}')">Replace All</button>` :
						'<span style="display:inline-block;width:5px"></span>';

					if (i===0) {
						// Add selection to open links in new tab|window
						selection = `<label>
							<input type="checkbox" id="matchesInNewTab" checked />
							<span>Open rows in new tab or window</span>
							${button}
						</label>`;
					}

					jQuery("#wpda_searchresults").append(`
						<div class="wpdaSchemaOutput">
							<i class="fas fa-database"></i>
							${schemaName}
							${selection}
						</div>
					`);
				}

				function replace(schemaName, tableName, searchString, searchCase, replaceString) {
					if (!replaceAllSelectedTables) {
						wpda_confirm("Replace?", `Replace all occurences of <strong>${searchString}</strong> with <strong>${replaceString}</strong> in table <strong>${tableName}</strong>?<br/><br/>This action cannot be undone!<br/>Are you sure you want to continue?`).then(function (a) {
							if (a) {
								replaceTable(schemaName, tableName, searchString, searchCase, replaceString);
							}
						});
					} else {
						replaceTable(schemaName, tableName, searchString, searchCase, replaceString);
					}
				}

				function replaceAll(searchString, replaceString) {
					wpda_confirm("Replace?",`Replace all occurences of <strong>${searchString}</strong> with <strong>${replaceString}</strong> in all selected tables?<br/><br/>This action cannot be undone!<br/>Are you sure you want to continue?`).then(function(a) {
						if (a) {
							replaceAllSelectedTables = true; // :-\
							jQuery(".replace-table-button:visible").trigger("click", "test");
							replaceAllSelectedTables = false; // :-/
						}
					});
				}

				function showError(schemaName, tableName) {
					jQuery(cleanId(`#result_${schemaName}_${tableName}`)).html("");
					jQuery(cleanId(`#result_${schemaName}_${tableName}`)).closest("a").find(".linkSpinner").hide();

					jQuery(cleanId(`#view_${schemaName}_${tableName}`)).closest("span").append(`
						<span class="communication_error wpda_tooltip" title="Please check the console for more information">
							ERROR
							<i class="fas fa-exclamation-triangle"></i>
						</span>
					`);
					jQuery(".wpda_tooltip").tooltip();
				}

				function searchTable(schemaName, tableName, searchString, searchCase) {
					jQuery.ajax({
						method: "POST",
						url: "<?php echo admin_url( 'admin-ajax.php?action=wpda_global_search' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>",
						data: {
							n: "<?php echo esc_attr( wp_create_nonce( self::NONCE_SEED . WPDA::get_current_user_login() ) ); ?>",
							sn: schemaName,
							tn: tableName,
							q: searchString,
							c: searchCase
						}
					}).done(
						function(msg) {
							if (msg.status==="OK") {
								showTableResults(schemaName, tableName, msg.msg);
							} else {
								console.error("Communication error on table " + tableName + ":", msg);
								showError(schemaName, tableName);
							}
						}
					);
				}

				function replaceTable(schemaName, tableName, searchString, searchCase, replaceString) {
					jQuery.ajax({
						method: "POST",
						url: "<?php echo admin_url( 'admin-ajax.php?action=wpda_global_replace' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>",
						data: {
							n: "<?php echo esc_attr( wp_create_nonce( self::NONCE_SEED . WPDA::get_current_user_login() ) ); ?>",
							sn: schemaName,
							tn: tableName,
							q: searchString,
							c: searchCase,
							r: replaceString
						}
					}).done(
						function(msg) {
							if (msg.status==="OK") {
								wpda_alert("Update results", "Updated " + msg.msg + " rows in table " + tableName + ".")
							} else {
								console.error("Communication error on table " + tableName + ":", msg);
								showError(schemaName, tableName);
							}
						}
					);
				}

				function startReplace() {
					if (jQuery("#replace_searchbox").val().trim()==="") {
						wpda_alert("Oops!", "Nothing to search. Please enter a search string in the search box above.");
						return;
					}

					startSearch(true);
				}

				function startSearch(addReplaceButtons = false) {
					if (jQuery("#searchbox").val().trim()==="") {
						wpda_alert("Oops!", "Nothing to search. Please enter a search string in the search box above.");
						return;
					} else {
						jQuery("#wpda_searchresults").empty();
						totalMatches = 0;
						updateTotalMatches(0);

						if (jQuery("#selectionDatabases .selectionFrameBody div input[type=checkbox]:checked").length>0) {
							jQuery("#wpda_searchresults_panel").show();
						} else {
							wpda_alert("No tables selected", "Please select one or more tables and try again.");
						}

						let searchString = jQuery("#searchbox").val();
						let searchCase = jQuery("#searchbox-case").is(":checked");
						let replaceString = jQuery("#replace_replacebox").val();
						jQuery("#selectionDatabases .selectionFrameBody div input[type=checkbox]:checked").each(function (i, elem) {
							schemaName = jQuery(elem).closest("div").attr("id");
							showSchemaOutput(schemaName, i, addReplaceButtons, searchString, replaceString);

							for (tableName in wpdaTables[schemaName]) {
								if (wpdaTables[schemaName][tableName]) {
									showTableOutput(schemaName, tableName, addReplaceButtons, searchString, searchCase, replaceString)
									searchTable(schemaName, tableName, searchString, searchCase);
								}
							}
						});
					}
				}

				function toggleAllTables() {
					let isChecked = jQuery("#toggleAllTables").is(":checked");
					jQuery("#selectionTables .selectionFrameBody input[type=checkbox]").prop(
						"checked",
						isChecked
					);

					let schemaName = jQuery(".databaseSelected").attr("id");

					for (tableName in wpdaTables[schemaName]) {
						wpdaTables[schemaName][tableName] = isChecked;
					}

					jQuery("#chk_" + cleanId(schemaName)).prop(
						"checked",
						jQuery("#selectionTables .selectionFrameBody input[type=checkbox]:checked").length>0
					);

					if (!isChecked) {
						jQuery("#toggleWordPressTables").prop("checked", false);
					}
				}

				function toggleWordPressTables() {
					if (jQuery("#toggleAllTables").is(":checked")) {
						return; // nothing to do
					}

					let isChecked = jQuery("#toggleWordPressTables").is(":checked");
					let jumpTo = false;
					jQuery("#selectionTables .selectionFrameBody input[type=checkbox]").each(function() {
						let tableName = jQuery(this).attr("id");
						if (wpTables.includes(tableName)) {
							wpdaTables[wpDatabase][tableName] = isChecked;
							jQuery("#" + tableName).prop("checked", isChecked);
							if (isChecked && !jumpTo) {
								jQuery("#" + tableName).focus();
								jumpTo = true;
							}
						}
					});

					jQuery("#chk_" + wpDatabase).prop(
						"checked",
						jQuery("#selectionTables .selectionFrameBody input[type=checkbox]:checked").length>0
					);
				}

				function toggleTable(schemaName, tableName) {
					if (event.target.type!=="checkbox") {
						wpdaTables[schemaName][tableName] = !jQuery("#" + tableName).prop("checked");
						jQuery("#" + tableName).prop("checked", wpdaTables[schemaName][tableName]);
					} else {
						wpdaTables[schemaName][tableName] = jQuery("#" + tableName).prop("checked");
					}

					if (wpdaTables[schemaName][tableName]) {
						// At least one table selected: enable search for current database
						jQuery("#chk_" + cleanId(schemaName)).prop("checked", true);
					} else {
						if (jQuery("#selectionTables .selectionFrameBody input[type=checkbox]:checked").length===0) {
							// No tables selected: disable search for current database
							jQuery("#chk_" + cleanId(schemaName)).prop("checked", false);
						}
					}
				}

				function selectSchema(schemaName) {
					if (wpDatabase===schemaName) {
						jQuery("#showWordPressTables").show();
					} else {
						jQuery("#showWordPressTables").hide();
					}

					jQuery("#selectionTables .selectionFrameBody").empty();

					let tables = wpdaTables[schemaName];
					if (tables===undefined) {
						alert("No tables found for schema " + schemaName);
					} else {
						for (tableName in tables) {
							let checked = tables[tableName] ? 'checked' : '';
							let newTable = `
								<div class="selectionFrameBodyElement"
									 onclick="toggleTable('${schemaName}', '${tableName}')"
								>
									<input type="checkbox" id="${tableName}" ${checked} class="selectionTable" />
									${tableName}
								</div>`;
							jQuery("#selectionTables .selectionFrameBody").append(newTable);
						}

						jQuery("#selectionDatabases .databaseSelected").removeClass("databaseSelected");
						jQuery("#" + cleanId(schemaName)).addClass("databaseSelected");

						jQuery("#selectionTables .selectionFrameHeader input[type=checkbox]").prop("checked", false);
					}
				}

				function getTables(schemaName) {
					var url = location.pathname + '?action=wpda_get_tables';
					var data = {
						wpdaschema_name: schemaName,
						wpda_wpnonce: '<?php echo esc_attr( wp_create_nonce( 'wpda-getdata-access-' . WPDA::get_current_user_login() ) ); ?>'
					};
					jQuery.post(
						url,
						data,
						function (data) {
							try {
								let testIfJson = JSON.parse(data);
								if (typeof testIfJson == "object") {
									let tables = [];
									jQuery.each(testIfJson, function (i, item) {
										tables[item.table_name] = false;
									});
									wpdaTables[schemaName] = tables;
								} else {
									console.error("Invalid table list for database " + schemaName, testIfJson);
								}
							} catch {
								console.error("Invalid table list for database " + schemaName, data);
							} finally {
								schemasProcessed++;
								if (schemasProcessed===wpdaDatabases.length) {
									jQuery(".selectionFrameBodySpinner").remove();
								}
							}
						}
					);
				}

				function getDatabases() {
					for (let i=0; i<wpdaDatabases.length; i++) {
						getTables(wpdaDatabases[i]);
					}
				}

				function enableTooltip() {
					jQuery(".wpda_tooltip_left").tooltip({
						tooltipClass: "wpda_tooltip_dashboard",
						track: true
					});
					jQuery(".wpda_tooltip_sar").tooltip({
						tooltipClass: "wpda_tooltip_sar_css",
					});
				}

				jQuery(function() {
					jQuery("#tabs").tabs();

					getDatabases();
					enableTooltip();
				});
			</script>
			<?php
		}

		public static function search() {
			self::check_request();

			$schema_name  = sanitize_text_field( wp_unslash( $_POST['sn'] ) ); // phpcs:ignore WordPress.Security.NonceVerification
			$table_name   = sanitize_text_field( wp_unslash( $_POST['tn'] ) ); // phpcs:ignore WordPress.Security.NonceVerification
			$search_value = sanitize_text_field( wp_unslash( $_POST['q'] ) ); // phpcs:ignore WordPress.Security.NonceVerification

			$wpdadb = WPDADB::get_db_connection( $schema_name );
			if ( null !== $wpdadb ) {
				$wpdadb->suppress_errors( true );
			}

			// Get column info.
			$wpda_list_columns = WPDA_List_Columns_Cache::get_list_columns( $schema_name, $table_name );
			$columns           = $wpda_list_columns->get_table_columns();
			if ( ! is_array( $columns ) ) {
				// Table not found.
				WPDA::sent_header( 'application/json' );
				WPDA::sent_msg( 'ERROR', 'Internal SQL error' );
				die();
			}

			// Determine case-sensitive search
			$search_case = 'true' === $_POST['c'];

			// Perform query
			$result = self::execute_query( $wpdadb, $schema_name, $table_name, $columns, $search_value, $search_case, true );

			// Process query results
			if ( '' === $wpdadb->last_error && is_array( $result ) && count( $result ) > 0 ) {//phpcs:ignore - 8.1 proof
				WPDA::sent_header( 'application/json' );
				WPDA::sent_msg( 'OK', $result[0][0] );
				die();
			}

			WPDA::sent_header( 'application/json' );
			WPDA::sent_msg( 'ERROR', $wpdadb->last_error );
			die();
		}

		private static function execute_query( $wpdadb, $schema_name, $table_name, $columns, $search_value, $search_case, $just_count = false ) {
            if ( 'rdb:' === substr( $schema_name, 0, 4) ) {
                // Remote database
                $query = true === $just_count ?
                    'select count(*) from `%1s`' : 'select * from `%1s`';

                // Define query.
                $query = $wpdadb->prepare(
                    $query,
                    array(
                        WPDA::remove_backticks( $table_name ),
                    )
                );
            } else {
                // Local database
                $query = true === $just_count ?
                    'select count(*) from `%1s`.`%1s`' : 'select * from `%1s`.`%1s`';

                // Define query.
                $query = $wpdadb->prepare(
                    $query,
                    array(
                        WPDA::remove_backticks( $schema_name ),
                        WPDA::remove_backticks( $table_name ),
                    )
                );
            }

			// Construct where clause.
			$where = WPDA::construct_where_clause(
				$schema_name,
				$table_name,
				$columns,
				$search_value,
				$search_case
			);
			if ( trim( $where ) !== '' ) {
				$query .= " where {$where} ";
			}

			// Perform query.
			return $wpdadb->get_results( $query, ( true === $just_count ? 'ARRAY_N' : 'ARRAY_A' ) );
		}

		public static function replace() {
			self::check_request();

			if ( ! isset( $_POST['r'] ) ) {
				WPDA::sent_header( 'application/json' );
				WPDA::sent_msg( 'ERROR', 'Invalid arguments' );
				die();
			}

			$schema_name   = sanitize_text_field( wp_unslash( $_POST['sn'] ) ); // phpcs:ignore WordPress.Security.NonceVerification
			$table_name    = sanitize_text_field( wp_unslash( $_POST['tn'] ) ); // phpcs:ignore WordPress.Security.NonceVerification
			$search_value  = sanitize_text_field( wp_unslash( $_POST['q'] ) ); // phpcs:ignore WordPress.Security.NonceVerification
			$replace_value = sanitize_text_field( wp_unslash( $_POST['r'] ) ); // phpcs:ignore WordPress.Security.NonceVerification

			$wpdadb = WPDADB::get_db_connection( $schema_name );
			if ( null !== $wpdadb ) {
				$wpdadb->suppress_errors( true );
			}

			// Get column info.
			$wpda_list_columns = WPDA_List_Columns_Cache::get_list_columns( $schema_name, $table_name );
			$columns           = $wpda_list_columns->get_table_columns();
			if ( ! is_array( $columns ) ) {
				// Table not found.
				WPDA::sent_header( 'application/json' );
				WPDA::sent_msg( 'ERROR', 'Internal SQL error' );
				die();
			}

			// Determine case-sensitive search
			if ( 'true' === $_POST['c'] ) {
				// Case-sensitive search and replace
				// Use built-in SQL replace function

				// Define query.
				$query = $wpdadb->prepare(
					'update `%1s`.`%1s` set ', // phpcs:ignore WordPress.DB.PreparedSQLPlaceholders
					array(
						WPDA::remove_backticks( $schema_name ),
						WPDA::remove_backticks( $table_name ),
					)
				);

				// Add columns to be updated.
				$set = '';
				foreach ( $columns as $column ) {
					if ( 'string' === WPDA::get_type( $column['data_type'] ) ) {
						if ( '' !== $set ) {
							$set .= ',';
						}
						$set .= $wpdadb->prepare(
							" `%1s` = replace( `%1s`, '%s', '%s' ) ",
							array(
								WPDA::remove_backticks( $column['column_name'] ),
								WPDA::remove_backticks( $column['column_name'] ),
								$search_value,
								$replace_value
							)
						);
					}
				}
				if ( '' === $set ) {
					WPDA::sent_header( 'application/json' );
					WPDA::sent_msg( 'ERROR', 'No updatable columns found in this table' );
					die();
				}

				$query .= $set;

				// Construct where clause.
				$where = WPDA::construct_where_clause(
					$schema_name,
					$table_name,
					$columns,
					$search_value
				);
				if ( trim( $where ) !== '' ) {
					$query .= " where {$where} ";
				}

				// Perform update.
				$wpdadb->get_results( $query, 'ARRAY_A' );
				if ( '' === $wpdadb->last_error ) {
					WPDA::sent_header( 'application/json' );
					WPDA::sent_msg( 'OK', $wpdadb->rows_affected );
					die();
				}
			} else {
				// Case-insensitive search and replace
				// There is no standard SQL function to replace case-insensitive, so we need to implement our own solution

				// Determine updatable columns
				$update_columns = array();
				foreach ( $columns as $column ) {
					if ( 'string' === WPDA::get_type( $column['data_type'] ) ) {
						$update_columns[] = $column['column_name'];
					}
				}

				// Determine primary key for record update
				$pk			   = $wpda_list_columns->get_table_primary_key();
				$rows_affected = 0;

				// Perform query
				$results = self::execute_query( $wpdadb, $schema_name, $table_name, $columns, $search_value, false );
				foreach ( $results as $result ) {
					$update_values = array();
					$pk_values     = array();

					foreach ( $update_columns as $update_column ) {
						if ( false !== stripos( $result[ $update_column ], $search_value ) ) {
							$update_values[ $update_column ] = str_ireplace( $search_value, $replace_value, $result[ $update_column ] );
							foreach ( $pk as $key ) {
								$pk_values[ $key ] = $result[ $key ];
							}
						}
					}

					// Update row
					$wpdadb->update(
						$table_name,
						$update_values,
						//phpcs:ignore - 8.1 proof
						( is_array( $pk ) && count( $pk ) > 0 ? $pk_values : $result ) // fall back to all cols if no pk
					);
					$rows_affected += $wpdadb->rows_affected;
				}

				WPDA::sent_header( 'application/json' );
				WPDA::sent_msg( 'OK', $rows_affected );
				die();
			}

			WPDA::sent_header( 'application/json' );
			WPDA::sent_msg( 'ERROR', $wpdadb->last_error );
			die();
		}

		private static function check_request() {
			$wpnonce = isset( $_POST['n'] ) ? sanitize_text_field( wp_unslash( $_POST['n'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
			if ( ! wp_verify_nonce( $wpnonce, self::NONCE_SEED . WPDA::get_current_user_login() ) ) {
				WPDA::sent_header( 'application/json' );
				WPDA::sent_msg( 'ERROR', 'Not authorized' );
				die();
			}

			if ( ! isset( $_POST['sn'], $_POST['tn'], $_POST['q'], $_POST['c'] ) || '' === $_POST['sn'] || '' === $_POST['tn'] ) {
				WPDA::sent_header( 'application/json' );
				WPDA::sent_msg( 'ERROR', 'Invalid arguments' );
				die();
			}

			if ( '' === $_POST['q'] ) {
				WPDA::sent_header( 'application/json' );
				WPDA::sent_msg( 'ERROR', 'Nothing to search' );
				die();
			}
		}

	}

}
