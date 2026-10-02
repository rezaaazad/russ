<?php
/**
 * Settings screen.
 *
 * @package LifeRussCore
 */

namespace LifeRuss\Core\Settings;

use LifeRuss\Core\Admin\Chrome;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Tabbed options page. Each tab is gated by its own capability.
 */
class SettingsPage {

	/**
	 * Reserved for future settings hooks. Saving happens in render().
	 */
	public static function hooks(): void {
		// Saving runs inside render() so the success notice stays on this screen.
	}

	/**
	 * Tabs in menu order.
	 *
	 * @return array<string, array{label: string, cap: string}>
	 */
	public static function tabs(): array {
		return array(
			'general'       => array(
				'label' => 'عمومی',
				'cap'   => 'lr_manage_settings',
			),
			'contact'       => array(
				'label' => 'تماس و شبکه‌ها',
				'cap'   => 'lr_manage_settings',
			),
			'footer'        => array(
				'label' => 'پاورقی',
				'cap'   => 'lr_manage_settings',
			),
			'currency'      => array(
				'label' => 'نرخ ارز',
				'cap'   => 'lr_manage_settings',
			),
			'notifications' => array(
				'label' => 'اعلان‌ها',
				'cap'   => 'lr_manage_settings',
			),
			'tracking'      => array(
				'label' => 'رهگیری',
				'cap'   => 'lr_manage_tracking',
			),
			'forms'         => array(
				'label' => 'فرم‌ها',
				'cap'   => 'lr_manage_settings',
			),
			'security'      => array(
				'label' => 'امنیت',
				'cap'   => 'lr_manage_security',
			),
			'languages'     => array(
				'label' => 'زبان‌ها',
				'cap'   => 'lr_manage_settings',
			),
			'backup'        => array(
				'label' => 'پشتیبان',
				'cap'   => 'lr_manage_backup',
			),
			'search'        => array(
				'label' => 'جستجو',
				'cap'   => 'lr_manage_settings',
			),
			'crm'           => array(
				'label' => 'اتوماسیون CRM',
				'cap'   => 'lr_manage_settings',
			),
			'account'       => array(
				'label' => 'حساب مراجع',
				'cap'   => 'lr_manage_settings',
			),
			'payments'      => array(
				'label' => 'پرداخت خدمات',
				'cap'   => 'lr_manage_settings',
			),
		);
	}

	/**
	 * Render the active tab.
	 */
	public static function render(): void {
		if ( ! current_user_can( 'lr_access_settings' ) ) {
			wp_die( esc_html__( 'به تنظیمات لایف‌روس دسترسی ندارید.', 'liferuss-core' ), '', array( 'response' => 403 ) );
		}

		$visible = array();
		foreach ( self::tabs() as $slug => $tab ) {
			if ( current_user_can( $tab['cap'] ) ) {
				$visible[ $slug ] = $tab;
			}
		}
		if ( ! $visible ) {
			wp_die( esc_html__( 'به تنظیمات لایف‌روس دسترسی ندارید.', 'liferuss-core' ), '', array( 'response' => 403 ) );
		}

		$requested = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$tab       = isset( $visible[ $requested ] ) ? $requested : (string) array_key_first( $visible );
		$notice    = self::maybe_save( $tab );

		Chrome::open( __( 'تنظیمات لایف‌روس', 'liferuss-core' ), 'تنظیمات' );
		if ( $notice ) {
			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html( $notice ) . '</p></div>';
		}
		self::nav( $visible, $tab );

		echo '<form method="post">';
		wp_nonce_field( 'lr_settings_save', 'lr_settings_nonce' );
		echo '<input type="hidden" name="lr_settings_tab" value="' . esc_attr( $tab ) . '">';
		echo '<table class="form-table" role="presentation">';
		call_user_func( array( self::class, 'fields_' . $tab ) );
		echo '</table>';
		submit_button( __( 'ذخیره تنظیمات', 'liferuss-core' ) );
		echo '</form></div>';
	}

	/**
	 * Save the posted tab when the nonce and capability match.
	 *
	 * @param string $tab Active tab.
	 */
	private static function maybe_save( string $tab ): string {
		if ( ! isset( $_POST['lr_settings_nonce'] ) ) {
			return '';
		}
		$nonce = sanitize_text_field( wp_unslash( $_POST['lr_settings_nonce'] ) );
		if ( ! wp_verify_nonce( $nonce, 'lr_settings_save' ) ) {
			return '';
		}
		$posted_tab = isset( $_POST['lr_settings_tab'] ) ? sanitize_key( wp_unslash( $_POST['lr_settings_tab'] ) ) : '';
		if ( $posted_tab !== $tab ) {
			return '';
		}
		$tabs = self::tabs();
		if ( ! isset( $tabs[ $tab ] ) || ! current_user_can( $tabs[ $tab ]['cap'] ) ) {
			return '';
		}

		$saver = 'save_' . $tab;
		if ( is_callable( array( self::class, $saver ) ) ) {
			call_user_func( array( self::class, $saver ) );
		}
		return __( 'تنظیمات ذخیره شد.', 'liferuss-core' );
	}

