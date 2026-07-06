function wpda_show_table_actions( schema_name, table_name, rownum, wpnonce, dbo_type, loading ) {
    jQuery('#wpda_admin_menu_actions_' + rownum).toggle();
    wpda_toggle_row_actions(rownum);
    if (jQuery('#wpda_admin_menu_actions_' + rownum).html()===loading) {
        url = location.pathname + '?action=wpda_show_table_actions';
        jQuery.ajax({
            method: 'POST',
            url: url,
            data: {
				wpdaschema_name: schema_name,
				table_name: table_name,
				_wpnonce: wpnonce,
				dbo_type: dbo_type,
				rownum: rownum
			}
        }).done(
            function(msg) {
                jQuery('#wpda_admin_menu_actions_' + rownum).html(msg);
            }
        );
    }
}

function wpda_toggle_row_actions( rownum ) {
    if (jQuery('#wpda_admin_menu_actions_' + rownum).is(":visible")) {
        jQuery("#rownum_" + rownum + " td div").removeClass("row-actions");
	} else {
        jQuery("#rownum_" + rownum + " td div").addClass("row-actions");
	}
}

function wpda_list_table_favourite( schema_name, table_name )  {
	if (jQuery('#span_favourites_'+ table_name).hasClass('dashicons-star-empty')) {
		action = 'wpda_add_favourite';
	} else {
		action = 'wpda_rem_favourite';
	}
	url = location.pathname + '?action=' + action;
	jQuery.ajax({
		method: 'POST',
		url: url,
		data: {
			wpdaschema_name: schema_name,
			table_name: table_name
		}
	}).done(
		function (msg) {
			if (msg === '1') {
				if (jQuery('#span_favourites_' + table_name).hasClass('dashicons-star-empty')) {
					jQuery('#span_favourites_' + table_name)
					.removeClass('dashicons-star-empty')
					.addClass('dashicons-star-filled')
					.prop('title', 'Remove from favourites');
				} else {
					jQuery('#span_favourites_' + table_name)
					.removeClass('dashicons-star-filled')
					.addClass('dashicons-star-empty')
					.prop('title', 'Add to favourites');
				}
				if (jQuery('#wpda_main_favourites_list').val()!=='') {
                    jQuery("#wpda_main_form :input[name='action']").val('-1');
                    jQuery("#wpda_main_form :input[name='action2']").val('-1');
					jQuery('#wpda_main_form').submit();
				}
			} else {
				alert('Adding to favourites failed!');
			}
		}
	);
}

function wpda_main_export_start() {
	jQuery("#wpda_main_export_form").submit();
	jQuery("#wpda_row_export_anchor").empty();

	return false;
}

function wpda_main_export(schemaName, wpnonce, selectedRows) {
	jQuery("#wpda_main_export_form__schema_name").val(schemaName);
	jQuery("#wpda_main_export_form__wpnonce").val(wpnonce);

	rows = JSON.parse(selectedRows);
	for (var i=0; i<rows.length; i++) {
		jQuery("#wpda_main_export_form").append("<input type='hidden' name='table_names[]' value='" + rows[i] + "' />");
	}

	jQuery("#wpda_row_export_anchor").append(
		"<button class='button button-primary wpda_row_export_button' onclick='return wpda_main_export_start();'>Download export</button>"
	);
}

function wpda_table_export(schemaName, tableNames, wpnonce, formatType, includeTableSettings) {
	jQuery("#wpda_table_export_form__schema_name").val(schemaName);
	jQuery("#wpda_table_export_form__table_names").val(tableNames);
	jQuery("#wpda_table_export_form__format_type").val(formatType);
	jQuery("#wpda_table_export_form__wpnonce").val(wpnonce);
	jQuery("#wpda_table_export_form__include_table_settings").val(includeTableSettings);

	jQuery("#wpda_table_export_form").submit();
}

function wpda_row_export_start() {
	jQuery("#wpda_row_export_form").submit();
	jQuery("#wpda_row_export_anchor").empty();

	return false;
}

