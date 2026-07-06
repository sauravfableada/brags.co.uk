CREATE TABLE {wp_prefix}wpda_app_container{wpda_postfix}
(cnt_id			bigint unsigned		not null	auto_increment
,cnt_dbs		varchar(128)		not null
,cnt_tbl		varchar(128)		not null
,cnt_cls		longtext			not null
,cnt_title		varchar(200)		not null
,app_id			bigint unsigned		not null
,cnt_seq_nr		smallint unsigned	not null
,cnt_table		longtext
,cnt_form		longtext
,cnt_rform		longtext
,cnt_relation   longtext
,cnt_chart		longtext
,cnt_map		longtext
,cnt_query		longtext
,cnt_dashboard	longtext
,primary key (cnt_id)
) {wpda_collate};
