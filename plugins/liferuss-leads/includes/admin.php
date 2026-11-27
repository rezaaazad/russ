<?php
/**
 * Admin inbox, lead detail, and settings.
 *
 * @package LifeRussLeads
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register the lead CPT (same slug as the theme so existing rows stay).
 */
function liferuss_leads_register_cpt() {
	register_post_type(
		'liferuss_lead',
		array(
			'labels'          => array(
				'name'          => 'درخواست‌ها',
				'singular_name' => 'درخواست',
				'edit_item'     => 'جزئیات درخواست',
				'menu_name'     => 'درخواست‌ها',
			),
			'public'          => false,
			'show_ui'         => false,
			'show_in_menu'    => false,
			'supports'        => array( 'title' ),
			'capability_type' => 'post',
		)
	);
}
add_action( 'init', 'liferuss_leads_register_cpt' );

/**
 * Backfill type slug / status on older theme-created leads.
 */
function liferuss_leads_backfill() {
	if ( get_option( 'liferuss_leads_backfilled' ) ) {
		return;
	}
	$ids = get_posts(
		array(
			'post_type'      => 'liferuss_lead',
			'post_status'    => 'any',
			'posts_per_page' => 300,
			'fields'         => 'ids',
			'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
				array(
					'key'     => '_liferuss_type_slug',
					'compare' => 'NOT EXISTS',
				),
			),
		)
	);
	foreach ( $ids as $id ) {
		update_post_meta( $id, '_liferuss_type_slug', liferuss_leads_slug_from_label( (string) get_post_meta( $id, '_liferuss_type', true ) ) );
		if ( ! get_post_meta( $id, '_liferuss_status', true ) ) {
			update_post_meta( $id, '_liferuss_status', 'new' );
		}
	}
	update_option( 'liferuss_leads_backfilled', '1' );
}
add_action( 'admin_init', 'liferuss_leads_backfill', 5 );

/**
 * Admin menu.
 */
function liferuss_leads_menu() {
	add_menu_page(
		'درخواست‌ها',
		'درخواست‌ها',
		'liferuss_manage_leads',
		'liferuss-leads',
		'liferuss_leads_render_inbox',
		'dashicons-email-alt',
		26
	);

	add_submenu_page(
		'liferuss-leads',
		'صندوق ورودی',
		'صندوق ورودی',
		'liferuss_manage_leads',
		'liferuss-leads',
		'liferuss_leads_render_inbox'
	);

	add_submenu_page(
		'liferuss-leads',
		'جزئیات درخواست',
		'جزئیات درخواست',
		'liferuss_manage_leads',
		'liferuss-leads-view',
		'liferuss_leads_render_detail'
	);

	if ( current_user_can( 'liferuss_manage_all_leads' ) || current_user_can( 'manage_options' ) ) {
		add_submenu_page(
			'liferuss-leads',
			'تنظیمات فرم‌ها',
			'تنظیمات فرم‌ها',
			'liferuss_manage_all_leads',
			'liferuss-leads-settings',
			'liferuss_leads_render_settings'
		);
	}
}
add_action( 'admin_menu', 'liferuss_leads_menu' );

/**
 * Hide the unused "جزئیات" submenu from the sidebar.
 */
function liferuss_leads_hide_view_submenu() {
	remove_submenu_page( 'liferuss-leads', 'liferuss-leads-view' );
}
add_action( 'admin_head', 'liferuss_leads_hide_view_submenu' );

/**
 * Inbox query args with staff scoping.
 *
 * @return array<string, mixed>
 */