function wpda_row_export(pid, schemaName, tableNames, wpnonce, formatType, mysqlSet, showCreate, showComments, selectedRows) {
	jQuery("#wpda_row_export_form__pid").val(pid);
	jQuery("#wpda_row_export_form__schema_name").val(schemaName);
	jQuery("#wpda_row_export_form__table_names").val(tableNames);
	jQuery("#wpda_row_export_form__mysql_set").val(mysqlSet);
	jQuery("#wpda_row_export_form__show_create").val(showCreate);
	jQuery("#wpda_row_export_form__show_comments").val(showComments);
	jQuery("#wpda_row_export_form__format_type").val(formatType);
	jQuery("#wpda_row_export_form__wpnonce").val(wpnonce);

	rows = JSON.parse(selectedRows);
	for (var column in rows) {
		for (var i=0; i<rows[column].length; i++) {
			jQuery("#wpda_row_export_form").append("<input type='hidden' name='" + column + "[]' value='" + rows[column][i] + "' />");
		}
	}

	jQuery("#wpda_row_export_anchor").append(
		"<button class='button button-primary wpda_row_export_button' onclick='return wpda_row_export_start();'>Download " + (formatType==='' ? 'SQL' : formatType) + " export</button>"
	);
}

function wpda_show_notice( value ) {
    if (
    	value==='bulk-delete' ||
		value==='bulk-drop' ||
		value==='bulk-truncate'
	) {
        return confirm('You are about to permanently delete these items from your site.\nThis action cannot be undone.\n\'Cancel\' to stop, \'OK\' to delete.');
    }
}

function wpda_action_button() {
    action1 = jQuery("#wpda_main_form :input[name='action']").val();
    if (action1!=="-1") {
		return wpda_show_notice(action1);
    }
    action2 = jQuery("#wpda_main_form :input[name='action2']").val();
    if (action2!=="-1") {
        return wpda_show_notice(action2);
    }
    return true;
}

// Source: https://stackoverflow.com/questions/24816/escaping-html-strings-with-jquery
var entityMap = {
	'&': '&amp;',
	'<': '&lt;',
	'>': '&gt;',
	'"': '&quot;',
	"'": '&#39;',
	'/': '&#x2F;',
	'`': '&#x60;',
	'=': '&#x3D;'
};

// Source: https://stackoverflow.com/questions/24816/escaping-html-strings-with-jquery
function escapeHtml(string) {
	return String(string).replace(/[&<>"'`=\/]/g, function (s) {
		return entityMap[s];
	});
}

function submit_table_settings(rownum, schema_name, table_name) {
	table_settings = {};

	if (jQuery("input:radio[name ='" + table_name + "_row_count_estimate']:checked").val() === "true") {
		table_settings['row_count_estimate'] = true;
	} else if (jQuery("input:radio[name ='" + table_name + "_row_count_estimate']:checked").val() === "false") {
		table_settings['row_count_estimate'] = false;
	} else {
		table_settings['row_count_estimate'] = null;
	}

	table_settings['row_count_estimate_value'] = jQuery("input:radio[name ='" + table_name + "_row_count_estimated_value']:checked").val();
	table_settings['row_count_estimate_value_hard'] = jQuery("#" + table_name + "_row_count_estimated_value_hard").val();

	table_settings['query_buffer_size'] =
		jQuery('#' + table_name + '_query_buffer_size').val();

	table_settings['row_level_security'] =
		jQuery('#' + table_name + '_row_level_security').is(':checked') ? 'true' : 'false';

	table_settings['hyperlink_definition'] =
		jQuery('#' + table_name + 'table_top_setting_hyperlink_definition_json').is(':checked') ? 'json' : 'text';

	jsonData = {};
	jsonData['request_type'] = 'table_settings';
	jsonData['table_settings'] = table_settings;
	jsonData['unused'] = jQuery('#wpda_' + rownum + '_sql_dml').val();

	// console.log(jsonData);
	// console.log(JSON.stringify(jsonData));

	// Submit
	wpda_rest_api(
		'save-settings',
		{
			action: 'table_settings',
			dbs: schema_name,
			tbl: table_name,
			settings: JSON.stringify(jsonData),
		},
		(function(data) {
			submitSettingsCallbackOk(data, rownum);
		}),
		submitSettingsCallbackError
	);

	return false;
}

