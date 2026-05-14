<?php
/**
 * Elementor widget: drag “Biopentra login” into the header (e.g. template post 3782).
 *
 * @package Biopentra_Header_Auth
 */

namespace Biopentra_Header_Auth;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Elementor\Controls_Manager;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Group_Control_Typography;
use Elementor\Icons_Manager;
use Elementor\Repeater;
use Elementor\Widget_Base;

/**
 * @package Biopentra_Header_Auth
 */
class Elementor_Widget_Header_Auth extends Widget_Base {

	public function get_name() {
		return 'biopentra_header_auth';
	}

	public function get_title() {
		return __( 'Biopentra login', 'biopentra-header-auth' );
	}

	public function get_icon() {
		return 'eicon-lock-user';
	}

	public function get_categories() {
		return array( 'biopentra' );
	}

	public function get_keywords() {
		return array( 'login', 'logout', 'account', 'user', 'woocommerce', 'biopentra', 'header', 'menu' );
	}

	public function get_style_depends() {
		return array( 'biopentra-header-auth' );
	}

	public function get_script_depends() {
		return array( 'biopentra-header-auth' );
	}

	/**
	 * Resolve repeater row to absolute URL.
	 *
	 * @param array $item Repeater row.
	 * @return string
	 */
	protected function resolve_item_url( array $item ) {
		$type = isset( $item['link_type'] ) ? (string) $item['link_type'] : 'custom';

		switch ( $type ) {
			case 'my_account':
				return biopentra_header_auth_my_account_url();
			case 'logout':
				return wp_logout_url( home_url( '/' ) );
			case 'login_page':
				return function_exists( 'biopentra_header_auth_login_url' )
					? biopentra_header_auth_login_url()
					: wp_login_url( home_url( '/' ) );
			case 'home':
				return home_url( '/' );
			case 'register':
				return wp_registration_url();
			case 'wc_orders':
				if ( function_exists( 'wc_get_account_endpoint_url' ) ) {
					return wc_get_account_endpoint_url( 'orders' );
				}
				return biopentra_header_auth_my_account_url();
			case 'wc_cart':
				if ( function_exists( 'wc_get_cart_url' ) ) {
					return wc_get_cart_url();
				}
				return home_url( '/' );
			case 'custom':
			default:
				return ! empty( $item['item_url']['url'] ) ? $item['item_url']['url'] : '#';
		}
	}

	/**
	 * Build dropdown inner HTML from repeater settings.
	 *
	 * @param array $settings Widget display settings.
	 * @return string
	 */
	protected function build_dropdown_html( array $settings ) {
		$rows = isset( $settings['dropdown_items'] ) && is_array( $settings['dropdown_items'] )
			? $settings['dropdown_items']
			: array();

		if ( array() === $rows ) {
			return '';
		}

		$out = '';

		foreach ( $rows as $item ) {
			$url = $this->resolve_item_url( $item );
			if ( '#' === $url || '' === $url ) {
				$url = '#';
			}

			$label = isset( $item['nav_label'] ) ? $item['nav_label'] : '';
			if ( '' === trim( wp_strip_all_tags( (string) $label ) ) ) {
				continue;
			}

			$classes = array( 'biopentra-header-auth__menuitem', 'biopentra-header-auth__menuitem--row' );
			if ( ! empty( $item['divider_before'] ) && 'yes' === $item['divider_before'] ) {
				$classes[] = 'biopentra-header-auth__menuitem--divider-before';
			}
			if ( ! empty( $item['item_style'] ) && 'danger' === $item['item_style'] ) {
				$classes[] = 'biopentra-header-auth__menuitem--danger';
			}

			$target = '';
			$rel    = '';
			if ( 'custom' === ( $item['link_type'] ?? '' ) && ! empty( $item['item_url']['is_external'] ) ) {
				$target = ' target="_blank"';
				$rel    = ' rel="noopener noreferrer"';
				if ( ! empty( $item['item_url']['nofollow'] ) ) {
					$rel = ' rel="noopener noreferrer nofollow"';
				}
			}

			$icon_html = '';
			if ( ! empty( $item['item_icon']['value'] ) ) {
				ob_start();
				Icons_Manager::render_icon(
					$item['item_icon'],
					array(
						'aria-hidden' => 'true',
						'class'       => 'biopentra-header-auth__i',
					)
				);
				$rendered = trim( (string) ob_get_clean() );
				if ( '' !== $rendered ) {
					$icon_html = '<span class="biopentra-header-auth__menu-icon">' . $rendered . '</span>';
				}
			}

			$out .= sprintf(
				'<a role="menuitem" class="%1$s" href="%2$s"%3$s%4$s>%5$s<span class="biopentra-header-auth__menu-text">%6$s</span></a>',
				esc_attr( implode( ' ', $classes ) ),
				esc_url( $url ),
				$target, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built safely above.
				$rel, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				$icon_html, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Elementor icon markup.
				wp_kses_post( $label )
			);
		}

		return $out;
	}

