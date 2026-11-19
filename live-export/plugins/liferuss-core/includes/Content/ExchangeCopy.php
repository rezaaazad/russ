<?php
/**
 * Publishes the exchange child pages when they are still the short seed.
 *
 * Edited pages are left alone. No prices, licenses, or testimonials are written here.
 *
 * @package LifeRussCore
 */

namespace LifeRuss\Core\Content;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * One-time upgrade for the exchange section.
 */
class ExchangeCopy {

	/**
	 * Leads that the earlier seed was allowed to publish.
	 *
	 * @var string[]
	 */
	private const PREVIOUS = array(
		'حواله به روسیه برای شهریه، خوابگاه یا هزینهٔ زندگی، بعد از استعلام نرخ و تأیید هویت انجام می‌شود.',
		'پرداخت شهریه باید با فاکتور همان دانشگاه و نام دانشجو یکی باشد.',
		'استعلام معمول بین تومان، دلار، یورو و روبل است. ارز دیگر را فقط وقتی مسیر واقعی دارد می‌پذیریم.',
		'تبدیل بین تومان، دلار، یورو، روبل و تتر با همان جدول نرخ روز. حاصل، قیمت معامله نیست.',
	);

	/**
	 * Hook the upgrade after the other copy seeders.
	 */
	public static function hooks(): void {
		add_action( 'init', array( self::class, 'maybe' ), 52 );
	}

	/**
	 * Fill the three children and the calculator once the hub exists.
	 */
	public static function maybe(): void {
		if ( '1' === (string) get_option( 'lr_exchange_copy', '' ) ) {
			return;
		}
		$parent = get_page_by_path( 'exchange' );
		if ( ! $parent instanceof \WP_Post ) {
			return;
		}
		self::seo(
			(int) $parent->ID,
			'استعلام نرخ روز ارز',
			'جدول نرخ روز با نام منبع و زمان هر ردیف. این اعداد مرجع بازار هستند و نرخ نهایی در پاسخ استعلام نوشته می‌شود.'
		);
		foreach ( Library::pages() as $page ) {
			if ( 'exchange' !== (string) ( $page['parent'] ?? '' ) ) {
				continue;
			}
			if ( ! in_array( (string) $page['slug'], array( 'transfer-to-russia', 'tuition-payment', 'currencies' ), true ) ) {
				continue;
			}
			self::fill( $page, (int) $parent->ID );
		}
		self::calculator( (int) $parent->ID );
		update_option( 'lr_exchange_copy', '1', false );
	}

	/**
	 * Create or upgrade one child.
	 *
	 * @param array<string, mixed> $page      Library row.
	 * @param int                  $parent_id Hub id.
	 */
	private static function fill( array $page, int $parent_id ): void {
		$path = 'exchange/' . (string) $page['slug'];
		$post = get_page_by_path( $path );
		$id   = $post instanceof \WP_Post ? (int) $post->ID : 0;
		if ( $id < 1 ) {
			$id = (int) wp_insert_post(
				array(
					'post_type'    => 'page',
					'post_status'  => 'publish',
					'post_title'   => (string) $page['title'],
					'post_name'    => (string) $page['slug'],
					'post_parent'  => $parent_id,
					'post_content' => (string) $page['content'],
				)
			);
		}
		if ( $id < 1 ) {
			return;
		}
		$current = get_post( $id );
		if ( $current instanceof \WP_Post && self::replaceable( $current ) ) {
			wp_update_post(
				array(
					'ID'           => $id,
					'post_title'   => (string) $page['title'],
					'post_content' => (string) $page['content'],
					'post_status'  => 'publish',
				)
			);
			update_post_meta( $id, '_lr_eyebrow', (string) $page['eyebrow'] );
			update_post_meta( $id, '_lr_lead', (string) $page['lead'] );
			update_post_meta( $id, '_lr_sections', wp_json_encode( $page['sections'], JSON_UNESCAPED_UNICODE ) );
			update_post_meta( $id, '_lr_faq_ids', self::faqs( (string) $page['slug'], $page['faqs'] ) );
			update_post_meta( $id, '_lr_testimonial_ids', '' );
		}
		update_post_meta( $id, '_wp_page_template', 'templates/path.php' );
		if ( '' === (string) get_post_meta( $id, '_lr_form', true ) ) {
			update_post_meta( $id, '_lr_form', 'exchange' );
		}
		if ( '' === (string) get_post_meta( $id, '_lr_schema', true ) ) {
			update_post_meta( $id, '_lr_schema', 'service' );
		}
		self::slot( $id );
		self::seo( $id, (string) $page['title'], (string) $page['lead'] );
	}