function submitSettingsCallbackOk(response, rownum) {
	if (response.code!==undefined && response.message!==undefined) {
		switch(response.code) {
			case "ok":
				jQuery("#wpda_" + rownum + "_sql_dml").val("UPDATE");
				jQuery.notify(response.message, "success");
				break;
			case "error":
				jQuery.notify(response.message, "error");
				break;
			default:
				jQuery.notify("Application error! Please contact the plugin development team.", "error");
		}
	} else {
		submitSettingsCallbackError(response);
	}
}

function submitSettingsCallbackError(response) {
	if (response.code!==undefined && response.message!==undefined) {
		switch(response.code) {
			case "error":
				jQuery.notify(response.message, "error");
				break;
			default:
				jQuery.notify("Application error! Please contact the plugin development team.", "error");
		}
	} else {
		jQuery.notify("Application error! Please contact the plugin development team.", "error");
	}
}

function submit_column_settings(rownum, schema_name, table_name ) {
	custom_settings = {};
	list_labels = {};
	form_labels = {};
	column_media = {};
	unused = {};

	jQuery('#wpda_table_settings_' + rownum + ' .wpda_table_setting_item').each(function () {
		element_id = jQuery(this).attr('id');
		element_value = jQuery(this).val();
		element_type = jQuery(this).attr('type');
		is_custom_element = false;
		for (i=0; i<custom_column_settings.length; i++) {
			if (element_id.startsWith(custom_column_settings[i])) {
				element_name = element_id.substr(custom_column_settings[i].length);
				if (element_type==="checkbox") {
					element_value = jQuery(this).is(':checked');
				}
				custom_settings[element_id] = element_value;
				is_custom_element = true;
			}
		}
		if (!is_custom_element) {
			if (element_id.startsWith("list_label_")) {
				element_name = element_id.substr(11);
				list_labels[element_name] = element_value;
			} else if (element_id.startsWith("form_label_")) {
				element_name = element_id.substr(11);
				form_labels[element_name] = element_value;
			} else if (element_id.startsWith("column_media_")) {
				element_name = element_id.substr(13);
				element_old_value = jQuery('#column_media_' + element_name + '_old').val();
				if (element_value !== element_old_value) {
					media_dml = jQuery('#column_media_' + element_name + '_dml').val();
					column_media[element_name] = {'value': element_value, 'dml': media_dml};
				}
			}
		}
	});

	unused["sql_dml"] = jQuery('#wpda_' + rownum + '_sql_dml').val();

	jsonData = {};
	jsonData['request_type'] = 'column_settings';
	jsonData['custom_settings'] = custom_settings;
	jsonData['list_labels'] = list_labels;
	jsonData['form_labels'] = form_labels;
	jsonData['column_media'] = column_media;
	jsonData['unused'] = unused;

	// console.log(jsonData);
	// console.log(JSON.stringify(jsonData));

	// Submit
	wpda_rest_api(
		'save-settings',
		{
			action: 'column_settings',
			dbs: schema_name,
			tbl: table_name,
			settings: JSON.stringify(jsonData),
		},
		(function(data) {
			submitSettingsCallbackOk(data, rownum);
		}),
		submitSettingsCallbackError
	);

	return false;
}

