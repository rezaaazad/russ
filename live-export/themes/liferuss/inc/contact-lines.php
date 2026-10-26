<?php
/**
 * Russia and UK contact lines. Empty fields stay hidden. No number is hardcoded.
 *
 * @package LifeRuss
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Theme option, then core settings. Packaged samples are not used here.
 *
 * @param string $key Option key.
 * @return string
 */
function liferuss_contact_line_value( $key ) {
	$opts  = liferuss_options();
	$value = isset( $opts[ $key ] ) ? trim( (string) $opts[ $key ] ) : '';
	if ( '' === $value && class_exists( '\LifeRuss\Core\Settings\Settings' ) ) {
		$contact = \LifeRuss\Core\Settings\Settings::get( 'contact' );
		$value   = trim( (string) ( $contact[ $key ] ?? '' ) );
	}
	return $value;
}

/**
 * tel: href, or empty when the value has no usable number.
 *
 * @param string $raw Saved value.
 * @return string
 */
function liferuss_tel_href( $raw ) {
	$raw = trim( (string) $raw );
	if ( '' === $raw ) {
		return '';
	}
	if ( 0 === strpos( strtolower( $raw ), 'tel:' ) ) {
		$raw = substr( $raw, 4 );
	}
	$digits = preg_replace( '/\D+/', '', $raw );
	if ( ! is_string( $digits ) || strlen( $digits ) < 8 ) {
		return '';
	}
	$prefix = ( isset( $raw[0] ) && '+' === $raw[0] ) ? '+' : '';
	return 'tel:' . $prefix . $digits;
}

/**
 * WhatsApp link. Accepts a wa.me URL or a number that becomes one.
 *
 * @param string $raw Saved value.
 * @return string
 */
function liferuss_wa_href( $raw ) {
	$raw = trim( (string) $raw );
	if ( '' === $raw ) {
		return '';
	}
	if ( preg_match( '#^https?://#i', $raw ) ) {
		$host = strtolower( (string) wp_parse_url( $raw, PHP_URL_HOST ) );
		$host = preg_replace( '/^www\./', '', $host );
		if ( ! in_array( $host, array( 'wa.me', 'api.whatsapp.com' ), true ) ) {
			return '';
		}
		$url = esc_url_raw( $raw );
		return $url ? $url : '';
	}
	$digits = preg_replace( '/\D+/', '', $raw );
	if ( ! is_string( $digits ) || strlen( $digits ) < 8 ) {
		return '';
	}
	return 'https://wa.me/' . $digits;
}

/**
 * Telegram link. Accepts a t.me URL or a public username.
 *
 * @param string $raw Saved value.
 * @return string
 */
function liferuss_tg_href( $raw ) {
	$raw = trim( (string) $raw );
	if ( '' === $raw ) {
		return '';
	}
	if ( preg_match( '#^https?://#i', $raw ) ) {
		$host = strtolower( (string) wp_parse_url( $raw, PHP_URL_HOST ) );
		$host = preg_replace( '/^www\./', '', $host );
		if ( ! in_array( $host, array( 't.me', 'telegram.me', 'telegram.dog' ), true ) ) {
			return '';
		}
		$url = esc_url_raw( $raw );
		return $url ? $url : '';
	}
	$handle = ltrim( $raw, '@' );
	if ( ! preg_match( '/^[A-Za-z0-9_]{4,32}$/', $handle ) ) {
		return '';
	}
	return 'https://t.me/' . rawurlencode( $handle );
}

/**
 * Lines the visitor can use. A region with every field empty is omitted.
 *
 * @return array<int, array<string, string>>
 */