	/**
	 * Calculator keeps the shortcode and gains a short explanation if it is still the seed.
	 *
	 * @param int $parent_id Hub id.
	 */
	private static function calculator( int $parent_id ): void {
		$post = get_page_by_path( 'exchange/calculator' );
		$id   = $post instanceof \WP_Post ? (int) $post->ID : 0;
		if ( $id < 1 ) {
			return;
		}
		$lead = (string) get_post_meta( $id, '_lr_lead', true );
		if ( in_array( $lead, self::PREVIOUS, true ) || '' === $lead ) {
			update_post_meta( $id, '_lr_lead', 'تبدیل مرجع بین تومان، دلار، یورو، روبل و تتر. اگر نرخ تومانی یک ارز در جدول نباشد، آن ارز در ماشین‌حساب نمی‌آید. حاصل، نرخ معامله نیست.' );
			update_post_meta(
				$id,
				'_lr_sections',
				wp_json_encode(
					array(
						array(
							'title' => 'چه چیزی حساب می‌شود',
							'body'  => 'فقط زوج‌هایی که هر دو طرف‌شان نرخ تومانی روی جدول دارند. دلار، یورو، روبل و تتر از همان تابلو می‌آیند. روبل بانکی یا نرخ فورا بانک وارد این حساب نمی‌شود چون واحدشان تومان نیست.',
						),
						array(
							'title' => 'قدم بعدی',
							'body'  => 'برای نرخ قابل معامله، فرم استعلام را بفرستید. پاسخ، نرخ همان درخواست است نه عدد ماشین‌حساب.',
						),
					),
					JSON_UNESCAPED_UNICODE
				)
			);
			update_post_meta(
				$id,
				'_lr_faq_ids',
				self::faqs(
					'calculator',
					array(
						array(
							'q' => 'حاصل ماشین‌حساب را می‌توانم ملاک واریز بگیرم؟',
							'a' => 'خیر. ماشین‌حساب مرجع است. نرخ واریز در پاسخ استعلام نوشته می‌شود.',
						),
						array(
							'q' => 'چرا یک ارز در فهرست نیست؟',
							'a' => 'وقتی تابلو برای آن ارز نرخ تومانی ندارد، از فهرست حذف می‌شود. عدد جایگزین ساخته نمی‌شود.',
						),
					)
				)
			);
		}
		if ( '' === (string) get_post_meta( $id, '_lr_schema', true ) ) {
			update_post_meta( $id, '_lr_schema', 'service' );
		}
		if ( '' === (string) get_post_meta( $id, '_lr_form', true ) ) {
			update_post_meta( $id, '_lr_form', 'exchange' );
		}
		self::slot( $id );
		self::seo( $id, 'ماشین‌حساب نرخ مرجع', (string) get_post_meta( $id, '_lr_lead', true ) );
		unset( $parent_id );
	}

	/**
	 * Persian title and description. Existing text is left in place.
	 *
	 * @param int    $id    Page id.
	 * @param string $title Document title.
	 * @param string $lead  Meta description.
	 */
	private static function seo( int $id, string $title, string $lead ): void {
		if ( $id < 1 ) {
			return;
		}
		if ( '' === (string) get_post_meta( $id, '_lr_seo_title', true ) && '' !== $title ) {
			update_post_meta( $id, '_lr_seo_title', $title );
		}
		if ( '' === (string) get_post_meta( $id, '_lr_seo_description', true ) && '' !== $lead ) {
			update_post_meta( $id, '_lr_seo_description', $lead );
		}
	}

	/**
	 * True when the stored page is still seed or empty.
	 *
	 * @param \WP_Post $post Page.
	 */
	private static function replaceable( \WP_Post $post ): bool {
		$lead = (string) get_post_meta( $post->ID, '_lr_lead', true );
		if ( in_array( $lead, self::PREVIOUS, true ) ) {
			return true;
		}
		$blob = $post->post_title . ' ' . $post->post_content . ' ' . $lead;
		if ( class_exists( Copy::class ) && Copy::is_placeholder( $blob ) ) {
			return true;
		}
		$plain = trim( wp_strip_all_tags( $post->post_content ) );
		return '' === $plain && '' === $lead;
	}

	/**
	 * Empty image slot. Width and height are fixed so the frame does not jump when the file arrives.
	 *
	 * @param int $id Page id.
	 */
	private static function slot( int $id ): void {
		if ( '' === (string) get_post_meta( $id, '_lr_slot_w', true ) ) {
			update_post_meta( $id, '_lr_slot_w', '1600' );
		}
		if ( '' === (string) get_post_meta( $id, '_lr_slot_h', true ) ) {
			update_post_meta( $id, '_lr_slot_h', '900' );
		}
	}

	/**
	 * FAQ posts for one slug.
	 *
	 * @param string               $slug Slug.
	 * @param array<int, mixed>    $faqs Question rows.
	 */
	private static function faqs( string $slug, array $faqs ): string {
		$ids = array();
		$i   = 1;
		foreach ( $faqs as $faq ) {
			$question = '';
			$answer   = '';
			if ( isset( $faq['q'], $faq['a'] ) ) {
				$question = (string) $faq['q'];
				$answer   = (string) $faq['a'];
			} elseif ( isset( $faq[0], $faq[1] ) ) {
				$question = (string) $faq[0];
				$answer   = (string) $faq[1];
			}
			if ( '' === $question || '' === $answer ) {
				continue;
			}
			$name  = 'exchange-' . $slug . '-' . $i;
			$found = get_posts(
				array(
					'name'           => $name,
					'post_type'      => 'lr_faq',
					'post_status'    => 'any',
					'posts_per_page' => 1,
					'fields'         => 'ids',
				)
			);
			if ( $found ) {
				$fid = (int) $found[0];
				wp_update_post(
					array(
						'ID'           => $fid,
						'post_title'   => $question,
						'post_content' => $answer,
						'post_status'  => 'publish',
					)
				);
			} else {
				$fid = (int) wp_insert_post(
					array(
						'post_type'    => 'lr_faq',
						'post_status'  => 'publish',
						'post_title'   => $question,
						'post_name'    => $name,
						'post_content' => $answer,
					)
				);
			}
			if ( $fid > 0 ) {
				$ids[] = $fid;
			}
			++$i;
		}
		return implode( ',', $ids );
	}
}