function submit_hyperlinks(rownum, schema_name, table_name) {
	hyperlinks = [];
	unused = {};
	isvalid = true;

	jQuery("#wpda_" + rownum + "_add_hyperlink_body tr").each(function() {
		let hyperlink_label, hyperlink_list, hyperlink_form, hyperlink_target, hyperlink_html;
		jQuery(this).find("td .wpda_action_font").each(function() {
			switch(jQuery(this).data("id")) {
				case "hyperlink_label":
					hyperlink_label = jQuery(this).val();
					break;
				case "hyperlink_list":
					hyperlink_list = jQuery(this).is(':checked');
					break;
				case "hyperlink_form":
					hyperlink_form = jQuery(this).is(':checked');
					break;
				case "hyperlink_target":
					hyperlink_target = jQuery(this).is(':checked');
					break;
				case "hyperlink_html":
					hyperlink_html = jQuery(this).val();
					break;
			}
		});

		if (hyperlink_label===""||hyperlink_html==="") {
			alert('Hyperlink label and HTML must be entered');
			isvalid = false;
		} else {
			let hyperlink_item = {
				'hyperlink_label': hyperlink_label,
				'hyperlink_list': hyperlink_list,
				'hyperlink_form': hyperlink_form,
				'hyperlink_target': hyperlink_target,
				'hyperlink_html': escapeHtml(hyperlink_html)
			};
			hyperlinks.push(hyperlink_item);
		}
	});

	if (!isvalid) {
		return false;
	}

	unused["sql_dml"] = jQuery('#wpda_' + rownum + '_sql_dml').val();

	jsonData = {};
	jsonData['request_type'] = 'column_settings';
	jsonData['hyperlinks'] = hyperlinks;
	jsonData['unused'] = unused;

	// console.log(jsonData);
	// console.log(JSON.stringify(jsonData));

	// Submit
	wpda_rest_api(
		'save-settings',
		{
			action: 'column_settings',
			dbs: schema_name,
			tbl: table_name,
			settings: JSON.stringify(jsonData),
		},
		(function(data) {
			submitSettingsCallbackOk(data, rownum);
		}),
		submitSettingsCallbackError
	);

	return false;
}

function submit_dashboard_menus(rownum, schema_name, table_name) {
	menu = [];
	isvalid = true;

	jQuery("#wpda_" + rownum + "_add_dashboard_menu_body tr").each(function() {
		let menu_name, menu_slug, menu_role, menu_id;
		jQuery(this).find("td .wpda_action_font").each(function() {
			switch(jQuery(this).data("id")) {
				case "menu_name":
					menu_name = jQuery(this).val();
					break;
				case "menu_slug":
					menu_slug = jQuery(this).val();
					break;
				case "menu_role":
					menu_role = jQuery(this).val();
					break;
				case "menu_id":
					menu_id = jQuery(this).val();
					break;
			}
		});

		if (menu_name===""||menu_slug==="") {
			alert('Menu name and slug must be entered');
			isvalid = false;
		} else {
			let menu_item = {
				'menu_name': menu_name,
				'menu_slug': menu_slug,
				'menu_role': menu_role,
				'menu_id': menu_id
			};
			menu.push(menu_item);
		}
	});

	if (!isvalid) {
		return false;
	}

	jsonData = {};
	jsonData['request_type'] = 'dashboard_menus';
	jsonData['menu'] = menu;

	if (wpdaDeletedDashboardMenus[rownum]!==undefined) {
		jsonData['delete'] = wpdaDeletedDashboardMenus[rownum];
	}

	// console.log(jsonData);
	// console.log(JSON.stringify(jsonData));

	// Submit
	wpda_rest_api(
		'save-settings',
		{
			action: 'dashboard_menus',
			dbs: schema_name,
			tbl: table_name,
			settings: JSON.stringify(jsonData),
		},
		(function(data) {
			submitMenusCallbackOk(data, rownum);
		}),
		submitSettingsCallbackError
	);

	return false;
}

function submitMenusCallbackOk(response, rownum) {
	if (response.code!==undefined && response.message!==undefined) {
		switch(response.code) {
			case "ok":
				jQuery.notify(response.message, "success");
				wpdaDeletedDashboardMenus[rownum] = [];
				if (response.data!==undefined && Array.isArray(response.data)) {
					for (let i=0; i<response.data.length; i++) {
						jQuery("#wpda_" + rownum + "_add_dashboard_menu_body tr .wpda_dashboard_menu_id").each(function() {
							if (jQuery(this).val()==="") {
								jQuery(this).val(response.data[i].menu_id);
							}
						});
					}
				}
				break;
			case "error":
				jQuery.notify(response.message, "error");
				break;
			default:
				jQuery.notify("Application error! Please contact the plugin development team.", "error");
		}
	} else {
		submitSettingsCallbackError(response);
	}
}

