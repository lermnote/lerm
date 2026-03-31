<?php // phpcs:disable WordPress.Files.FileName
declare( strict_types=1 );

namespace Lerm\View;

/**
 * 导航栏登录/用户菜单项
 *
 * 原来混在 AjaxLogin 里，与 Ajax 逻辑无关，单独提取。
 *
 * bootstrap.php 中按需初始化：
 *   if ( $login_options['menu_login_item'] ) {
 *       NavMenu::init( $login_options );
 *   }
 *
 * @package Lerm\View
 */
final class NavMenu {

	private static array $args = array(
		'login_page_id'       => 0,
		'account_page_url'    => '',
		'login_redirect_url'  => '',
		'logout_redirect_url' => '',
	);

	public static function init( array $args = array() ): void {
		self::$args = wp_parse_args( $args, self::$args );
		add_filter( 'wp_nav_menu_items', array( __CLASS__, 'inject_login_item' ), 10, 2 );
	}

	/**
	 * 在 primary 菜单末尾注入登录按钮 / 用户下拉菜单
	 */
	public static function inject_login_item( string $items, object $args ): string {
		if ( 'primary' !== ( $args->theme_location ?? '' ) ) {
			return $items;
		}

		if ( is_user_logged_in() ) {
			$items .= self::render_user_dropdown();
		} else {
			$items .= self::render_login_link();
		}

		return $items;
	}

	// -------------------------------------------------------------------------
	// 私有渲染方法
	// -------------------------------------------------------------------------

	private static function render_user_dropdown(): string {
		$user        = wp_get_current_user();
		$account_url = esc_url( self::$args['account_page_url'] ? self::$args['account_page_url'] : home_url( '/' ) );
		$logout_url  = esc_url( wp_logout_url( self::$args['logout_redirect_url'] ? self::$args['logout_redirect_url'] : home_url( '/' ) ) );

		ob_start();
		?>
		<li class="nav-item dropdown menu-item-login">
			<a class="nav-link dropdown-toggle" href="#" role="button"
				data-bs-toggle="dropdown" aria-expanded="false">
				<?php echo get_avatar( $user->ID, 20 ); ?>
				<?php echo esc_html( $user->user_login ); ?>
			</a>
			<ul class="dropdown-menu">
				<li class="text-center">
					<h6 class="dropdown-header"><?php echo get_avatar( $user->ID, 64 ); ?></h6>
					<span class="text-info"><?php echo esc_html( $user->display_name ); ?></span>
				</li>
				<li>
					<a class="dropdown-item" href="<?php echo esc_attr( $account_url ); ?>">
						<?php esc_html_e( 'Account', 'lerm' ); ?>
					</a>
				</li>
				<li><hr class="dropdown-divider"></li>
				<li>
					<a class="dropdown-item" href="<?php echo esc_attr( $logout_url ); ?>">
						<?php esc_html_e( 'Log out', 'lerm' ); ?>
					</a>
				</li>
			</ul>
		</li>
		<?php
		return (string) ob_get_clean();
	}

	private static function render_login_link(): string {
		$login_url = self::$args['login_page_id']
			? (string) get_permalink( absint( self::$args['login_page_id'] ) )
			: wp_login_url();

		return sprintf(
			'<li class="nav-item menu-item-login"><a class="nav-link" href="%s">%s</a></li>',
			esc_url( $login_url ),
			esc_html__( 'Login', 'lerm' )
		);
	}
}
