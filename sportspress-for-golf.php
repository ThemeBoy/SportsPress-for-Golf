<?php
/*
Plugin Name: SportsPress for Golf
Plugin URI: http://themeboy.com/
Description: A suite of golf features for SportsPress.
Author: ThemeBoy
Author URI: http://themeboy.com/
Version: 0.9.1
*/

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'SportsPress_Golf' ) ) :

/**
 * Main SportsPress Golf Class
 *
 * @class SportsPress_Golf
 * @version	0.9.1
 */
class SportsPress_Golf {

	/**
	 * Constructor.
	 */
	public function __construct() {
		// Define constants
		$this->define_constants();

		// Require SportsPress core
		add_action( 'tgmpa_register', array( $this, 'require_core' ) );

		// Enqueue scripts
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ), 30 );

		// Add par to event performance
		add_action( 'sportspress_event_performance_meta_box_table_footer', array( $this, 'meta_box_table_footer' ), 10, 7 );
		add_action( 'sportspress_event_performance_table_footer', array( $this, 'table_footer' ), 10, 4 );
		add_filter( 'sportspress_event_performance_show_footer', '__return_true' );

		// Add par to event result equation builder
		add_filter( 'sportspress_equation_options', array( $this, 'equation_options' ) );
		add_filter( 'sportspress_event_result_equation_vars', array( $this, 'equation_vars' ), 10, 3 );

		// Format results to reflect if over or under par
		add_filter( 'sportspress_event_results', array( $this, 'results' ), 10, 4 );

		// Add number of holes to event meta box
		add_action( 'sportspress_event_details_meta_box', array( $this, 'meta_box_holes' ) );
		add_action( 'sportspress_process_sp_event_meta', array( $this, 'save_holes' ), 10, 2 );
		add_filter( 'sportspress_event_performance_labels', array( $this, 'limit_labels' ), 10, 2 );
		add_filter( 'sportspress_event_details', array( $this, 'event_details' ), 10, 2 );

		// Change text to reflect golf terminology
		add_filter( 'gettext', array( $this, 'gettext' ), 20, 3 );

		// Hide player positions
		add_filter( 'sportspress_has_positions', '__return_false' );
		add_filter( 'sportspress_player_admin_columns', array( $this, 'remove_player_admin_position_column' ) );
		add_filter( 'sportspress_taxonomies', array( $this, 'taxonomies' ) );

		// Define default sport
		add_filter( 'sportspress_default_sport', array( $this, 'default_sport' ) );

		// Include required files
		$this->includes();
	}

	/**
	 * Define constants.
	*/
	private function define_constants() {
		if ( !defined( 'SP_GOLF_VERSION' ) )
			define( 'SP_GOLF_VERSION', '0.9.1' );

		if ( !defined( 'SP_GOLF_URL' ) )
			define( 'SP_GOLF_URL', plugin_dir_url( __FILE__ ) );

		if ( !defined( 'SP_GOLF_DIR' ) )
			define( 'SP_GOLF_DIR', plugin_dir_path( __FILE__ ) );
	}

	/**
	 * Enqueue styles.
	 */
	public static function admin_enqueue_scripts() {
		wp_enqueue_style( 'sportspress-golf-admin', SP_GOLF_URL . 'css/admin.css', array( 'sportspress-admin-menu-styles' ), '0.9' );
	}

	/**
	 * Include required files.
	*/
	private function includes() {
		require_once dirname( __FILE__ ) . '/includes/class-tgm-plugin-activation.php';
	}

	/**
	 * Require SportsPress core.
	*/
	public static function require_core() {
		$plugins = array(
			array(
				'name'        => 'SportsPress',
				'slug'        => 'sportspress',
				'required'    => true,
				'version'     => '2.3',
				'is_callable' => array( 'SportsPress', 'instance' ),
			),
		);

		$config = array(
			'default_path' => '',
			'menu'         => 'tgmpa-install-plugins',
			'has_notices'  => true,
			'dismissable'  => true,
			'is_automatic' => true,
			'message'      => '',
			'strings'      => array(
				'nag_type' => 'updated'
			)
		);

		tgmpa( $plugins, $config );
	}

	/**
	 * Display number of holes in event edit page.
	*/
	public function meta_box_holes( $post = null ) {
		if ( ! $post ) return;
		$holes = get_post_meta( $post->ID, 'sp_holes', true );
		?>
		<div class="sp-event-holes-field">
			<p><strong><?php _e( 'Round', 'sportspress' ); ?></strong></p>
			<p>
				<input name="sp_holes" type="number" step="1" min="0" class="small-text" placeholder="18" value="<?php echo $holes; ?>">
				<?php _e( 'holes', 'sportspress' ); ?>
			</p>
		</div>
		<?php
	}

	/**
	 * Save holes data.
	 */
	public static function save_holes( $post_id, $post ) {
		update_post_meta( $post_id, 'sp_holes', sp_array_value( $_POST, 'sp_holes', array() ) );
	}

	/**
	 * Limit number of holes in scorecard.
	 */
	public static function limit_labels( $labels, $post = null ) {
		if ( ! $post ) return;
		$holes = (int) get_post_meta( $post->ID, 'sp_holes', true );
		if ( $holes ) {
			$labels = array_slice( $labels, 0, $holes, true );
		}
		return $labels;
	}

	/**
	 * Display holes in event details.
	 */
	public static function event_details( $data, $post_id = null ) {
		if ( ! $post_id ) return;
		$holes = (int) get_post_meta( $post_id, 'sp_holes', true );
		if ( ! $holes ) $holes = 18;
		$data[ __( 'Holes', 'sportspress' ) ] = $holes;
		return $data;
	}

	/**
	 * Display par in event edit page.
	*/
	public function meta_box_table_footer( $data = array(), $labels = array(), $team_id = 0, $positions = array(), $status = true, $sortable = true, $numbers = true ) {
		?>
		<tr class="sp-row sp-post sp-par">
			<?php if ( $sortable ) { ?>
				<td>&nbsp;</td>
			<?php } ?>
			<?php if ( $numbers ) { ?>
				<td>&nbsp;</td>
			<?php } ?>
			<td><strong><?php _e( 'Par', 'sportspress' ); ?></strong></td>
			<?php if ( ! empty( $positions ) ) { ?>
				<td>&nbsp;</td>
			<?php } ?>
			<?php foreach( $labels as $column => $label ):
				$pars = sp_array_value( $data, -1, array() );
				$value = sp_array_value( $pars, $column, '' );
				?>
				<td><input type="text" name="sp_players[-1][-1][<?php echo $column; ?>]" value="<?php echo $value; ?>" /></td>
			<?php endforeach; ?>
			<?php if ( $status ) { ?>
				<td>&nbsp;</td>
			<?php } ?>
		</tr>
		<?php
	}

	/**
	 * Display par in event page.
	*/
	public function table_footer( $data = array(), $labels = array(), $position = null, $performance_ids = null ) {
		$show_players = get_option( 'sportspress_event_show_players', 'yes' ) === 'yes' ? true : false;
		$show_numbers = get_option( 'sportspress_event_show_player_numbers', 'yes' ) === 'yes' ? true : false;
		$mode = get_option( 'sportspress_event_performance_mode', 'values' );

		$row = sp_array_value( $data, -1, array() );
		$row = array_filter( $row );
		$row = array_intersect_key( $row, $labels );
		if ( ! empty( $row ) ) {
			?>
			<tr class="sp-par-row <?php echo ( $i % 2 == 0 ? 'odd' : 'even' ); ?>">
				<?php
				if ( $show_players ):
					if ( $show_numbers ) {
						echo '<td class="data-number">&nbsp;</td>';
					}
					echo '<td class="data-name">' . __( 'Par', 'sportspress' ) . '</td>';
				endif;

				$row = sp_array_value( $data, -1, array() );

				if ( $mode == 'icons' ) echo '<td class="sp-performance-icons">';

				foreach ( $labels as $key => $label ):
					if ( 'name' == $key )
						continue;
					if ( isset( $position ) && 'position' == $key )
						continue;
					if ( $key == 'position' ):
						$value = '&nbsp;';
					elseif ( array_key_exists( $key, $row ) && $row[ $key ] != '' ):
						$value = $row[ $key ];
					else:
						$value = '-';
					endif;

					if ( $mode == 'values' ):
						echo '<td class="data-' . $key . '">' . $value . '</td>';
					elseif ( intval( $value ) && $mode == 'icons' ):
						$performance_id = sp_array_value( $performance_ids, $key, null );
						if ( $performance_id && has_post_thumbnail( $performance_id ) ):
							echo str_repeat( get_the_post_thumbnail( $performance_id, 'sportspress-fit-mini' ) . ' ', $value );
						endif;
					endif;
				endforeach;

				if ( $mode == 'icons' ) echo '</td>';
				?>
			</tr>
			<?php
		}
	}

	/**
	 * Add par to result equation builder.
	*/
	public function equation_options( $options = array() ) {
		$options[ __( 'Performance', 'sportspress' ) ][ '$strokes' ] = __( 'Strokes', 'sportspress' );
		$options[ __( 'Performance', 'sportspress' ) ][ '$par' ] = __( 'Par', 'sportspress' );
		return $options;
	}

	/**
	 * Add par to result equation vars.
	*/
	public function equation_vars( $vars = array(), $performance = array(), $id = 0 ) {
		$pars = sp_array_value( sp_array_value( $performance, -1, array() ), -1, array() );
		$vars['par'] = array_sum( $pars );
		
		if ( ! $id ) return $vars;

		$vars['strokes'] = 0;
		$team = sp_array_value( $performance, $id, array() );
		foreach ( $team as $pid => $pp ) {
			$vars['strokes'] += array_sum( $pp );
		}

		return $vars;
	}

	/**
	 * Format results to reflect if over or under par.
	*/
	public function results( $results = array(), $id = 0 ) {
		foreach ( $results as $team_id => $team_results ) {
			if ( ! is_array( $team_results ) ) continue;
			if ( ! $team_id ) continue;
			foreach ( $team_results as $key => $value ) {
				if ( 'par' !== $key ) continue;
				$results[ $team_id ][ $key ] = sprintf( "%+d", $value );
			}
		}
		return $results;
	}

	/** 
	 * Text filter.
	 */
	public function gettext( $translated_text, $untranslated_text, $domain ) {
		if ( $domain == 'sportspress' ) {
			switch ( $untranslated_text ) {
				case 'Events':
					$translated_text = __( 'Rounds', 'sportspress' );
					break;
				case 'Event':
					$translated_text = __( 'Round', 'sportspress' );
					break;
				case 'Add New Event':
					$translated_text = __( 'Add New Round', 'sportspress' );
					break;
				case 'Edit Event':
					$translated_text = __( 'Edit Round', 'sportspress' );
					break;
				case 'View Event':
					$translated_text = __( 'View Round', 'sportspress' );
					break;
				case 'View all events':
					$translated_text = __( 'View all rounds', 'sportspress' );
					break;
				case 'Venues':
					$translated_text = __( 'Courses', 'sportspress' );
					break;
				case 'Venue':
					$translated_text = __( 'Course', 'sportspress' );
					break;
				case 'Edit Venue':
					$translated_text = __( 'Edit Course', 'sportspress' );
					break;
				case 'Box Score':
					$translated_text = __( 'Scorecard', 'sportspress' );
					break;
				case 'League Tables':
					$translated_text = __( 'Leaderboards', 'sportspress' );
					break;
				case 'League Table':
					$translated_text = __( 'Leaderboard', 'sportspress' );
					break;
				case 'Add New League Table':
					$translated_text = __( 'Add New Leaderboard', 'sportspress' );
					break;
				case 'Edit League Table':
					$translated_text = __( 'Edit Leaderboard', 'sportspress' );
					break;
				case 'View League Table':
					$translated_text = __( 'View Leaderboard', 'sportspress' );
					break;
				case 'Adjustments':
					$translated_text = __( 'Handicaps', 'sportspress' );
					break;
			}
		}
		
		return $translated_text;
	}

	/**
	 * Add venue fields.
	 *
	 * @access public
	 * @return void
	 */
	public function add_venue_fields() {
		?>
		<div class="form-field">
			<label for="term_meta[sp_rating]"><?php _e( 'Rating', 'sportspress' ); ?></label>
			<input type="text" class="sp-venue-rating" name="term_meta[sp_rating]" id="term_meta[sp_rating]" value="72">
		</div>
		<div class="form-field">
			<label for="term_meta[sp_slope]"><?php _e( 'Slope', 'sportspress' ); ?></label>
			<input type="text" class="sp-venue-slope" name="term_meta[sp_slope]" id="term_meta[sp_slope]" value="113">
		</div>
	<?php
	}

	/**
	 * Edit venue fields.
	 *
	 * @access public
	 * @param mixed $term Term (category) being edited
	 */
	public function edit_venue_fields( $term ) {
	 	$t_id = $term->term_id;
		$term_meta = get_option( "taxonomy_$t_id" ); ?>
		<tr class="form-field">
			<th scope="row" valign="top"><label for="term_meta[sp_rating]"><?php _e( 'Rating', 'sportspress' ); ?></label></th>
			<td>
				<input type="text" class="sp-venue-rating" name="term_meta[sp_rating]" id="term_meta[sp_rating]" value="<?php echo esc_attr( isset( $term_meta['sp_rating'] ) ) ? esc_attr( $term_meta['sp_rating'] ) : ''; ?>">
			</td>
		</tr>
		<tr class="form-field">
			<th scope="row" valign="top"><label for="term_meta[sp_slope]"><?php _e( 'Slope', 'sportspress' ); ?></label></th>
			<td>
				<input type="text" class="sp-venue-slope" name="term_meta[sp_slope]" id="term_meta[sp_slope]" value="<?php echo esc_attr( isset( $term_meta['sp_slope'] ) ) ? esc_attr( $term_meta['sp_slope'] ) : ''; ?>">
			</td>
		</tr>
	<?php
	}

	/**
	 * Add venue columns in admin.
	 *
	 * @access public
	 * @param mixed $columns
	 * @return array
	 */
	public function venue_columns( $columns ) {
		$columns['sp_rating'] = __( 'Rating', 'sportspress' );
		$columns['sp_slope'] = __( 'Slope', 'sportspress' );
		return $columns;
	}

	/**
	 * Column value added to category admin.
	 *
	 * @access public
	 * @param mixed $columns
	 * @param mixed $column
	 * @param mixed $id
	 * @return array
	 */
	public function column_value( $columns, $column, $id ) {
		if ( $column == 'sp_rating' ) {
			$term_meta = get_option( "taxonomy_$id" );
			return ( isset( $term_meta['sp_rating'] ) ? $term_meta['sp_rating'] : '' );
		} elseif ( $column == 'sp_slope' ) {
			$term_meta = get_option( "taxonomy_$id" );
			return ( isset( $term_meta['sp_slope'] ) ? $term_meta['sp_slope'] : '' );
		}

		return $columns;
	}

	/**
	 * Remove position column from player admin.
	*/
	public function remove_player_admin_position_column( $columns = array() ) {
		unset( $columns['sp_position'] );
		return $columns;
	}

	/**
	 * Remove position taxonomy.
	*/
	public function taxonomies( $taxonomies = array() ) {
		if ( ( $key = array_search( 'sp_position', $taxonomies ) ) !== false ) {
			unset( $taxonomies[ $key ] );
		}
		return $taxonomies;
	}

	/**
	 * Define default sport.
	*/
	public function default_sport() {
		return 'golf';
	}
}

endif;

new SportsPress_Golf();