function settab(rownum, tab) {
	for (i = 1; i <= 6; i++) {
		jQuery("#" + rownum + "-sel-" + i.toString()).removeClass('nav-tab-active');
		jQuery("#" + rownum + "-tab-" + i.toString()).hide();
	}
	jQuery("#" + rownum + "-sel-" + tab).addClass('nav-tab-active');
	jQuery("#" + rownum + "-tab-" + tab).show();
}

function backup_respository_tables(
	app_table_name,
	app_container_table_name,
	app_apps_table_name,
	table_settings_table_name,
	media_table_name,
	design_table_name,
	data_projects_project_name,
	data_projects_page_name,
	data_projects_table_name,
	data_publication_table_name,
	menus_table_name,
	csv_import_table_name,
	logging_table_name
) {
	jQuery('#app_table_name').val(app_table_name);
	jQuery('#app_container_table_name').val(app_container_table_name);
	jQuery('#app_apps_table_name').val(app_apps_table_name);
	jQuery('#table_settings_table_name').val(table_settings_table_name);
	jQuery('#media_table_name').val(media_table_name);
	jQuery('#design_table_name').val(design_table_name);
	jQuery('#data_projects_project_name').val(data_projects_project_name);
	jQuery('#data_projects_page_name').val(data_projects_page_name);
	jQuery('#data_projects_table_name').val(data_projects_table_name);
	jQuery('#data_publication_table_name').val(data_publication_table_name);
	jQuery('#menus_table_name').val(menus_table_name);
	jQuery('#csv_import_table_name').val(csv_import_table_name);
	jQuery('#logging_table_name').val(logging_table_name);

	jQuery('#wpda-download-backup').submit();
}

function restore_respository_tables() {
	if (!confirm("Restore repository tables? This action cannot be undone!\n\nIt might be wise to backup your actual repository tables first...")) {
		return;
	}

	jQuery('#restore_app_table_name').val(jQuery("input[name='restore_app_table_name']:checked").val());
	jQuery('#restore_app_container_table_name').val(jQuery("input[name='restore_app_container_table_name']:checked").val());
	jQuery('#restore_app_apps_table_name').val(jQuery("input[name='restore_app_apps_table_name']:checked").val());
	jQuery('#restore_table_settings_table_name').val(jQuery("input[name='restore_table_settings_table_name']:checked").val());
	jQuery('#restore_media_table_name').val(jQuery("input[name='restore_media_table_name']:checked").val());
	jQuery('#restore_design_table_name').val(jQuery("input[name='restore_design_table_name']:checked").val());
	jQuery('#restore_data_projects_project_name').val(jQuery("input[name='restore_data_projects_project_name']:checked").val());
	jQuery('#restore_data_projects_page_name').val(jQuery("input[name='restore_data_projects_project_name']:checked").val());
	jQuery('#restore_data_projects_table_name').val(jQuery("input[name='restore_data_projects_table_name']:checked").val());
	jQuery('#restore_data_publication_table_name').val(jQuery("input[name='restore_data_publication_table_name']:checked").val());
	jQuery('#restore_menus_table_name').val(jQuery("input[name='restore_menus_table_name']:checked").val());
	jQuery('#restore_csv_import_table_name').val(jQuery("input[name='restore_csv_import_table_name']:checked").val());
	jQuery('#restore_logging_table_name').val(jQuery("input[name='restore_logging_table_name']:checked").val());

	jQuery('#restore_date').val(jQuery('.wpda-restore-repository-backup-selected').data('backupDate'));

	jQuery('#wpda-restore-respository').submit();
}

function get_table_row_count(wpnonce, schema_name, table_name) {
	url = location.pathname + '?action=wpda_get_table_row_count';
	jQuery.ajax({
		method: 'POST',
		url: url,
		data: {
			wpda_wpnonce: wpnonce,
			wpdaschema_name: schema_name,
			wpdatable_name: table_name
		}
	}).done(
		function(msg) {
			try {
				json = JSON.parse(msg);
				if (json[0].row_count === undefined) {
					alert('Ajax error: row count failed');
				} else {
					jQuery("#" + table_name + "_row_count_estimated_value_hard").val(json[0].row_count);
				}
			} catch (e) {
				alert('Ajax error: ' + e.message);
			}
		}
	);
}

