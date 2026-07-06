CREATE TABLE {wp_prefix}wpda_app{wpda_postfix}
(app_id				bigint unsigned		not null	auto_increment
,app_name			varchar(30)			not null
,app_title			varchar(100)		not null
,app_type			tinyint				not null
,app_settings 		longtext
,app_theme	 		longtext
,app_add_to_menu	tinyint(1)			not null	default 0
,primary key (app_id)
,unique key (app_name)
) {wpda_collate};