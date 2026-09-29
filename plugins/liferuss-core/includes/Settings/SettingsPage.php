<?php
/**
 * Settings screen.
 *
 * @package LifeRussCore
 */

namespace LifeRuss\Core\Settings;

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

		echo '<div class="wrap lr-wrap">';
		echo '<h1>' . esc_html__( 'تنظیمات لایف‌روس', 'liferuss-core' ) . '</h1>';
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
		echo '<tr><td colspan="2"><p>' . esc_html__( 'نرخ دستی: چند دلار آمریکا برابر یک واحد از ارز است. با ذخیره، amount_usd شهریه‌های همان ارز دوباره حساب می‌شود.', 'liferuss-core' ) . '</p></td></tr>';
		foreach ( Settings::CURRENCIES as $code ) {
			$rate = $v['rates'][ $code ];
			$meta = '';
			if ( ! empty( $rate['updated_at'] ) ) {
				$meta = sprintf(
					/* translators: 1: datetime UTC, 2: user id */
					__( 'آخرین به‌روزرسانی: %1$s (کاربر %2$d)', 'liferuss-core' ),
					(string) $rate['updated_at'],
					(int) $rate['updated_by']
				);
			}
			echo '<tr><th scope="row"><label for="rate_' . esc_attr( $code ) . '">' . esc_html( $code ) . '</label></th><td>';
			echo '<input class="regular-text" type="text" inputmode="decimal" id="rate_' . esc_attr( $code ) . '" name="rates[' . esc_attr( $code ) . ']" value="' . esc_attr( (string) $rate['usd_per_unit'] ) . '">';
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
	 * Form copy. The public form endpoint comes later.
	 */
	private static function fields_forms(): void {
		$v = Settings::get( 'forms' );
		self::area_row( 'success_message', __( 'پیام موفقیت', 'liferuss-core' ), (string) $v['success_message'] );
		self::text_row( 'notify_email', __( 'ایمیل اطلاع‌رسانی پیش‌فرض', 'liferuss-core' ), (string) $v['notify_email'], 'email' );
	}

	/**
	 * Security flags. Two-factor login is deferred.
	 */
	private static function fields_security(): void {
		$v = Settings::get( 'security' );
		echo '<tr><th scope="row">' . esc_html__( 'حذف داده هنگام حذف افزونه', 'liferuss-core' ) . '</th><td>';
		echo '<label><input type="checkbox" name="delete_data_on_uninstall" value="1" ' . checked( '1', (string) $v['delete_data_on_uninstall'], false ) . '> ';
		echo esc_html__( 'با حذف افزونه، جدول‌ها، نقش‌های lr_* و تنظیمات پاک شوند. پیش‌فرض خاموش است.', 'liferuss-core' );
		echo '</label></td></tr>';
		echo '<tr><td colspan="2"><p class="description">' . esc_html__( 'ورود دومرحله‌ای در این نسخه پیاده نشده است. متای lr_2fa_enabled برای مرحلهٔ بعد نگه داشته می‌شود.', 'liferuss-core' ) . '</p></td></tr>';
	}

	/**
	 * Language placeholder. Polylang stays out of this release.
	 */
	private static function fields_languages(): void {
		$v = Settings::get( 'languages' );
		self::text_row( 'default_language', __( 'زبان پیش‌فرض', 'liferuss-core' ), (string) $v['default_language'] );
		echo '<tr><td colspan="2"><p class="description">' . esc_html__( 'اتصال Polylang و جدول ترجمه‌ها در نسخهٔ بعدی است.', 'liferuss-core' ) . '</p></td></tr>';
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
		$posted   = isset( $_POST['rates'] ) && is_array( $_POST['rates'] ) ? wp_unslash( $_POST['rates'] ) : array(); // phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		foreach ( Settings::CURRENCIES as $code ) {
			$incoming[ $code ] = isset( $posted[ $code ] ) ? sanitize_text_field( (string) $posted[ $code ] ) : '';
		}
		Settings::save_currency( $incoming );
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
				'success_message' => self::posted_area( 'success_message' ),
				'notify_email'    => sanitize_email( self::posted_text( 'notify_email' ) ),
			)
		);
	}

	/**
	 * Save the uninstall flag. Default remains off.
	 */
	private static function save_security(): void {
		$flag = isset( $_POST['delete_data_on_uninstall'] ) ? '1' : '0'; // phpcs:ignore WordPress.Security.NonceVerification.Missing
		update_option( 'lr_delete_data_on_uninstall', $flag, false );
		Settings::update(
			'security',
			array(
				'delete_data_on_uninstall' => $flag,
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