function expandFieldset(id) {
	if (jQuery("#wpda_table_expand_" + id).is(":visible")) {
		jQuery("#wpda_field_expand_" + id).removeClass("fa-minus-circle");
		jQuery("#wpda_field_expand_" + id).addClass("fa-plus-circle");
		jQuery("#wpda_fieldset_expand_" + id).val("off");
		jQuery("#wpda_table_expand_" + id).css("display", "none");
	} else {
		jQuery("#wpda_field_expand_" + id).removeClass("fa-plus-circle");
		jQuery("#wpda_field_expand_" + id).addClass("fa-minus-circle");
		jQuery("#wpda_fieldset_expand_" + id).val("on");
		jQuery("#wpda_table_expand_" + id).css("display", "table");
	}
}

function wpda_add_icons_to_dialog_buttons() {
	jQuery("div.ui-dialog div.ui-dialog-buttonset button").each(function() {
		switch(jQuery(this).text()) {
			case "OK":
			case "Select":
			case "Close":
				jQuery(this).prepend('<i class="fas fa-check wpda_icon_on_button"></i> ');
				break;
			case "Apply":
				jQuery(this).prepend('<i class="fas fa-code-commit wpda_icon_on_button"></i> ');
				break;
			case "Delete":
				jQuery(this).prepend('<i class="fas fa-trash wpda_icon_on_button"></i> ');
				break;
			case "Keep":
				jQuery(this).prepend('<i class="fas fa-eraser wpda_icon_on_button"></i> ');
				break;
			case "Cancel":
				jQuery(this).prepend('<i class="fas fa-times-circle wpda_icon_on_button"></i> ');
				break;
		}
	});
}

function wpda_dbinit_admin( schema_name, wpnonce ) {
	jQuery.ajax({
		method: 'POST',
		url: wpda_admin_vars.wpda_ajaxurl + '?action=wpda_dbinit_admin',
		data: {
			wpdaschema_name: schema_name,
			wpnonce: wpnonce,
		}
	}).done(
		function(data) {
			if (typeof data === "string") {
				msg = JSON.parse(data);
			} else {
				msg = data;
			}
			if (msg.status==="OK") {
				jQuery.notify('Database function wpda_get_wp_user_id() created', 'success');
			} else {
				console.log(msg);
				jQuery.notify(msg.msg, 'error');
			}
		}
	);
}

function wpda_toggle_password(id, e) {
	// Change input type
	let elm = document.getElementById(id);
	if (elm.type === "password") {
		elm.type = "text";
	} else {
		elm.type = "password";
	}

	// Change icon if applicable
	let icn = jQuery(e.target);
	if (icn.hasClass("fa-eye")) {
		icn.addClass("fa-eye-slash").removeClass("fa-eye");
	} else if (icn.hasClass("fa-eye-slash")) {
		icn.addClass("fa-eye").removeClass("fa-eye-slash");
	}
}

function wpda_alert(title, txt) {
	jQuery("<p></p>").append(txt).dialog({
		title: title,
		modal: true,
		buttons: {
			OK: function() {
				jQuery(this).dialog("close");
			}
		}
	});
}

function wpda_confirm(title, txt) {
	var defer = jQuery.Deferred();

	jQuery("<p></p>").append(txt).dialog({
		title: title,
		modal: true,
		buttons: {
			OK: function() {
				defer.resolve(true);
				jQuery(this).dialog("close");
			},
			CANCEL: function() {
				defer.resolve(false);
				jQuery(this).dialog("close");
			}
		}
	});

	return defer.promise();
}

function restApiPanelOnChange(elem, panel) {
	if (elem.is(":checked")) {
		panel.show();
	} else {
		panel.hide();
	}
}

function restApiAccessOnChange(elem, access) {
	if (elem.val() === "anonymous") {
		access.hide();
	} else {
		access.show();
	}
}

