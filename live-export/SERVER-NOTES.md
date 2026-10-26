# Live server snapshot — liferuss.com — 2026-10-26

Exported read-only from the production server (Parspack / Hestia CP, Turkey). Code only.

## WordPress
    7.1.3
## PHP
    PHP 8.5.10 (cli) (built: Aug 28 2026 06:46:18) (NTS)
## Theme
    name	status	version	update
    liferuss	active	1.14.3	none
## Plugins
    name	status	version	update
    nginx-helper	active	2.4.1	none
    redis-cache	active	3.0.0	none
    liferuss-leads	active	1.0.0	none
    liferuss-core	active	1.14.3	none
    modiriat-wordpress	active	1.0.0	none
    object-cache.php	dropin		
## mu-plugins
    name	version
## siteurl
    https://liferuss.com
## Cron events
    hook	next_run_relative	recurrence
    lr_search_queue	2 minutes 16 seconds	15 minutes
    lr_crm_sla	16 minutes 12 seconds	1 hour
    lr_pay_expire	16 minutes 12 seconds	1 hour
    wp_privacy_delete_old_export_files	28 minutes 47 seconds	1 hour
    wp_update_user_counts	28 minutes 54 seconds	12 hours
    rediscache_discard_metrics	57 minutes	1 hour
    wp_version_check	1 hour 28 minutes	12 hours
    wp_update_plugins	1 hour 58 minutes	12 hours
    wp_update_themes	2 hours 28 minutes	12 hours
    lr_academy_daily	3 hours 14 minutes	1 day
    lr_crm_digest	6 hours 16 minutes	1 day
    lr_purge_lead_files	7 hours 16 minutes	1 day
    lr_purge_not_found	7 hours 16 minutes	1 day
    recovery_mode_clean_expired_keys	12 hours 28 minutes	1 day
    wp_privacy_personal_data_cleanup_requests	12 hours 28 minutes	1 day
    wp_scheduled_delete	12 hours 28 minutes	1 day
    delete_expired_transients	12 hours 28 minutes	1 day
    wp_scheduled_auto_draft_delete	12 hours 28 minutes	1 day
    rt_wp_nginx_helper_check_log_file_size_daily	12 hours 54 minutes	1 day
    lr_gsc_pull	23 hours 26 minutes	1 day
    wp_delete_temp_updater_backups	4 days 12 hours	1 week
    wp_site_health_scheduled_check	5 days 12 hours	1 week
## system crontab (root)
## user crontab rezadoci
    no crontab for rezadoci
## Hestia web template
        "TPL": "default",
        "STATS": "awstats",
        "STATS_USER": "",
        "SSL": "yes",
        "SSL_FORCE": "yes",
        "SSL_HSTS": "yes",
        "SSL_HOME": "same",
        "BACKEND": "default",
        "PROXY": "caching",
        "PROXY_EXT": "css,htm,html,js,mjs,json,xml,apng,avif,bmp,cur,gif,ico,jfif,jpg,jpeg,pjp,pjpeg,png,svg,tif,tiff,webp,aac,caf,flac,m4a,midi,mp3,ogg,opus,wav,3gp,av1,avi,m4v,mkv,mov,mpg,mpeg,mp4,mp4v,webm,otf,ttf,woff,woff2,doc,docx,odf,odp,ods,odt,pdf,ppt,pptx,rtf,txt,xls,xlsx,7z,bz2,gz,rar,tar,tgz,zip,apk,appx,bin,dmg,exe,img,iso,jar,msi,webmanifest",

## Excluded on purpose
wp-config.php, .env files, SSH keys/tokens/API keys, database dumps, wp-content/uploads, backups, caches,
third-party plugins (nginx-helper, redis-cache, modiriat-wordpress — listed above only), WordPress core.
Secret-pattern grep over the export found only code references (nonce helpers, settings keys, `by_token` etc.); no literal credentials.