	protected function register_controls() {
		$this->register_content_controls();
		$this->register_style_controls();
	}

	protected function register_content_controls() {
		$this->start_controls_section(
			'section_content',
			array(
				'label' => __( 'Logged out', 'biopentra-header-auth' ),
			)
		);

		$this->add_control(
			'login_text',
			array(
				'label'   => __( 'Log in label', 'biopentra-header-auth' ),
				'type'    => Controls_Manager::TEXT,
				'default' => __( 'Log in', 'biopentra-header-auth' ),
			)
		);

		$this->add_control(
			'login_url',
			array(
				'label'       => __( 'Log in link (optional)', 'biopentra-header-auth' ),
				'type'        => Controls_Manager::URL,
				'description' => __( 'Leave empty to use the same URL as the header “Log in” link (WooCommerce My Account, or wp-login.php if WC is not set up).', 'biopentra-header-auth' ),
				'options'     => false,
				'dynamic'     => array( 'active' => true ),
			)
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_logged_in',
			array(
				'label' => __( 'Logged in — trigger', 'biopentra-header-auth' ),
			)
		);

		$this->add_control(
			'identifier',
			array(
				'label'   => __( 'Show in trigger', 'biopentra-header-auth' ),
				'type'    => Controls_Manager::SELECT,
				'options' => array(
					'email'        => __( 'Email', 'biopentra-header-auth' ),
					'display_name' => __( 'Display name', 'biopentra-header-auth' ),
					'user_login'   => __( 'Username', 'biopentra-header-auth' ),
				),
				'default' => 'email',
			)
		);

		$this->end_controls_section();

		$link_options = array(
			'custom'     => __( 'Custom URL', 'biopentra-header-auth' ),
			'my_account' => __( 'My account page', 'biopentra-header-auth' ),
			'logout'     => __( 'Log out', 'biopentra-header-auth' ),
			'login_page' => __( 'WooCommerce My Account (login)', 'biopentra-header-auth' ),
			'home'       => __( 'Site home', 'biopentra-header-auth' ),
			'register'   => __( 'Registration page', 'biopentra-header-auth' ),
		);

		if ( function_exists( 'wc_get_account_endpoint_url' ) ) {
			$link_options['wc_orders'] = __( 'WooCommerce — Orders', 'biopentra-header-auth' );
		}
		if ( function_exists( 'wc_get_cart_url' ) ) {
			$link_options['wc_cart'] = __( 'WooCommerce — Cart', 'biopentra-header-auth' );
		}

		$repeater = new Repeater();

		$repeater->add_control(
			'nav_label',
			array(
				'label'       => __( 'Label', 'biopentra-header-auth' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => __( 'Menu item', 'biopentra-header-auth' ),
				'label_block' => true,
				'dynamic'     => array( 'active' => true ),
			)
		);

		$repeater->add_control(
			'link_type',
			array(
				'label'   => __( 'Link', 'biopentra-header-auth' ),
				'type'    => Controls_Manager::SELECT,
				'options' => $link_options,
				'default' => 'custom',
			)
		);

		$repeater->add_control(
			'item_url',
			array(
				'label'       => __( 'Custom URL', 'biopentra-header-auth' ),
				'type'        => Controls_Manager::URL,
				'placeholder' => __( 'https://', 'biopentra-header-auth' ),
				'dynamic'     => array( 'active' => true ),
				'condition'   => array(
					'link_type' => 'custom',
				),
			)
		);

		$repeater->add_control(
			'item_icon',
			array(
				'label' => __( 'Icon', 'biopentra-header-auth' ),
				'type'  => Controls_Manager::ICONS,
				'skin'  => 'inline',
			)
		);

		$repeater->add_control(
			'item_style',
			array(
				'label'   => __( 'Style', 'biopentra-header-auth' ),
				'type'    => Controls_Manager::SELECT,
				'options' => array(
					'default' => __( 'Default', 'biopentra-header-auth' ),
					'danger'  => __( 'Emphasis (e.g. logout)', 'biopentra-header-auth' ),
				),
				'default' => 'default',
			)
		);

		$repeater->add_control(
			'divider_before',
			array(
				'label'        => __( 'Separator above', 'biopentra-header-auth' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'Yes', 'biopentra-header-auth' ),
				'label_off'    => __( 'No', 'biopentra-header-auth' ),
				'return_value' => 'yes',
				'default'      => '',
			)
		);

		$this->start_controls_section(
			'section_dropdown',
			array(
				'label' => __( 'Logged in — dropdown menu', 'biopentra-header-auth' ),
			)
		);

		$this->add_control(
			'dropdown_help',
			array(
				'type'            => Controls_Manager::RAW_HTML,
				'raw'             => __( 'Add rows for each menu line. Pick a preset link (My account, Log out, …) or <strong>Custom URL</strong> for anything else. Icons are optional.', 'biopentra-header-auth' ),
				'content_classes' => 'elementor-descriptor',
			)
		);

		$this->add_control(
			'dropdown_items',
			array(
				'type'        => Controls_Manager::REPEATER,
				'fields'      => $repeater->get_controls(),
				'title_field' => '{{{ nav_label }}}',
				'default'     => array(
					array(
						'nav_label'      => __( 'My account', 'biopentra-header-auth' ),
						'link_type'      => 'my_account',
						'item_style'     => 'default',
						'divider_before' => '',
					),
					array(
						'nav_label'      => __( 'Log out', 'biopentra-header-auth' ),
						'link_type'      => 'logout',
						'item_style'     => 'danger',
						'divider_before' => '',
					),
				),
			)
		);

		$this->end_controls_section();
	}

	protected function register_style_controls() {
		$this->start_controls_section(
			'section_style_trigger',
			array(
				'label' => __( 'Trigger', 'biopentra-header-auth' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_control(
			'trigger_color',
			array(
				'label'     => __( 'Color', 'biopentra-header-auth' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .biopentra-header-auth' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'trigger_hover_color',
			array(
				'label'     => __( 'Hover color', 'biopentra-header-auth' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .biopentra-header-auth__trigger:hover' => 'color: {{VALUE}};',
					'{{WRAPPER}} .biopentra-header-auth__trigger:focus-visible' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'trigger_typography',
				'label'    => __( 'Typography', 'biopentra-header-auth' ),
				'selector' => '{{WRAPPER}} .biopentra-header-auth__trigger',
			)
		);

		$this->add_responsive_control(
			'trigger_label_max_width',
			array(
				'label'      => __( 'Label max width', 'biopentra-header-auth' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px', '%', 'rem' ),
				'range'      => array(
					'px' => array( 'min' => 80, 'max' => 400 ),
				),
				'selectors'  => array(
					'{{WRAPPER}} .biopentra-header-auth__label' => 'max-width: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_responsive_control(
			'trigger_icon_size',
			array(
				'label'      => __( 'User icon size', 'biopentra-header-auth' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 12, 'max' => 36 ) ),
				'selectors'  => array(
					'{{WRAPPER}} .biopentra-header-auth__svg' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_style_dropdown',
			array(
				'label' => __( 'Dropdown panel', 'biopentra-header-auth' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_responsive_control(
			'dropdown_min_width',
			array(
				'label'      => __( 'Min width', 'biopentra-header-auth' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px', 'rem' ),
				'range'      => array(
					'px' => array( 'min' => 160, 'max' => 420 ),
				),
				'selectors'  => array(
					'{{WRAPPER}} .biopentra-header-auth__dropdown' => 'min-width: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_responsive_control(
			'dropdown_radius',
			array(
				'label'      => __( 'Border radius', 'biopentra-header-auth' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%' ),
				'selectors'  => array(
					'{{WRAPPER}} .biopentra-header-auth__dropdown' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->add_control(
			'dropdown_bg',
			array(
				'label'     => __( 'Background', 'biopentra-header-auth' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .biopentra-header-auth__dropdown' => 'background-color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'dropdown_border_color',
			array(
				'label'     => __( 'Border color', 'biopentra-header-auth' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .biopentra-header-auth__dropdown' => 'border-color: {{VALUE}};',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			array(
				'name'     => 'dropdown_shadow',
				'label'    => __( 'Shadow', 'biopentra-header-auth' ),
				'selector' => '{{WRAPPER}} .biopentra-header-auth__dropdown',
			)
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_style_items',
			array(
				'label' => __( 'Dropdown items', 'biopentra-header-auth' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'item_typography',
				'label'    => __( 'Typography', 'biopentra-header-auth' ),
				'selector' => '{{WRAPPER}} .biopentra-header-auth__menu-text',
			)
		);

		$this->add_control(
			'item_color',
			array(
				'label'     => __( 'Text color', 'biopentra-header-auth' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .biopentra-header-auth__menuitem' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'item_hover_bg',
			array(
				'label'     => __( 'Hover background', 'biopentra-header-auth' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .biopentra-header-auth__menuitem:hover' => 'background-color: {{VALUE}};',
					'{{WRAPPER}} .biopentra-header-auth__menuitem:focus-visible' => 'background-color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'item_hover_color',
			array(
				'label'     => __( 'Hover text color', 'biopentra-header-auth' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .biopentra-header-auth__menuitem:hover .biopentra-header-auth__menu-text' => 'color: {{VALUE}};',
					'{{WRAPPER}} .biopentra-header-auth__menuitem:focus-visible .biopentra-header-auth__menu-text' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_responsive_control(
			'item_padding',
			array(
				'label'      => __( 'Padding', 'biopentra-header-auth' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', 'em' ),
				'selectors'  => array(
					'{{WRAPPER}} .biopentra-header-auth__menuitem' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->add_responsive_control(
			'item_icon_gap',
			array(
				'label'      => __( 'Gap (icon ↔ text)', 'biopentra-header-auth' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 0, 'max' => 24 ) ),
				'selectors'  => array(
					'{{WRAPPER}} .biopentra-header-auth__menuitem--row' => 'gap: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_responsive_control(
			'menu_icon_size',
			array(
				'label'      => __( 'Item icon size', 'biopentra-header-auth' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 10, 'max' => 32 ) ),
				'selectors'  => array(
					'{{WRAPPER}} .biopentra-header-auth__menu-icon' => 'font-size: {{SIZE}}{{UNIT}};',
					'{{WRAPPER}} .biopentra-header-auth__menu-icon svg' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_control(
			'item_danger_color',
			array(
				'label'     => __( 'Emphasis hover color', 'biopentra-header-auth' ),
				'type'      => Controls_Manager::COLOR,
				'description' => __( 'Used for items with Style → Emphasis.', 'biopentra-header-auth' ),
				'selectors' => array(
					'{{WRAPPER}} .biopentra-header-auth__menuitem--danger:hover' => 'color: {{VALUE}};',
					'{{WRAPPER}} .biopentra-header-auth__menuitem--danger:focus-visible' => 'color: {{VALUE}};',
					'{{WRAPPER}} .biopentra-header-auth__menuitem--danger:hover .biopentra-header-auth__menu-text' => 'color: {{VALUE}};',
				),
			)
		);

		$this->end_controls_section();
	}

	protected function render() {
		$s = $this->get_settings_for_display();

		$dropdown = $this->build_dropdown_html( $s );

		$args = array(
			'login_text'      => $s['login_text'] ?? __( 'Log in', 'biopentra-header-auth' ),
			'logout_text'     => __( 'Log out', 'biopentra-header-auth' ),
			'my_account_text' => __( 'My account', 'biopentra-header-auth' ),
			'identifier'      => $s['identifier'] ?? 'email',
			'dropdown_html'   => $dropdown,
			'login_link'      => isset( $s['login_url'] ) && is_array( $s['login_url'] ) ? $s['login_url'] : array(),
		);

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in biopentra_header_auth_get_html / markup helpers.
		echo biopentra_header_auth_get_html( $args );
	}
}