function submit_rest_api(schema_name, table_name, settings ) {
	// Submit
	wpda_rest_api(
		'save-settings',
		{
			action: 'rest_api',
			dbs: schema_name,
			tbl: table_name,
			settings: JSON.stringify(settings),
		},
		(function(data) {
			submitSettingsCallbackOk(data, "norownum");
		}),
		submitSettingsCallbackError
	);

	return false;
}

function restApiTestCallbackOk(response, rownum) {
	if (response.code!==undefined && response.message!==undefined) {
		switch(response.code) {
			case "ok":
				jQuery.notify("Please check your console...", "success");
				console.log(response);
				break;
			default:
				jQuery.notify("Please check your console...", "error");
				console.error(response);
		}
	} else {
		restApiTestCallbackError(response);
	}
}

function restApiTestCallbackError(response) {
	if (response.code!==undefined && response.message!==undefined) {
		jQuery.notify("Please check your console...", "error");
		console.error(response);
	} else {
		jQuery.notify("Please check your console...", "error");
	}
}

var wpdaDeletedDashboardMenus = [];
function deleteDashboardMenu(element, rownum) {
	let menu_id = jQuery(element).closest("tr").find(".wpda_dashboard_menu_id").val();
	if (wpdaDeletedDashboardMenus[rownum]===undefined) {
		wpdaDeletedDashboardMenus[rownum] = [];
	}
	wpdaDeletedDashboardMenus[rownum].push(menu_id);
	jQuery(element).closest("tr").remove();
}

wpdaMediaFrames = [];
function wpdaInitMediaItem(mediaId, mediaContainer, mediaItem, mediaType, dataType, title, buttonText, mediaTypes) {
	wpdaMediaFrames[mediaId] = null;

	jQuery("#media_" + mediaId  + "_upload_button").on("click", function (e) {
		e.preventDefault();

		if (wpdaMediaFrames[mediaId]!==null) {
			wpdaMediaFrames[mediaId].open();
			wpdaSetMediaSelection(
				mediaItem,
				wpdaMediaFrames[mediaId]
			);
		} else {
			wpdaMediaFrames[mediaId] = wp.media({
				title: title,
				button: {
					text: buttonText
				},
				library: {
					type: mediaTypes
				},
				multiple: dataType==="number" ? "false" : "true",
				render: false
			});

			wpdaMediaFrames[mediaId].on("select", function () {
				if ('number' === dataType) {
					attachment = wpdaMediaFrames[mediaId].state().get('selection').first().toJSON();
				} else {
					attachment = wpdaMediaFrames[mediaId].state().get('selection').toJSON();
				}

				wpdaGetMedia(
					mediaContainer,
					mediaItem,
					dataType,
					attachment,
					mediaType
				);
			});

			wpdaMediaFrames[mediaId].open();
			wpdaSetMediaSelection(
				mediaItem,
				wpdaMediaFrames[mediaId]
			);
		}
	});

	jQuery("#media_" + mediaId + "_remove_button").on("click", function (e) {
		e.preventDefault();
		mediaContainer.empty();
		mediaItem.val('');
	});
}

function wpdaGetMedia(mediaContainer, mediaItem, dataType, attachment, attachmentType) {
	mediaContainer.empty();
	if ('number' === dataType) {
		mediaItem.val(attachment.id);
		mediaContainer.append(
			wpdaGetMediaContent(attachment, attachmentType)
		);
	} else {
		mediaItem.val('');
		for (var i = 0; i < attachment.length; ++i) {
			if (attachment[i].id !== '') {
				if (mediaItem.val() === '') {
					mediaItem.val(attachment[i].id);
				} else {
					mediaItem.val(
						mediaItem.val() + ',' + attachment[i].id
					);
				}
				mediaContainer.append(
					wpdaGetMediaContent(attachment[i], attachmentType)
				);
			}
		}
	}
}

function wpdaGetMediaContent(attachment, attachmentType) {
	switch(attachmentType) {
		case 'image':
			return wpdaGetMediaImage(attachment)
		case 'video':
			return wpdaGetMediaVideo(attachment)
		case 'audio':
			return wpdaGetMediaAudio(attachment)
		default:
			return wpdaGetMediaItem(attachment)
	}
}

