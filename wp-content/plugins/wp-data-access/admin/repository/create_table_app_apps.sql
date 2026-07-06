CREATE TABLE {wp_prefix}wpda_app_apps{wpda_postfix}
(app_id			bigint unsigned		not null
,app_id_detail	bigint unsigned		not null
,seq_nr			smallint unsigned	not null
,app_settings 	longtext
,primary key (app_id, app_id_detail)
) {wpda_collate};