	/**
	 * Tab navigation.
	 *
	 * @param array<string, array{label: string, cap: string}> $visible Visible tabs.
	 * @param string                                           $current Current slug.
	 */
	private static function nav( array $visible, string $current ): void {
		echo '<nav class="nav-tab-wrapper lr-tabs">';
		foreach ( $visible as $slug => $tab ) {
			$url   = add_query_arg(
				array(
					'page' => 'lr-settings',
					'tab'  => $slug,
				),
				admin_url( 'admin.php' )
			);
			$class = 'nav-tab' . ( $slug === $current ? ' nav-tab-active' : '' );
			echo '<a class="' . esc_attr( $class ) . '" href="' . esc_url( $url ) . '">' . esc_html( $tab['label'] ) . '</a>';
		}
		echo '</nav>';
	}

	/**
	 * Text field row.
	 *
	 * @param string $name  Input name.
	 * @param string $label Label.
	 * @param string $value Value.
	 * @param string $type  Input type.
	 */
	private static function text_row( string $name, string $label, string $value, string $type = 'text' ): void {
		echo '<tr><th scope="row"><label for="' . esc_attr( $name ) . '">' . esc_html( $label ) . '</label></th><td>';
		echo '<input class="regular-text" type="' . esc_attr( $type ) . '" id="' . esc_attr( $name ) . '" name="' . esc_attr( $name ) . '" value="' . esc_attr( $value ) . '">';
		echo '</td></tr>';
	}

	/**
	 * Textarea row.
	 *
	 * @param string $name  Field name.
	 * @param string $label Label.
	 * @param string $value Value.
	 */
	private static function area_row( string $name, string $label, string $value ): void {
		echo '<tr><th scope="row"><label for="' . esc_attr( $name ) . '">' . esc_html( $label ) . '</label></th><td>';
		echo '<textarea class="large-text" rows="4" id="' . esc_attr( $name ) . '" name="' . esc_attr( $name ) . '">' . esc_textarea( $value ) . '</textarea>';
		echo '</td></tr>';
	}