function wpdaGetMediaItem(attachment) {
	return `
		<span class="wpda_media_container wpda_media">
			<a href="${attachment.url}" target="_blank">
				<img src="${attachment.icon}">
				<br/>
				${attachment.title}
			</a>
		</span>
	`;
}

function wpdaGetMediaImage(attachment) {
	return `
		<span class="wpda_media_container wpda_image">
			<img src="${attachment.url}"/>
		</span>
	`;
}

function wpdaGetMediaAudio(attachment) {
	return `
		<span class="wpda_media_container wpda_audio">
			<audio src="${attachment.url}" controls></audio>
		</span>
	`;
}

function wpdaGetMediaVideo(attachment) {
	return `
		<span class="wpda_media_container wpda_video">
			<video controls>
				<source src="${attachment.url}">
			</video>
		</span>
	`;
}

function wpdaSetMediaSelection(mediaItem, frame) {
	// Selects selected media in media uploader.
	selection = frame.state().get('selection');
	media_ids = mediaItem.val();
	media_ids = media_ids.split(',');
	media_ids.forEach(
		function (id) {
			attachment = wp.media.attachment(id);
			attachment.fetch();
			selection.add(attachment ? [attachment] : []);
		}
	);
}

const setLegacyTools = () => {
	jQuery("#wpda-legacy-tool-settings-panel").toggle();
}

const setLegacyToolStatus = (element) => {
	if (jQuery(element).find("i.fas.fa-toggle-off").length === 1) {
		// Turn on
		jQuery(element).html('<i class="fas fa-toggle-on" style="font-size: 28px;"></i>');
	} else {
		// Turn off
		jQuery(element).html('<i class="fas fa-toggle-off" style="font-size: 28px;"></i>');
	}
}

const updateLegacyTools = () => {
	const toolStatus = {}

	jQuery.each(
		jQuery("#wpda-legacy-tool-settings-panel table td.status"),
		function(key, value) {
			const tool = jQuery(value).data("tool");
			const status = jQuery(value).find("i.fas.fa-toggle-off").length === 0
			toolStatus[tool] = status;
		}
	);

	jQuery("#wpda-legacy-tool-settings-legacy-tool-data").val(JSON.stringify(toolStatus));
	jQuery("#wpda-legacy-tool-settings-form-data").submit();
}

const submitMailCallbackOk = (response, debug) => {
	if (response.code!==undefined && response.message!==undefined) {
		switch(response.code) {
			case "ok":
				jQuery("#debugInfoContainer").parent().parent().hide()
				jQuery.notify(response.message, "success");
				break;
			case "error":
				jQuery.notify(response.message, "error");
				break;
			default:
				jQuery.notify("Application error! Please contact the plugin development team.", "error");
		}

		if (debug === true) {
			jQuery("#debugInfoContainer").html(response.debug)
			jQuery("#debugInfoContainer").parent().parent().show()
		}
	} else {
		submitSettingsCallbackError(response);
	}

	jQuery('#mailSpinner').hide();
}

const submitMailCallbackError = (response) => {
	if (response.code!==undefined && response.message!==undefined) {
		switch(response.code) {
			case "error":
				jQuery.notify(response.message, "error");
				break;
			default:
				jQuery.notify("Application error! Please contact the plugin development team.", "error");
		}
	} else {
		jQuery.notify("Application error! Please contact the plugin development team.", "error");
	}

	jQuery('#mailSpinner').hide();
}

const sendMail = (to, subject, message, debug) => {
	if (to.trim() === "" || subject.trim() === "" || message.trim() === "") {
		jQuery.notify("Invalid function call! Missing arguments.", "error");
		jQuery('#mailSpinner').hide();
	} else {
		wpda_rest_api(
			"action/mail",
			{
				to: to,
				subject: subject,
				message: message,
			},
			function(response) {
				submitMailCallbackOk(response, debug)
			},
			submitSettingsCallbackError
		);
	}
}