function liferuss_contact_directory() {
	$lang = function_exists( 'liferuss_current_lang' ) ? liferuss_current_lang() : 'fa';
	$copy = array(
		'fa' => array(
			'ru'   => 'روسیه',
			'uk'   => 'بریتانیا',
			'call' => 'تماس',
			'wa'   => 'واتساپ',
			'tg'   => 'تلگرام',
		),
		'en' => array(
			'ru'   => 'Russia',
			'uk'   => 'UK',
			'call' => 'Call',
			'wa'   => 'WhatsApp',
			'tg'   => 'Telegram',
		),
		'ru' => array(
			'ru'   => 'Россия',
			'uk'   => 'Великобритания',
			'call' => 'Звонок',
			'wa'   => 'WhatsApp',
			'tg'   => 'Telegram',
		),
		'ar' => array(
			'ru'   => 'روسيا',
			'uk'   => 'بريطانيا',
			'call' => 'اتصال',
			'wa'   => 'واتساب',
			'tg'   => 'تيليغرام',
		),
	);
	$pack    = isset( $copy[ $lang ] ) ? $copy[ $lang ] : $copy['fa'];
	$regions = array(
		'ru' => array( 'phone_ru', 'whatsapp_ru', 'telegram_ru' ),
		'uk' => array( 'phone_uk', 'whatsapp_uk', 'telegram_uk' ),
	);
	$lines   = array();
	foreach ( $regions as $id => $keys ) {
		$phone = liferuss_contact_line_value( $keys[0] );
		$tel   = liferuss_tel_href( $phone );
		$wa    = liferuss_wa_href( liferuss_contact_line_value( $keys[1] ) );
		$tg    = liferuss_tg_href( liferuss_contact_line_value( $keys[2] ) );
		if ( '' === $tel && '' === $wa && '' === $tg ) {
			continue;
		}
		$lines[] = array(
			'id'         => $id,
			'label'      => $pack[ $id ],
			'phone_text' => $phone,
			'tel'        => $tel,
			'wa'         => $wa,
			'tg'         => $tg,
			'call'       => $pack['call'],
			'wa_label'   => $pack['wa'],
			'tg_label'   => $pack['tg'],
		);
	}
	return $lines;
}

/**
 * One external or tel anchor.
 *
 * @param string $href    URL.
 * @param string $class   Class.
 * @param string $label   Visible label.
 * @param string $icon    Icon name.
 * @param bool   $outside Open in a new tab.
 */
function liferuss_contact_anchor( $href, $class, $label, $icon, $outside ) {
	echo '<a class="' . esc_attr( $class ) . '" href="' . esc_url( $href ) . '"';
	if ( $outside ) {
		echo ' target="_blank" rel="noopener noreferrer"';
	}
	echo '>';
	if ( $icon && function_exists( 'liferuss_icon' ) ) {
		echo liferuss_icon( $icon ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
	echo '<span>' . esc_html( $label ) . '</span></a>';
}

/**
 * Print the directory for one surface. Nothing is printed when both regions are empty.
 *
 * @param string $context header|drawer|footer|channels|callbar.
 */
function liferuss_the_contact_directory( $context ) {
	$lines = liferuss_contact_directory();
	if ( ! $lines ) {
		return;
	}
	if ( 'callbar' === $context ) {
		echo '<div class="lr-callbar" role="navigation" aria-label="' . esc_attr( 'تماس' ) . '">';
		foreach ( $lines as $line ) {
			if ( $line['tel'] ) {
				liferuss_contact_anchor( $line['tel'], 'lr-callbar__link', $line['label'], 'phone', false );
			}
			if ( $line['wa'] ) {
				liferuss_contact_anchor( $line['wa'], 'lr-callbar__link', $line['label'] . ' · ' . $line['wa_label'], 'whatsapp', true );
			}
			if ( $line['tg'] ) {
				liferuss_contact_anchor( $line['tg'], 'lr-callbar__link', $line['label'] . ' · ' . $line['tg_label'], 'telegram', true );
			}
		}
		echo '</div>';
		return;
	}

	echo '<div class="lr-lines lr-lines--' . esc_attr( $context ) . '">';
	foreach ( $lines as $line ) {
		echo '<div class="lr-line">';
		echo '<span class="lr-line__name">' . esc_html( $line['label'] ) . '</span>';
		if ( $line['tel'] ) {
			$visible = 'header' === $context ? $line['call'] : $line['phone_text'];
			liferuss_contact_anchor( $line['tel'], 'lr-line__link', $visible, 'phone', false );
		}
		if ( $line['wa'] ) {
			liferuss_contact_anchor( $line['wa'], 'lr-line__link', $line['wa_label'], 'whatsapp', true );
		}
		if ( $line['tg'] ) {
			liferuss_contact_anchor( $line['tg'], 'lr-line__link', $line['tg_label'], 'telegram', true );
		}
		echo '</div>';
	}
	echo '</div>';
}