function liferuss_leads_inbox_query_args() {
	$paged  = isset( $_GET['paged'] ) ? max( 1, absint( $_GET['paged'] ) ) : 1; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$type   = isset( $_GET['lead_type'] ) ? sanitize_key( wp_unslash( $_GET['lead_type'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$status = isset( $_GET['lead_status'] ) ? sanitize_key( wp_unslash( $_GET['lead_status'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$assignee = isset( $_GET['lead_assignee'] ) ? absint( $_GET['lead_assignee'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

	$visible = liferuss_leads_visible_types();
	$meta    = array();

	if ( null !== $visible ) {
		if ( ! $visible ) {
			$meta[] = array(
				'key'     => '_liferuss_type_slug',
				'value'   => '___none___',
				'compare' => '=',
			);
		} else {
			if ( $type && ! in_array( $type, $visible, true ) ) {
				$type = '';
			}
			$meta[] = array(
				'key'     => '_liferuss_type_slug',
				'value'   => $type ? array( $type ) : $visible,
				'compare' => 'IN',
			);
		}
	} elseif ( $type && isset( liferuss_leads_form_types()[ $type ] ) ) {
		$meta[] = array(
			'key'   => '_liferuss_type_slug',
			'value' => $type,
		);
	}

	if ( $status && isset( liferuss_leads_statuses()[ $status ] ) ) {
		if ( 'new' === $status ) {
			$meta[] = array(
				'relation' => 'OR',
				array(
					'key'   => '_liferuss_status',
					'value' => 'new',
				),
				array(
					'key'     => '_liferuss_status',
					'compare' => 'NOT EXISTS',
				),
			);
		} else {
			$meta[] = array(
				'key'   => '_liferuss_status',
				'value' => $status,
			);
		}
	}

	if ( $assignee && liferuss_leads_user_is_admin() ) {
		$meta[] = array(
			'key'   => '_liferuss_assignee',
			'value' => $assignee,
		);
	}

	$args = array(
		'post_type'      => 'liferuss_lead',
		'post_status'    => array( 'private', 'publish', 'draft' ),
		'posts_per_page' => 20,
		'paged'          => $paged,
		'orderby'        => 'date',
		'order'          => 'DESC',
	);
	if ( $meta ) {
		$args['meta_query'] = $meta; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
	}
	return $args;
}

/**
 * Render inbox.
 */
function liferuss_leads_render_inbox() {
	if ( ! current_user_can( 'liferuss_manage_leads' ) ) {
		wp_die( esc_html( 'دسترسی ندارید.' ) );
	}

	$query   = new WP_Query( liferuss_leads_inbox_query_args() );
	$type    = isset( $_GET['lead_type'] ) ? sanitize_key( wp_unslash( $_GET['lead_type'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$status  = isset( $_GET['lead_status'] ) ? sanitize_key( wp_unslash( $_GET['lead_status'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$assignee = isset( $_GET['lead_assignee'] ) ? absint( $_GET['lead_assignee'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$visible = liferuss_leads_visible_types();
	$types   = liferuss_leads_form_types();
	if ( is_array( $visible ) ) {
		$types = array_intersect_key( $types, array_flip( $visible ) );
	}
	?>
	<div class="wrap liferuss-leads-wrap">
		<h1>صندوق درخواست‌ها</h1>
		<form method="get" class="liferuss-leads-filters">
			<input type="hidden" name="page" value="liferuss-leads">
			<label>
				نوع
				<select name="lead_type">
					<option value="">همه</option>
					<?php foreach ( $types as $slug => $item ) : ?>
						<option value="<?php echo esc_attr( $slug ); ?>" <?php selected( $type, $slug ); ?>><?php echo esc_html( $item['label'] ); ?></option>
					<?php endforeach; ?>
				</select>
			</label>
			<label>
				وضعیت
				<select name="lead_status">
					<option value="">همه</option>
					<?php foreach ( liferuss_leads_statuses() as $slug => $label ) : ?>
						<option value="<?php echo esc_attr( $slug ); ?>" <?php selected( $status, $slug ); ?>><?php echo esc_html( $label ); ?></option>
					<?php endforeach; ?>
				</select>
			</label>
			<?php if ( liferuss_leads_user_is_admin() ) : ?>
				<label>
					مسئول
					<select name="lead_assignee">
						<option value="0">همه</option>
						<?php foreach ( liferuss_leads_staff_users() as $user ) : ?>
							<option value="<?php echo esc_attr( (string) $user->ID ); ?>" <?php selected( $assignee, $user->ID ); ?>><?php echo esc_html( $user->display_name ); ?></option>
						<?php endforeach; ?>
					</select>
				</label>
			<?php endif; ?>
			<button class="button">فیلتر</button>
		</form>

		<table class="widefat striped liferuss-leads-table">
			<thead>
				<tr>
					<th>نام</th>
					<th>نوع</th>
					<th>تلفن</th>
					<th>وضعیت</th>
					<th>مسئول</th>
					<th>تاریخ</th>
				</tr>
			</thead>
			<tbody>
			<?php if ( ! $query->have_posts() ) : ?>
				<tr><td colspan="6">درخواستی یافت نشد.</td></tr>
			<?php endif; ?>
			<?php
			while ( $query->have_posts() ) :
				$query->the_post();
				$id     = get_the_ID();
				$slug   = liferuss_leads_post_type_slug( $id );
				$st     = liferuss_leads_post_status_key( $id );
				$uid    = absint( get_post_meta( $id, '_liferuss_assignee', true ) );
				$staff  = $uid ? get_userdata( $uid ) : null;
				$view   = add_query_arg(
					array(
						'page' => 'liferuss-leads-view',
						'lead' => $id,
					),
					admin_url( 'admin.php' )
				);
				?>
				<tr>
					<td><a href="<?php echo esc_url( $view ); ?>"><?php echo esc_html( get_the_title() ); ?></a></td>
					<td><?php echo esc_html( liferuss_leads_type_label( $slug ) ); ?></td>
					<td><?php echo esc_html( (string) get_post_meta( $id, '_liferuss_phone', true ) ); ?></td>
					<td><?php echo esc_html( liferuss_leads_statuses()[ $st ] ); ?></td>
					<td><?php echo $staff ? esc_html( $staff->display_name ) : '—'; ?></td>
					<td><?php echo esc_html( get_the_date() ); ?></td>
				</tr>
			<?php endwhile; ?>
			<?php wp_reset_postdata(); ?>
			</tbody>
		</table>
		<?php
		if ( $query->max_num_pages > 1 ) {
			echo '<div class="tablenav"><div class="tablenav-pages">';
			echo wp_kses_post(
				paginate_links(
					array(
						'base'      => add_query_arg( 'paged', '%#%' ),
						'format'    => '',
						'current'   => max( 1, $query->get( 'paged' ) ),
						'total'     => $query->max_num_pages,
						'prev_text' => 'قبلی',
						'next_text' => 'بعدی',
					)
				)
			);
			echo '</div></div>';
		}
		?>
	</div>
	<?php
}

/**
 * Save status / assignee from the detail screen.
 */
function liferuss_leads_save_detail() {
	if ( ! isset( $_POST['liferuss_leads_detail'] ) ) {
		return;
	}
	if ( ! current_user_can( 'liferuss_manage_leads' ) ) {
		wp_die( esc_html( 'دسترسی ندارید.' ) );
	}
	check_admin_referer( 'liferuss_leads_detail' );
	$lead_id = isset( $_POST['lead_id'] ) ? absint( $_POST['lead_id'] ) : 0;
	if ( ! $lead_id || 'liferuss_lead' !== get_post_type( $lead_id ) || ! liferuss_leads_user_can_access( $lead_id ) ) {
		wp_die( esc_html( 'این درخواست برای شما قابل دسترسی نیست.' ) );
	}

	$status = isset( $_POST['lead_status'] ) ? sanitize_key( wp_unslash( $_POST['lead_status'] ) ) : 'new';
	if ( ! isset( liferuss_leads_statuses()[ $status ] ) ) {
		$status = 'new';
	}
	update_post_meta( $lead_id, '_liferuss_status', $status );

	if ( liferuss_leads_user_is_admin() ) {
		update_post_meta( $lead_id, '_liferuss_assignee', isset( $_POST['lead_assignee'] ) ? absint( $_POST['lead_assignee'] ) : 0 );
	}

	wp_safe_redirect(
		add_query_arg(
			array(
				'page'    => 'liferuss-leads-view',
				'lead'    => $lead_id,
				'updated' => '1',
			),
			admin_url( 'admin.php' )
		)
	);
	exit;
}
add_action( 'admin_init', 'liferuss_leads_save_detail' );

/**
 * Lead detail.
 */
function liferuss_leads_render_detail() {
	$lead_id = isset( $_GET['lead'] ) ? absint( $_GET['lead'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	if ( ! $lead_id || 'liferuss_lead' !== get_post_type( $lead_id ) || ! liferuss_leads_user_can_access( $lead_id ) ) {
		wp_die( esc_html( 'این درخواست برای شما قابل دسترسی نیست.' ) );
	}

	$post    = get_post( $lead_id );
	$slug    = liferuss_leads_post_type_slug( $lead_id );
	$status  = liferuss_leads_post_status_key( $lead_id );
	$uid     = absint( get_post_meta( $lead_id, '_liferuss_assignee', true ) );
	$extra   = get_post_meta( $lead_id, '_liferuss_extra', true );
	$file    = get_post_meta( $lead_id, '_liferuss_file', true );
	$updated = isset( $_GET['updated'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	?>
	<div class="wrap liferuss-leads-wrap">
		<h1><?php echo esc_html( $post->post_title ); ?></h1>
		<p><a href="<?php echo esc_url( admin_url( 'admin.php?page=liferuss-leads' ) ); ?>">← بازگشت به صندوق</a></p>
		<?php if ( $updated ) : ?>
			<div class="notice notice-success is-dismissible"><p>ذخیره شد.</p></div>
		<?php endif; ?>

		<div class="liferuss-leads-detail">
			<p><strong>تلفن:</strong> <?php echo esc_html( (string) get_post_meta( $lead_id, '_liferuss_phone', true ) ); ?></p>
			<p><strong>نوع:</strong> <?php echo esc_html( liferuss_leads_type_label( $slug ) ); ?></p>
			<?php if ( get_post_meta( $lead_id, '_liferuss_level', true ) ) : ?>
				<p><strong>مقطع:</strong> <?php echo esc_html( (string) get_post_meta( $lead_id, '_liferuss_level', true ) ); ?></p>
			<?php endif; ?>
			<?php
			if ( is_array( $extra ) ) {
				foreach ( $extra as $label => $value ) {
					if ( '' === $value ) {
						continue;
					}
					echo '<p><strong>' . esc_html( (string) $label ) . ':</strong> ' . esc_html( (string) $value ) . '</p>';
				}
			}
			if ( $file ) {
				echo '<p><strong>فایل:</strong> <a href="' . esc_url( $file ) . '" target="_blank" rel="noopener noreferrer">' . esc_html( $file ) . '</a></p>';
			}
			?>
			<p><strong>زبان فرم:</strong> <?php echo esc_html( (string) get_post_meta( $lead_id, '_liferuss_lang', true ) ); ?></p>
			<p><strong>IP:</strong> <?php echo esc_html( (string) get_post_meta( $lead_id, '_liferuss_ip', true ) ); ?></p>
			<p><strong>تاریخ:</strong> <?php echo esc_html( get_the_date( '', $post ) ); ?></p>
		</div>

		<form method="post" class="liferuss-leads-manage">
			<?php wp_nonce_field( 'liferuss_leads_detail' ); ?>
			<input type="hidden" name="liferuss_leads_detail" value="1">
			<input type="hidden" name="lead_id" value="<?php echo esc_attr( (string) $lead_id ); ?>">
			<p>
				<label>وضعیت
					<select name="lead_status">
						<?php foreach ( liferuss_leads_statuses() as $key => $label ) : ?>
							<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $status, $key ); ?>><?php echo esc_html( $label ); ?></option>
						<?php endforeach; ?>
					</select>
				</label>
			</p>
			<?php if ( liferuss_leads_user_is_admin() ) : ?>
				<p>
					<label>مسئول پیگیری
						<select name="lead_assignee">
							<option value="0">—</option>
							<?php foreach ( liferuss_leads_staff_users() as $user ) : ?>
								<option value="<?php echo esc_attr( (string) $user->ID ); ?>" <?php selected( $uid, $user->ID ); ?>><?php echo esc_html( $user->display_name ); ?></option>
							<?php endforeach; ?>
						</select>
					</label>
				</p>
			<?php endif; ?>
			<?php submit_button( 'ذخیره' ); ?>
		</form>
	</div>
	<?php
}

/**
 * Settings + staff assignment.
 */
function liferuss_leads_render_settings() {
	if ( ! liferuss_leads_user_is_admin() ) {
		wp_die( esc_html( 'دسترسی ندارید.' ) );
	}

	if ( isset( $_POST['liferuss_leads_settings_save'] ) ) {
		check_admin_referer( 'liferuss_leads_settings' );
		$enabled = array();
		foreach ( array_keys( liferuss_leads_form_types() ) as $slug ) {
			$enabled[ $slug ] = isset( $_POST['enabled'][ $slug ] ) ? '1' : '0';
		}
		update_option(
			'liferuss_leads_settings',
			array(
				'notify_email' => isset( $_POST['notify_email'] ) ? sanitize_email( wp_unslash( $_POST['notify_email'] ) ) : '',
				'enabled'      => $enabled,
			)
		);

		foreach ( liferuss_leads_staff_users() as $staff_user ) {
			if ( liferuss_leads_user_is_admin( $staff_user->ID ) ) {
				continue;
			}
			$raw   = isset( $_POST['staff_types'][ $staff_user->ID ] ) ? (array) wp_unslash( $_POST['staff_types'][ $staff_user->ID ] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			$clean = array();
			foreach ( $raw as $slug ) {
				$slug = sanitize_key( $slug );
				if ( isset( liferuss_leads_form_types()[ $slug ] ) ) {
					$clean[] = $slug;
				}
			}
			update_user_meta( $staff_user->ID, '_liferuss_allowed_forms', $clean );
		}

		echo '<div class="notice notice-success is-dismissible"><p>تنظیمات ذخیره شد.</p></div>';
	}

	$settings = liferuss_leads_settings();
	?>
	<div class="wrap liferuss-leads-wrap">
		<h1>تنظیمات فرم‌ها</h1>
		<form method="post">
			<?php wp_nonce_field( 'liferuss_leads_settings' ); ?>
			<input type="hidden" name="liferuss_leads_settings_save" value="1">
			<table class="form-table" role="presentation">
				<tr>
					<th>ایمیل اعلان</th>
					<td>
						<input type="email" class="regular-text" name="notify_email" value="<?php echo esc_attr( $settings['notify_email'] ); ?>">
						<p class="description">اگر خالی باشد از ایمیل فرم قالب استفاده می‌شود.</p>
					</td>
				</tr>
				<tr>
					<th>نوع‌های فعال</th>
					<td>
						<?php foreach ( liferuss_leads_form_types() as $slug => $type ) : ?>
							<label style="display:block;margin-bottom:6px;">
								<input type="checkbox" name="enabled[<?php echo esc_attr( $slug ); ?>]" value="1" <?php checked( liferuss_leads_type_enabled( $slug ) ); ?>>
								<?php echo esc_html( $type['label'] ); ?>
								<code><?php echo esc_html( $slug ); ?></code>
							</label>
						<?php endforeach; ?>
						<p class="description">برای افزودن نوع جدید از فیلتر <code>liferuss_leads_form_types</code> استفاده کنید.</p>
					</td>
				</tr>
			</table>

			<h2>دسترسی پشتیبانی</h2>
			<p>نقش <strong>پشتیبانی لایف روس</strong> را به کاربر بدهید، سپس نوع فرم‌های مجاز را علامت بزنید. مدیران همه را می‌بینند.</p>
			<table class="widefat striped">
				<thead>
					<tr>
						<th>کاربر</th>
						<?php foreach ( liferuss_leads_form_types() as $type ) : ?>
							<th><?php echo esc_html( $type['label'] ); ?></th>
						<?php endforeach; ?>
					</tr>
				</thead>
				<tbody>
				<?php
				$staff = liferuss_leads_staff_users();
				if ( ! $staff ) {
					echo '<tr><td colspan="' . esc_attr( (string) ( 1 + count( liferuss_leads_form_types() ) ) ) . '">هنوز کاربر پشتیبانی‌ای نیست. از کاربران → افزودن، نقش «پشتیبانی لایف روس» را انتخاب کنید.</td></tr>';
				}
				foreach ( $staff as $user ) :
					$assigned = liferuss_leads_user_types( $user->ID );
					$is_admin = liferuss_leads_user_is_admin( $user->ID );
					?>
					<tr>
						<td><?php echo esc_html( $user->display_name . ' (' . $user->user_login . ')' ); ?></td>
						<?php foreach ( array_keys( liferuss_leads_form_types() ) as $slug ) : ?>
							<td>
								<?php if ( $is_admin ) : ?>
									همه
								<?php else : ?>
									<input type="checkbox" name="staff_types[<?php echo esc_attr( (string) $user->ID ); ?>][]" value="<?php echo esc_attr( $slug ); ?>" <?php checked( in_array( $slug, $assigned, true ) ); ?>>
								<?php endif; ?>
							</td>
						<?php endforeach; ?>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
			<?php submit_button( 'ذخیره تنظیمات' ); ?>
		</form>
	</div>
	<?php
}