	/**
	 * Posted text field.
	 *
	 * @param string $key Field name.
	 */
	private static function posted_text( string $key ): string {
		if ( ! isset( $_POST[ $key ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
			return '';
		}
		return sanitize_text_field( wp_unslash( $_POST[ $key ] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
	}

	/**
	 * Posted textarea, tags stripped.
	 *
	 * @param string $key Field name.
	 */
	private static function posted_area( string $key ): string {
		if ( ! isset( $_POST[ $key ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
			return '';
		}
		return sanitize_textarea_field( wp_unslash( $_POST[ $key ] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
	}

	/**
	 * Tracking snippets stay as HTML. Only lr_manage_tracking can post them.
	 *
	 * @param string $key Field name.
	 */
	private static function posted_scripts( string $key ): string {
		if ( ! isset( $_POST[ $key ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
			return '';
		}
		$raw = wp_unslash( $_POST[ $key ] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized,WordPress.Security.NonceVerification.Missing
		if ( ! is_string( $raw ) ) {
			return '';
		}
		return wp_check_invalid_utf8( str_replace( "\0", '', $raw ) );
	}

	/**
	 * General fields.
	 */
	private static function fields_general(): void {
		$v = Settings::get( 'general' );
		self::text_row( 'brand_name', __( 'نام برند', 'liferuss-core' ), (string) $v['brand_name'] );
		self::text_row( 'academic_year', __( 'سال تحصیلی پیش‌فرض', 'liferuss-core' ), (string) $v['academic_year'] );
		self::text_row( 'support_email', __( 'ایمیل پشتیبانی', 'liferuss-core' ), (string) $v['support_email'], 'email' );
	}

	/**
	 * Contact and social fields.
	 */
	private static function fields_contact(): void {
		$v = Settings::get( 'contact' );
		self::text_row( 'phone', __( 'تلفن', 'liferuss-core' ), (string) $v['phone'] );
		self::text_row( 'whatsapp', __( 'واتساپ', 'liferuss-core' ), (string) $v['whatsapp'] );
		self::text_row( 'telegram', __( 'تلگرام', 'liferuss-core' ), (string) $v['telegram'] );
		self::text_row( 'instagram', __( 'اینستاگرام', 'liferuss-core' ), (string) $v['instagram'] );
		self::text_row( 'email', __( 'ایمیل', 'liferuss-core' ), (string) $v['email'], 'email' );
		self::area_row( 'address', __( 'نشانی', 'liferuss-core' ), (string) $v['address'] );
	}

	/**
	 * Footer fields.
	 */
	private static function fields_footer(): void {
		$v = Settings::get( 'footer' );
		self::area_row( 'about', __( 'متن کوتاه پاورقی', 'liferuss-core' ), (string) $v['about'] );
		self::text_row( 'copyright', __( 'کپی‌رایت', 'liferuss-core' ), (string) $v['copyright'] );
	}

	/**
	 * Manual exchange rates.
	 */
	private static function fields_currency(): void {
		$v = Settings::get( 'currency' );
		echo '<tr><td colspan="2"><p>' . esc_html__( 'نرخ دستی منبع حقیقت است: چند دلار آمریکا برابر یک واحد از ارز است. قفل دستی همیشه بر به‌روزرسانی خودکار مقدم است. IRT روی جفت IRR ذخیره می‌شود.', 'liferuss-core' ) . '</p></td></tr>';
		echo '<tr><th scope="row"><label for="fx_provider">' . esc_html__( 'منبع', 'liferuss-core' ) . '</label></th><td><select id="fx_provider" name="fx_provider">';
		foreach ( array(
			'manual' => 'دستی',
			'json'   => 'نشانی JSON',
		) as $key => $label ) {
			echo '<option value="' . esc_attr( $key ) . '" ' . selected( (string) $v['provider'], $key, false ) . '>' . esc_html( $label ) . '</option>';
		}
		echo '</select></td></tr>';
		self::text_row( 'fx_json_url', __( 'نشانی JSON', 'liferuss-core' ), (string) $v['json_url'], 'url' );
		self::text_row( 'fx_path_usd_rub', __( 'مسیر JSON برای USD به RUB', 'liferuss-core' ), (string) $v['path_usd_rub'] );
		self::text_row( 'fx_path_usd_irt', __( 'مسیر JSON برای USD به IRT', 'liferuss-core' ), (string) $v['path_usd_irt'] );
		self::text_row( 'fx_path_rub_irt', __( 'مسیر JSON برای RUB به IRT', 'liferuss-core' ), (string) $v['path_rub_irt'] );
		echo '<tr><th scope="row"><label for="fx_interval">' . esc_html__( 'بازه', 'liferuss-core' ) . '</label></th><td><select id="fx_interval" name="fx_interval">';
		foreach ( array(
			'hourly' => 'ساعتی',
			'6h'     => 'هر ۶ ساعت',
			'daily'  => 'روزانه',
		) as $key => $label ) {
			echo '<option value="' . esc_attr( $key ) . '" ' . selected( (string) $v['interval'], $key, false ) . '>' . esc_html( $label ) . '</option>';
		}
		echo '</select></td></tr>';
		if ( (int) $v['fail_count'] > 0 ) {
			echo '<tr><th scope="row">' . esc_html__( 'خطای واکشی', 'liferuss-core' ) . '</th><td>' . esc_html( (string) $v['fail_count'] . ' — ' . (string) $v['last_error'] ) . '</td></tr>';
		}
		foreach ( Settings::CURRENCIES as $code ) {
			$rate = $v['rates'][ $code ];
			$meta = '';
			if ( ! empty( $rate['updated_at'] ) ) {
				$meta = 'آخرین به‌روزرسانی: ' . \LifeRuss\Core\CRM\Jalali::plain( (string) $rate['updated_at'] );
			}
			echo '<tr><th scope="row"><label for="rate_' . esc_attr( $code ) . '">' . esc_html( $code ) . '</label></th><td>';
			echo '<input class="regular-text" type="text" inputmode="decimal" id="rate_' . esc_attr( $code ) . '" name="rates[' . esc_attr( $code ) . ']" value="' . esc_attr( (string) $rate['usd_per_unit'] ) . '"> ';
			echo '<label><input type="checkbox" name="locks[' . esc_attr( $code ) . ']" value="1" ' . checked( '1', (string) ( $rate['manual_lock'] ?? '0' ), false ) . '> ' . esc_html__( 'قفل دستی', 'liferuss-core' ) . '</label>';
			if ( $meta ) {
				echo '<p class="description">' . esc_html( $meta ) . '</p>';
			}
			echo '</td></tr>';
		}
	}

	/**
	 * Telegram and email notification fields.
	 */
	private static function fields_notifications(): void {
		$v = Settings::get( 'notifications' );
		echo '<tr><th scope="row">' . esc_html__( 'تلگرام', 'liferuss-core' ) . '</th><td>';
		echo '<label><input type="checkbox" name="telegram_enabled" value="1" ' . checked( '1', (string) $v['telegram_enabled'], false ) . '> ' . esc_html__( 'فعال', 'liferuss-core' ) . '</label>';
		echo '</td></tr>';
		self::text_row( 'bot_token', __( 'توکن ربات', 'liferuss-core' ), (string) $v['bot_token'] );
		self::text_row( 'chat_id', __( 'شناسه گفتگوی پیش‌فرض', 'liferuss-core' ), (string) $v['chat_id'] );
		echo '<tr><th scope="row">' . esc_html__( 'ایمیل', 'liferuss-core' ) . '</th><td>';
		echo '<label><input type="checkbox" name="email_enabled" value="1" ' . checked( '1', (string) $v['email_enabled'], false ) . '> ' . esc_html__( 'فعال', 'liferuss-core' ) . '</label>';
		echo '</td></tr>';
		self::text_row( 'from_name', __( 'نام فرستنده', 'liferuss-core' ), (string) $v['from_name'] );
		self::text_row( 'from_email', __( 'ایمیل فرستنده', 'liferuss-core' ), (string) $v['from_email'], 'email' );

		$labels = array(
			'education' => 'پذیرش',
			'language'  => 'زبان روسی',
			'exchange'  => 'صرافی',
			'cargo'     => 'کارگو',
			'trade'     => 'تجارت',
			'contact'   => 'تماس',
		);
		foreach ( Settings::NOTICE_SERVICES as $code ) {
			$row = $v['services'][ $code ];
			echo '<tr><th scope="row">' . esc_html( $labels[ $code ] ) . '</th><td>';
			echo '<input class="regular-text" type="email" name="svc_email[' . esc_attr( $code ) . ']" value="' . esc_attr( (string) $row['email'] ) . '" placeholder="' . esc_attr__( 'ایمیل این سرویس', 'liferuss-core' ) . '"> ';
			echo '<input class="regular-text" type="text" name="svc_chat[' . esc_attr( $code ) . ']" value="' . esc_attr( (string) $row['chat_id'] ) . '" placeholder="' . esc_attr__( 'شناسه گفتگوی تلگرام', 'liferuss-core' ) . '">';
			echo '</td></tr>';
		}
	}

	/**
	 * Tracking scripts.
	 */
	private static function fields_tracking(): void {
		$v = Settings::get( 'tracking' );
		self::text_row( 'gtm_id', 'GTM ID', (string) $v['gtm_id'] );
		self::text_row( 'ga4_id', 'GA4 ID', (string) $v['ga4_id'] );
		self::area_row( 'head_scripts', __( 'اسکریپت head', 'liferuss-core' ), (string) $v['head_scripts'] );
		self::area_row( 'body_scripts', __( 'اسکریپت body', 'liferuss-core' ), (string) $v['body_scripts'] );
		echo '<tr><td colspan="2"><p class="description">' . esc_html__( 'اسکریپت‌ها خام ذخیره می‌شوند و فقط نقش‌های دارای lr_manage_tracking می‌توانند آن‌ها را تغییر دهند. تزریق در قالب در این نسخه انجام نمی‌شود.', 'liferuss-core' ) . '</p></td></tr>';
	}

	/**
	 * Form copy and Cloudflare Turnstile. Empty keys leave the check off.
	 */
	private static function fields_forms(): void {
		$v = Settings::get( 'forms' );
		self::area_row( 'success_message', __( 'پیام موفقیت', 'liferuss-core' ), (string) $v['success_message'] );
		self::text_row( 'notify_email', __( 'ایمیل اطلاع‌رسانی پیش‌فرض', 'liferuss-core' ), (string) $v['notify_email'], 'email' );
		self::text_row( 'turnstile_site_key', 'Turnstile site key', (string) $v['turnstile_site_key'] );
		self::text_row( 'turnstile_secret', 'Turnstile secret', (string) $v['turnstile_secret'] );
		echo '<tr><td colspan="2"><p class="description">' . esc_html__( 'اگر هر دو کلید خالی باشند، فرم عمومی بدون Turnstile ثبت می‌شود.', 'liferuss-core' ) . '</p></td></tr>';
	}

	/**
	 * Security flags, login limit, and the authenticator grace window.
	 */
	private static function fields_security(): void {
		$v = Settings::get( 'security' );
		echo '<tr><th scope="row">' . esc_html__( 'حذف داده هنگام حذف افزونه', 'liferuss-core' ) . '</th><td>';
		echo '<label><input type="checkbox" name="delete_data_on_uninstall" value="1" ' . checked( '1', (string) $v['delete_data_on_uninstall'], false ) . '> ';
		echo esc_html__( 'با حذف افزونه، جدول‌ها، نقش‌های lr_* و تنظیمات پاک شوند. پیش‌فرض خاموش است.', 'liferuss-core' );
		echo '</label></td></tr>';
		self::text_row( 'totp_grace_days', __( 'مهلت فعال‌سازی ورود دومرحله‌ای (روز)', 'liferuss-core' ), (string) $v['totp_grace_days'], 'number' );
		self::text_row( 'login_limit', __( 'سقف تلاش ناموفق ورود', 'liferuss-core' ), (string) $v['login_limit'], 'number' );
		self::text_row( 'not_found_days', __( 'نگهداری گزارش ۴۰۴ (روز)', 'liferuss-core' ), (string) $v['not_found_days'], 'number' );
		echo '<tr><td colspan="2"><p class="description">' . esc_html__( 'ورود دومرحله‌ای برای مدیر ارشد، ادمین، مدیر محتوا، مدیر سئو و مشاور اجباری است. رمز برنامه و REST از این مرحله رد نمی‌شوند.', 'liferuss-core' ) . '</p></td></tr>';
	}

	/**
	 * Default language and whether incomplete translations may be indexed.
	 */
	private static function fields_languages(): void {
		$v = Settings::get( 'languages' );
		self::text_row( 'default_language', __( 'زبان پیش‌فرض', 'liferuss-core' ), (string) $v['default_language'] );
		echo '<tr><th scope="row">' . esc_html__( 'نمایهٔ ترجمه‌های ناقص', 'liferuss-core' ) . '</th><td>';
		echo '<label><input type="checkbox" name="index_incomplete" value="1" ' . checked( '1', (string) $v['index_incomplete'], false ) . '> ';
		echo esc_html__( 'روسی، انگلیسی و عربی حتی قبل از علامت «ترجمه کامل» ایندکس شوند. پیش‌فرض خاموش است.', 'liferuss-core' );
		echo '</label></td></tr>';
		echo '<tr><td colspan="2"><p class="description">' . esc_html__( 'فارسی زبان پیش‌فرض است. اگر Polylang فعال باشد، نوع‌های نوشته و رشته‌های قالب در آن ثبت می‌شوند. ردیف‌های کاتالوگ زبان‌خنثی می‌مانند.', 'liferuss-core' ) . '</p></td></tr>';
	}

	/**
	 * Meilisearch connection. An empty host keeps the MySQL fallback.
	 */
	private static function fields_search(): void {
		$v = Settings::get( 'search' );
		self::text_row( 'search_host', __( 'آدرس Meilisearch', 'liferuss-core' ), (string) $v['host'] );
		self::text_row( 'search_api_key', __( 'کلید API', 'liferuss-core' ), '', 'password' );
		self::text_row( 'search_index_prefix', __( 'پیشوند نمایه', 'liferuss-core' ), (string) $v['index_prefix'] );
		echo '<tr><td colspan="2"><p class="description">' . esc_html__( 'اگر آدرس یا کلید خالی باشد، یا سرویس جواب ندهد، جستجو با جدول محلی MySQL ادامه پیدا می‌کند. کلید ذخیره‌شده اینجا نمایش داده نمی‌شود؛ برای عوض کردنش مقدار جدید بنویسید.', 'liferuss-core' ) . '</p>';
		$url = wp_nonce_url( admin_url( 'admin-post.php?action=lr_search_reindex' ), 'lr_search_reindex' );
		echo '<p><a class="button" href="' . esc_url( $url ) . '">' . esc_html__( 'بازسازی نمایه', 'liferuss-core' ) . '</a></p>';
		if ( isset( $_GET['reindex'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$count = absint( wp_unslash( $_GET['reindex'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			echo '<p>' . esc_html( sprintf( /* translators: %d: document count */ __( '%d سند در نمایهٔ محلی نوشته شد.', 'liferuss-core' ), $count ) ) . '</p>';
		}
		echo '</td></tr>';
	}

	/**
	 * Save search settings. A blank key keeps the stored key.
	 */
	private static function save_search(): void {
		$current = Settings::get( 'search' );
		$key     = self::posted_text( 'search_api_key' );
		if ( '' === $key ) {
			$key = (string) $current['api_key'];
		}
		$prefix = sanitize_key( self::posted_text( 'search_index_prefix' ) );
		if ( '' === $prefix ) {
			$prefix = 'liferuss';
		}
		Settings::update(
			'search',
			array(
				'host'         => untrailingslashit( esc_url_raw( self::posted_text( 'search_host' ) ) ),
				'api_key'      => $key,
				'index_prefix' => $prefix,
			)
		);
	}

	/**
	 * CRM automation rules.
	 */
	private static function fields_crm(): void {
		$v = Settings::get( 'crm' );
		echo '<tr><th scope="row">' . esc_html__( 'ارجاع خودکار', 'liferuss-core' ) . '</th><td>';
		echo '<label><input type="checkbox" name="auto_assign" value="1" ' . checked( '1', (string) $v['auto_assign'], false ) . '> ';
		echo esc_html__( 'لید جدید بین مشاوران و اپراتورهای فعال همان سرویس، به‌نوبت، تقسیم شود. فقط کسانی که lr_allowed_services همان گروه را دارند.', 'liferuss-core' );
		echo '</label></td></tr>';
		self::text_row( 'sla_hours', __( 'مهلت اولین تماس (ساعت)', 'liferuss-core' ), (string) $v['sla_hours'], 'number' );
		self::text_row( 'dedupe_days', __( 'پنجرهٔ تکرار (روز)', 'liferuss-core' ), (string) $v['dedupe_days'], 'number' );
		self::text_row( 'followup_contacted', __( 'پیگیری پس از «تماس» (روز)', 'liferuss-core' ), (string) $v['followup_contacted'], 'number' );
		self::text_row( 'followup_documents', __( 'یادآوری پس از «مدارک» (روز)', 'liferuss-core' ), (string) $v['followup_documents'], 'number' );
		self::text_row( 'followup_qualified', __( 'پیگیری پس از «واجد شرایط» (روز)', 'liferuss-core' ), (string) $v['followup_qualified'], 'number' );
		echo '<tr><th scope="row">' . esc_html__( 'خلاصهٔ روزانه', 'liferuss-core' ) . '</th><td>';
		echo '<label><input type="checkbox" name="daily_digest" value="1" ' . checked( '1', (string) $v['daily_digest'], false ) . '> ';
		echo esc_html__( 'هر روز وظایف سررسیدشده برای هر مشاور ایمیل شود. تأخیر SLA به مدیر و، اگر روشن باشد، تلگرام می‌رود.', 'liferuss-core' );
		echo '</label></td></tr>';
	}

	/**
	 * Save CRM rules.
	 */
	private static function save_crm(): void {
		$hours = absint( self::posted_text( 'sla_hours' ) );
		$days  = absint( self::posted_text( 'dedupe_days' ) );
		Settings::update(
			'crm',
			array(
				'auto_assign'        => isset( $_POST['auto_assign'] ) ? '1' : '0', // phpcs:ignore WordPress.Security.NonceVerification.Missing
				'sla_hours'          => (string) ( $hours > 0 ? $hours : 4 ),
				'dedupe_days'        => (string) ( $days > 0 ? $days : 30 ),
				'followup_contacted' => (string) absint( self::posted_text( 'followup_contacted' ) ),
				'followup_documents' => (string) absint( self::posted_text( 'followup_documents' ) ),
				'followup_qualified' => (string) absint( self::posted_text( 'followup_qualified' ) ),
				'daily_digest'       => isset( $_POST['daily_digest'] ) ? '1' : '0', // phpcs:ignore WordPress.Security.NonceVerification.Missing
			)
		);
	}

	/**
	 * Client account delivery.
	 */
	private static function fields_account(): void {
		$v = Settings::get( 'account' );
		echo '<tr><th scope="row"><label for="sms_provider">' . esc_html__( 'ارسال پیامک', 'liferuss-core' ) . '</label></th><td>';
		echo '<select id="sms_provider" name="sms_provider">';
		echo '<option value="stub"' . selected( 'stub', (string) $v['sms_provider'], false ) . '>' . esc_html__( 'آزمایشی — ثبت در گزارش، بدون اپراتور', 'liferuss-core' ) . '</option>';
		echo '</select>';
		echo '<p class="description">' . esc_html__( 'ورود با ایمیل از نامهٔ سایت استفاده می‌کند. پیامک از فیلتر liferuss_sms_send می‌گذرد و در غیر این صورت به این ارائه‌دهنده می‌رسد.', 'liferuss-core' ) . '</p>';
		echo '</td></tr>';
	}

	/**
	 * Save the SMS provider. Only the stub is built in.
	 */
	private static function fields_payments(): void {
		$v = Settings::get( 'payments' );
		self::text_row( 'merchant_id', __( 'مرچنت زرین‌پال', 'liferuss-core' ), (string) $v['merchant_id'] );
		echo '<tr><th scope="row">' . esc_html__( 'سندباکس', 'liferuss-core' ) . '</th><td>';
		echo '<label><input type="checkbox" name="pay_sandbox" value="1" ' . checked( '1', (string) $v['sandbox'], false ) . '> ' . esc_html__( 'استفاده از sandbox.zarinpal.com', 'liferuss-core' ) . '</label>';
		echo '<p class="description">' . esc_html__( 'مبلغ صورتحساب به تومان (IRT) به درگاه می‌رود. درگاه‌های دیگر با فیلتر liferuss_payment_gateway جایگزین می‌شوند.', 'liferuss-core' ) . '</p>';
		echo '</td></tr>';
	}

	/**
	 * Save Zarinpal credentials.
	 */
	private static function save_payments(): void {
		Settings::update(
			'payments',
			array(
				'merchant_id' => self::posted_text( 'merchant_id' ),
				'sandbox'     => isset( $_POST['pay_sandbox'] ) ? '1' : '0', // phpcs:ignore WordPress.Security.NonceVerification.Missing
			)
		);
	}

	/**
	 * Save the SMS provider. Only the stub is built in.
	 */
	private static function save_account(): void {
		$provider = sanitize_key( self::posted_text( 'sms_provider' ) );
		Settings::update(
			'account',
			array(
				'sms_provider' => 'stub' === $provider ? 'stub' : 'stub',
			)
		);
	}

	/**
	 * Backup placeholder.
	 */
	private static function fields_backup(): void {
		$v = Settings::get( 'backup' );
		self::text_row( 'retention_days', __( 'نگهداری (روز)', 'liferuss-core' ), (string) $v['retention_days'], 'number' );
		echo '<tr><td colspan="2"><p class="description">' . esc_html__( 'زمان‌بندی پشتیبان در این نسخه اجرا نمی‌شود. این مقدار فقط ذخیره می‌شود.', 'liferuss-core' ) . '</p></td></tr>';
	}

	/**
	 * Save general.
	 */
	private static function save_general(): void {
		Settings::update(
			'general',
			array(
				'brand_name'    => self::posted_text( 'brand_name' ),
				'academic_year' => self::posted_text( 'academic_year' ),
				'support_email' => sanitize_email( self::posted_text( 'support_email' ) ),
			)
		);
	}

	/**
	 * Save contact.
	 */
	private static function save_contact(): void {
		Settings::update(
			'contact',
			array(
				'phone'     => self::posted_text( 'phone' ),
				'whatsapp'  => esc_url_raw( self::posted_text( 'whatsapp' ) ),
				'telegram'  => esc_url_raw( self::posted_text( 'telegram' ) ),
				'instagram' => esc_url_raw( self::posted_text( 'instagram' ) ),
				'email'     => sanitize_email( self::posted_text( 'email' ) ),
				'address'   => self::posted_area( 'address' ),
			)
		);
	}

	/**
	 * Save footer.
	 */
	private static function save_footer(): void {
		Settings::update(
			'footer',
			array(
				'about'     => self::posted_area( 'about' ),
				'copyright' => self::posted_text( 'copyright' ),
			)
		);
	}

	/**
	 * Save currency rates.
	 */
	private static function save_currency(): void {
		$incoming = array();
		$locks    = array();
		$posted   = isset( $_POST['rates'] ) && is_array( $_POST['rates'] ) ? wp_unslash( $_POST['rates'] ) : array(); // phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$locked   = isset( $_POST['locks'] ) && is_array( $_POST['locks'] ) ? wp_unslash( $_POST['locks'] ) : array(); // phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		foreach ( Settings::CURRENCIES as $code ) {
			$incoming[ $code ] = isset( $posted[ $code ] ) ? sanitize_text_field( (string) $posted[ $code ] ) : '';
			$locks[ $code ]    = ! empty( $locked[ $code ] );
		}
		Settings::save_fx(
			array(
				'provider'     => self::posted_text( 'fx_provider' ),
				'json_url'     => self::posted_text( 'fx_json_url' ),
				'path_usd_rub' => self::posted_text( 'fx_path_usd_rub' ),
				'path_usd_irt' => self::posted_text( 'fx_path_usd_irt' ),
				'path_rub_irt' => self::posted_text( 'fx_path_rub_irt' ),
				'interval'     => self::posted_text( 'fx_interval' ),
			)
		);
		Settings::save_currency( $incoming, $locks );
		\LifeRuss\Core\Currency\Rates::reschedule();
	}

	/**
	 * Save notifications.
	 */
	private static function save_notifications(): void {
		$current  = Settings::get( 'notifications' );
		$emails   = isset( $_POST['svc_email'] ) && is_array( $_POST['svc_email'] ) ? wp_unslash( $_POST['svc_email'] ) : array(); // phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$chats    = isset( $_POST['svc_chat'] ) && is_array( $_POST['svc_chat'] ) ? wp_unslash( $_POST['svc_chat'] ) : array(); // phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$services = array();
		foreach ( Settings::NOTICE_SERVICES as $code ) {
			$services[ $code ] = array(
				'email'   => isset( $emails[ $code ] ) ? sanitize_email( (string) $emails[ $code ] ) : '',
				'chat_id' => isset( $chats[ $code ] ) ? sanitize_text_field( (string) $chats[ $code ] ) : '',
			);
		}
		Settings::update(
			'notifications',
			array(
				'telegram_enabled' => isset( $_POST['telegram_enabled'] ) ? '1' : '0', // phpcs:ignore WordPress.Security.NonceVerification.Missing
				'bot_token'        => self::posted_text( 'bot_token' ),
				'chat_id'          => self::posted_text( 'chat_id' ),
				'email_enabled'    => isset( $_POST['email_enabled'] ) ? '1' : '0', // phpcs:ignore WordPress.Security.NonceVerification.Missing
				'from_email'       => sanitize_email( self::posted_text( 'from_email' ) ),
				'from_name'        => self::posted_text( 'from_name' ) ? self::posted_text( 'from_name' ) : (string) $current['from_name'],
				'services'         => $services,
			)
		);
	}

	/**
	 * Save tracking. Scripts are intentionally unfiltered HTML.
	 */
	private static function save_tracking(): void {
		Settings::update(
			'tracking',
			array(
				'gtm_id'       => self::posted_text( 'gtm_id' ),
				'ga4_id'       => self::posted_text( 'ga4_id' ),
				'head_scripts' => self::posted_scripts( 'head_scripts' ),
				'body_scripts' => self::posted_scripts( 'body_scripts' ),
			)
		);
	}

	/**
	 * Save form copy.
	 */
	private static function save_forms(): void {
		Settings::update(
			'forms',
			array(
				'success_message'    => self::posted_area( 'success_message' ),
				'notify_email'       => sanitize_email( self::posted_text( 'notify_email' ) ),
				'turnstile_site_key' => self::posted_text( 'turnstile_site_key' ),
				'turnstile_secret'   => self::posted_text( 'turnstile_secret' ),
			)
		);
	}

	/**
	 * Save the uninstall flag. Default remains off.
	 */
	private static function save_security(): void {
		$flag = isset( $_POST['delete_data_on_uninstall'] ) ? '1' : '0'; // phpcs:ignore WordPress.Security.NonceVerification.Missing
		update_option( 'lr_delete_data_on_uninstall', $flag, false );
		$grace = absint( self::posted_text( 'totp_grace_days' ) );
		$limit = absint( self::posted_text( 'login_limit' ) );
		$days  = absint( self::posted_text( 'not_found_days' ) );
		Settings::update(
			'security',
			array(
				'delete_data_on_uninstall' => $flag,
				'totp_grace_days'          => (string) $grace,
				'login_limit'              => (string) ( $limit > 0 ? $limit : 10 ),
				'not_found_days'           => (string) ( $days > 0 ? $days : 90 ),
			)
		);
	}

	/**
	 * Save language default.
	 */
	private static function save_languages(): void {
		$code = self::posted_text( 'default_language' );
		if ( ! preg_match( '/^[a-z]{2}$/', $code ) ) {
			$code = 'fa';
		}
		Settings::update(
			'languages',
			array(
				'default_language' => $code,
				'index_incomplete' => isset( $_POST['index_incomplete'] ) ? '1' : '0', // phpcs:ignore WordPress.Security.NonceVerification.Missing
			)
		);
	}

	/**
	 * Save backup retention. No job runs yet.
	 */
	private static function save_backup(): void {
		$days = absint( self::posted_text( 'retention_days' ) );
		if ( $days < 1 ) {
			$days = 30;
		}
		Settings::update(
			'backup',
			array(
				'retention_days' => (string) $days,
			)
		);
	}
}
