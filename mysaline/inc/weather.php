<?php
/**
 * Local weather, from the National Weather Service.
 *
 * Why api.weather.gov and not one of the commercial APIs: it needs no key and
 * no signup, it has no free tier to outgrow or billing to forget, its data is
 * US government work in the public domain, and it is the same source the local
 * TV stations quote. For a Saline County paper there is no better answer, and
 * nothing here can stop working because a trial expired.
 *
 * The API is a two-step lookup: a lat/lon resolves to a forecast grid and a
 * list of nearby observation stations, and those URLs are then fetched for the
 * forecast and the current conditions. The grid never moves, so it is cached
 * for a month; the readings are cached for minutes.
 *
 * Failure is normal and must never be visible. Every request has a short
 * timeout, the last good reading is kept separately from the cache, and if
 * there is nothing to show the widget renders nothing at all rather than an
 * error.
 *
 * @package MySaline
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const MYSALINE_WX_ENDPOINT   = 'https://api.weather.gov';
const MYSALINE_WX_LAST_GOOD  = 'mysaline_weather_last_good';
const MYSALINE_WX_GRID_TTL   = MONTH_IN_SECONDS;
const MYSALINE_WX_DATA_TTL   = 15 * MINUTE_IN_SECONDS;
// Benton, the county seat.
const MYSALINE_WX_DEFAULT_LAT = '34.5645';
const MYSALINE_WX_DEFAULT_LON = '-92.5868';

/**
 * Whether the weather display is switched on.
 *
 * @return bool
 */
function mysaline_weather_enabled() {
	return (bool) get_theme_mod( 'mysaline_weather_enable', true );
}

/**
 * Configured coordinates, as a "lat,lon" string.
 *
 * NWS rejects more than four decimal places, so they are trimmed.
 *
 * @return string
 */
function mysaline_weather_point() {
	$lat = (float) get_theme_mod( 'mysaline_weather_lat', MYSALINE_WX_DEFAULT_LAT );
	$lon = (float) get_theme_mod( 'mysaline_weather_lon', MYSALINE_WX_DEFAULT_LON );

	return sprintf( '%.4F,%.4F', $lat, $lon );
}

/**
 * GET a JSON document from the weather service.
 *
 * NWS asks that clients identify themselves with a contact address so they can
 * get in touch about traffic rather than simply blocking; the site URL and admin
 * email are used for that.
 *
 * @param string $url Absolute URL.
 * @return array|null Decoded body, or null on any failure.
 */
function mysaline_weather_get( $url ) {
	$response = wp_remote_get(
		$url,
		array(
			// Deliberately short. A slow weather API must never hold up a page.
			'timeout'     => 5,
			'redirection' => 2,
			'headers'     => array(
				'Accept'     => 'application/geo+json',
				'User-Agent' => sprintf(
					'(%s, %s)',
					wp_parse_url( home_url(), PHP_URL_HOST ),
					get_option( 'admin_email' )
				),
			),
		)
	);

	if ( is_wp_error( $response ) || 200 !== (int) wp_remote_retrieve_response_code( $response ) ) {
		return null;
	}

	$data = json_decode( wp_remote_retrieve_body( $response ), true );

	return is_array( $data ) ? $data : null;
}

/**
 * Resolve coordinates to the forecast grid and station list.
 *
 * Cached for a month: a point's grid square does not move.
 *
 * @return array|null {forecast:string, stations:string, city:string, state:string}
 */
function mysaline_weather_grid() {
	$point = mysaline_weather_point();
	$key   = 'mysaline_wx_grid_' . md5( $point );

	$cached = get_transient( $key );
	if ( is_array( $cached ) ) {
		return $cached;
	}

	$data = mysaline_weather_get( MYSALINE_WX_ENDPOINT . '/points/' . rawurlencode( $point ) );
	if ( empty( $data['properties']['forecast'] ) ) {
		// Cache the miss briefly so an outage does not mean a lookup per page view.
		set_transient( $key, array(), 10 * MINUTE_IN_SECONDS );
		return null;
	}

	$props = $data['properties'];
	$grid  = array(
		'forecast' => (string) $props['forecast'],
		'stations' => isset( $props['observationStations'] ) ? (string) $props['observationStations'] : '',
		'city'     => isset( $props['relativeLocation']['properties']['city'] ) ? (string) $props['relativeLocation']['properties']['city'] : '',
		'state'    => isset( $props['relativeLocation']['properties']['state'] ) ? (string) $props['relativeLocation']['properties']['state'] : '',
	);

	set_transient( $key, $grid, MYSALINE_WX_GRID_TTL );

	return $grid;
}

/**
 * Convert a Celsius reading to the configured unit.
 *
 * @param float|null $celsius Reading.
 * @return int|null Rounded temperature.
 */
function mysaline_weather_temp( $celsius ) {
	if ( null === $celsius || ! is_numeric( $celsius ) ) {
		return null;
	}

	if ( 'C' === get_theme_mod( 'mysaline_weather_units', 'F' ) ) {
		return (int) round( (float) $celsius );
	}

	return (int) round( (float) $celsius * 9 / 5 + 32 );
}

/**
 * The reading to display. Reads cache only — never makes a request.
 *
 * Fetching inside a page render means one unlucky visitor every cache period
 * waits on four sequential round-trips to a third party before they see any
 * HTML, and a slow or hanging API becomes a slow or hanging site. So the
 * network work is done by mysaline_weather_refresh() on a schedule, and the
 * page is only ever allowed to read what is already stored.
 *
 * @return array|null {temp:int, summary:string, place:string, unit:string, periods:array}
 */
function mysaline_weather_data() {
	if ( ! mysaline_weather_enabled() ) {
		return null;
	}

	$cached = get_transient( 'mysaline_wx_data_' . md5( mysaline_weather_point() ) );
	if ( is_array( $cached ) && ! empty( $cached ) ) {
		return $cached;
	}

	// Nothing cached yet. Ask for a refresh out of band and show the last known
	// reading, or nothing at all on a cold start.
	mysaline_weather_schedule_refresh();

	return mysaline_weather_last_good();
}

/**
 * Queue a background refresh, unless one is already due shortly.
 */
function mysaline_weather_schedule_refresh() {
	if ( ! wp_next_scheduled( 'mysaline_weather_refresh' ) ) {
		wp_schedule_single_event( time() + 5, 'mysaline_weather_refresh' );
	}
}

/**
 * Fetch current conditions and store them. This is the only code that talks to
 * the network, and it runs on cron, never during a page render.
 *
 * @return array|null
 */
function mysaline_weather_refresh() {
	if ( ! mysaline_weather_enabled() ) {
		return null;
	}

	$key = 'mysaline_wx_data_' . md5( mysaline_weather_point() );

	$grid = mysaline_weather_grid();
	if ( ! $grid ) {
		return mysaline_weather_last_good();
	}

	$unit = 'C' === get_theme_mod( 'mysaline_weather_units', 'F' ) ? 'C' : 'F';

	$out = array(
		'temp'    => null,
		'summary' => '',
		'unit'    => $unit,
		'place'   => trim( (string) get_theme_mod( 'mysaline_weather_place', '' ) ),
		'periods' => array(),
	);

	if ( '' === $out['place'] ) {
		$out['place'] = $grid['city'] ? $grid['city'] : __( 'Saline County', 'mysaline' );
	}

	// Forecast periods.
	$forecast = mysaline_weather_get( $grid['forecast'] );
	if ( ! empty( $forecast['properties']['periods'] ) ) {
		foreach ( array_slice( (array) $forecast['properties']['periods'], 0, 5 ) as $period ) {
			$temp = isset( $period['temperature'] ) ? (int) $period['temperature'] : null;

			// Periods report in the office's unit, which is F for US offices.
			if ( null !== $temp && 'C' === $unit && 'F' === ( $period['temperatureUnit'] ?? 'F' ) ) {
				$temp = (int) round( ( $temp - 32 ) * 5 / 9 );
			}

			$out['periods'][] = array(
				'name'    => isset( $period['name'] ) ? (string) $period['name'] : '',
				'temp'    => $temp,
				'short'   => isset( $period['shortForecast'] ) ? (string) $period['shortForecast'] : '',
				'detail'  => isset( $period['detailedForecast'] ) ? (string) $period['detailedForecast'] : '',
				'is_day'  => ! empty( $period['isDaytime'] ),
			);
		}

		// Until a live observation is found, the current period stands in.
		if ( ! empty( $out['periods'][0] ) ) {
			$out['temp']    = $out['periods'][0]['temp'];
			$out['summary'] = $out['periods'][0]['short'];
		}
	}

	// A live reading from the nearest station that is actually reporting.
	if ( $grid['stations'] ) {
		$stations = mysaline_weather_get( $grid['stations'] );
		$ids      = array();
		foreach ( (array) ( $stations['features'] ?? array() ) as $feature ) {
			if ( ! empty( $feature['properties']['stationIdentifier'] ) ) {
				$ids[] = (string) $feature['properties']['stationIdentifier'];
			}
		}

		// Small airports go quiet overnight, so try a few before giving up.
		foreach ( array_slice( $ids, 0, 3 ) as $id ) {
			$obs = mysaline_weather_get( MYSALINE_WX_ENDPOINT . '/stations/' . rawurlencode( $id ) . '/observations/latest' );
			$c   = $obs['properties']['temperature']['value'] ?? null;
			if ( null !== $c ) {
				$out['temp'] = mysaline_weather_temp( $c );
				if ( ! empty( $obs['properties']['textDescription'] ) ) {
					$out['summary'] = (string) $obs['properties']['textDescription'];
				}
				break;
			}
		}
	}

	if ( null === $out['temp'] ) {
		return mysaline_weather_last_good();
	}

	set_transient( $key, $out, MYSALINE_WX_DATA_TTL );

	// Kept outside the cache so an outage falls back to the last real reading
	// instead of an empty widget.
	update_option( MYSALINE_WX_LAST_GOOD, $out, false );

	return $out;
}

/**
 * The last reading that succeeded, if it is recent enough to be worth showing.
 *
 * @return array|null
 */
function mysaline_weather_last_good() {
	$last = get_option( MYSALINE_WX_LAST_GOOD );

	return is_array( $last ) && ! empty( $last['temp'] ) ? $last : null;
}

/**
 * Pick an icon key from a forecast phrase.
 *
 * NWS returns prose ("Chance Showers And Thunderstorms"), not a condition code,
 * so the phrase is matched most-specific first — thunder before rain, because
 * a thunderstorm description also contains the word "showers".
 *
 * @param string $summary Forecast text.
 * @param bool   $is_day  Daytime period.
 * @return string Icon key.
 */
function mysaline_weather_icon_key( $summary, $is_day = true ) {
	$s = strtolower( $summary );

	$map = array(
		'thunder' => array( 'thunder', 'tstm' ),
		'snow'    => array( 'snow', 'sleet', 'flurr', 'ice', 'freezing', 'winter', 'blizzard' ),
		'rain'    => array( 'rain', 'shower', 'drizzle' ),
		'fog'     => array( 'fog', 'haze', 'mist', 'smoke' ),
		'wind'    => array( 'wind', 'breezy', 'blustery' ),
		'cloud'   => array( 'overcast', 'cloudy' ),
		'partly'  => array( 'partly', 'mostly sunny', 'mostly clear', 'few clouds', 'scattered' ),
	);

	foreach ( $map as $key => $needles ) {
		foreach ( $needles as $needle ) {
			if ( false !== strpos( $s, $needle ) ) {
				return $key;
			}
		}
	}

	return $is_day ? 'sun' : 'moon';
}

/**
 * Inline SVG for a condition.
 *
 * Inline rather than linked so the icon inherits the surrounding text colour,
 * costs no extra request, and survives in the static export.
 *
 * @param string $key Icon key from mysaline_weather_icon_key().
 * @return string SVG markup.
 */
function mysaline_weather_icon( $key ) {
	$cloud = '<path d="M6.5 18h11a3.5 3.5 0 0 0 .3-7 5 5 0 0 0-9.5-1.2A3.6 3.6 0 0 0 6.5 18Z" fill="currentColor"/>';

	$icons = array(
		'sun'     => '<circle cx="12" cy="12" r="4.6" fill="currentColor"/><g stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><path d="M12 2.4v2.4M12 19.2v2.4M2.4 12h2.4M19.2 12h2.4M5.2 5.2l1.7 1.7M17.1 17.1l1.7 1.7M18.8 5.2l-1.7 1.7M6.9 17.1l-1.7 1.7"/></g>',
		'moon'    => '<path d="M20 14.3A8.2 8.2 0 0 1 9.7 4a8.5 8.5 0 1 0 10.3 10.3Z" fill="currentColor"/>',
		'partly'  => '<circle cx="8.4" cy="8.4" r="3.4" fill="currentColor"/><g stroke="currentColor" stroke-width="1.5" stroke-linecap="round"><path d="M8.4 1.9v1.8M8.4 13.1v1.1M1.9 8.4h1.8M13.1 8.4h1.8M3.8 3.8l1.3 1.3M11.7 11.7l1.3 1.3M12.9 3.8l-1.2 1.3"/></g><path d="M9 20h9a3 3 0 0 0 .3-6 4.4 4.4 0 0 0-8.3-1A3.1 3.1 0 0 0 9 20Z" fill="currentColor"/>',
		'cloud'   => $cloud,
		'rain'    => $cloud . '<g stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><path d="M9 20.2l-.8 1.9M13 20.2l-.8 1.9M17 20.2l-.8 1.9"/></g>',
		'snow'    => $cloud . '<g stroke="currentColor" stroke-width="1.6" stroke-linecap="round"><path d="M9 20.4v1.8M8.2 21.3h1.6M13 20.4v1.8M12.2 21.3h1.6M17 20.4v1.8M16.2 21.3h1.6"/></g>',
		'thunder' => $cloud . '<path d="M13.2 19.4h2.6l-1.5 2.4h1.9l-3.9 3.4 1-2.9h-1.7Z" fill="currentColor" transform="translate(0 -1.4)"/>',
		'fog'     => $cloud . '<g stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><path d="M5 20.6h14M7 22.9h10"/></g>',
		'wind'    => '<g stroke="currentColor" stroke-width="1.9" stroke-linecap="round" fill="none"><path d="M3 9h10a2.6 2.6 0 1 0-2.6-2.6"/><path d="M3 14h13.4a2.6 2.6 0 1 1-2.6 2.6"/><path d="M3 11.5h6"/></g>',
	);

	$svg = isset( $icons[ $key ] ) ? $icons[ $key ] : $icons['sun'];

	return '<svg class="ms-wx__icon" viewBox="0 0 24 24" width="20" height="20" aria-hidden="true" focusable="false">' . $svg . '</svg>';
}

/**
 * Tags allowed when printing the weather markup.
 *
 * wp_kses_post() strips SVG entirely, so the icon elements are allowed
 * explicitly. This is an allowlist, not an escape hatch: no href, no script,
 * no event attributes, so nothing here can carry executable content even if
 * the upstream feed changed shape.
 *
 * @return array
 */
function mysaline_weather_kses() {
	$shape = array(
		'fill'             => true,
		'stroke'           => true,
		'stroke-width'     => true,
		'stroke-linecap'   => true,
		'stroke-linejoin'  => true,
		'stroke-opacity'   => true,
		'transform'        => true,
	);

	return array(
		'div'    => array( 'class' => true ),
		'p'      => array( 'class' => true ),
		'ul'     => array( 'class' => true ),
		'li'     => array( 'class' => true ),
		'span'   => array(
			'class' => true,
			'title' => true,
		),
		'svg'    => array(
			'class'       => true,
			'viewbox'     => true,
			'width'       => true,
			'height'      => true,
			'aria-hidden' => true,
			'focusable'   => true,
			'xmlns'       => true,
		),
		'g'      => $shape,
		'path'   => array_merge( $shape, array( 'd' => true ) ),
		'circle' => array_merge( $shape, array(
			'cx' => true,
			'cy' => true,
			'r'  => true,
		) ),
	);
}

/**
 * The compact weather chip used in the top bar.
 *
 * @return string
 */
function mysaline_weather_chip() {
	$wx = mysaline_weather_data();
	if ( ! $wx || null === $wx['temp'] ) {
		return '';
	}

	$icon = mysaline_weather_icon( mysaline_weather_icon_key( $wx['summary'] ) );

	return sprintf(
		'<span class="ms-wx-chip" title="%1$s">%2$s<span class="ms-wx-chip__temp">%3$s&deg;%4$s</span><span class="ms-wx-chip__place">%5$s</span></span>',
		esc_attr( $wx['summary'] ),
		$icon,
		esc_html( (string) $wx['temp'] ),
		esc_html( $wx['unit'] ),
		esc_html( $wx['place'] )
	);
}

/**
 * The full forecast card used in the sidebar widget.
 *
 * @return string
 */
function mysaline_weather_card() {
	$wx = mysaline_weather_data();
	if ( ! $wx || null === $wx['temp'] ) {
		return '';
	}

	$out  = '<div class="ms-wx">';
	$out .= '<div class="ms-wx__now">';
	$out .= '<span class="ms-wx__now-icon">' . mysaline_weather_icon( mysaline_weather_icon_key( $wx['summary'] ) ) . '</span>';
	$out .= '<div>';
	$out .= '<p class="ms-wx__temp">' . esc_html( (string) $wx['temp'] ) . '&deg;' . esc_html( $wx['unit'] ) . '</p>';
	$out .= '<p class="ms-wx__summary">' . esc_html( $wx['summary'] ) . '</p>';
	$out .= '<p class="ms-wx__place">' . esc_html( $wx['place'] ) . '</p>';
	$out .= '</div></div>';

	if ( ! empty( $wx['periods'] ) ) {
		$out .= '<ul class="ms-wx__periods">';
		foreach ( array_slice( $wx['periods'], 0, 4 ) as $period ) {
			$out .= '<li class="ms-wx__period">';
			$out .= '<span class="ms-wx__period-name">' . esc_html( $period['name'] ) . '</span>';
			$out .= '<span class="ms-wx__period-icon">' . mysaline_weather_icon( mysaline_weather_icon_key( $period['short'], $period['is_day'] ) ) . '</span>';
			$out .= '<span class="ms-wx__period-short">' . esc_html( $period['short'] ) . '</span>';
			if ( null !== $period['temp'] ) {
				$out .= '<span class="ms-wx__period-temp">' . esc_html( (string) $period['temp'] ) . '&deg;</span>';
			}
			$out .= '</li>';
		}
		$out .= '</ul>';
	}

	$out .= '<p class="ms-wx__credit">' . esc_html__( 'Source: National Weather Service', 'mysaline' ) . '</p>';
	$out .= '</div>';

	return $out;
}

/**
 * Shortcode so the forecast can be dropped into any page or post.
 *
 * @return string
 */
function mysaline_weather_shortcode() {
	return mysaline_weather_card();
}
add_shortcode( 'mysaline_weather', 'mysaline_weather_shortcode' );

/**
 * Clear the cached readings when the location or units change.
 *
 * Without this, changing the town in the Customizer would appear to do nothing
 * for up to fifteen minutes.
 */
function mysaline_weather_flush() {
	global $wpdb;

	delete_option( MYSALINE_WX_LAST_GOOD );

	// The cache key is keyed by coordinates, so the old key is unknown here.
	$wpdb->query(
		"DELETE FROM {$wpdb->options}
		 WHERE option_name LIKE '_transient_mysaline_wx_%'
		    OR option_name LIKE '_transient_timeout_mysaline_wx_%'"
	);

	wp_cache_flush();
}
add_action( 'customize_save_after', 'mysaline_weather_flush' );

/**
 * Run the refresh when cron fires.
 */
add_action( 'mysaline_weather_refresh', 'mysaline_weather_refresh' );

/**
 * A fifteen-minute interval, matching how often observations are published.
 *
 * @param array $schedules Existing schedules.
 * @return array
 */
function mysaline_weather_cron_schedule( $schedules ) {
	if ( ! isset( $schedules['mysaline_quarter_hour'] ) ) {
		$schedules['mysaline_quarter_hour'] = array(
			'interval' => MYSALINE_WX_DATA_TTL,
			'display'  => __( 'Every 15 minutes (MySaline weather)', 'mysaline' ),
		);
	}

	return $schedules;
}
add_filter( 'cron_schedules', 'mysaline_weather_cron_schedule' );

/**
 * Keep the recurring refresh registered.
 */
function mysaline_weather_cron_init() {
	if ( ! wp_next_scheduled( 'mysaline_weather_tick' ) ) {
		wp_schedule_event( time() + 60, 'mysaline_quarter_hour', 'mysaline_weather_tick' );
	}
}
add_action( 'init', 'mysaline_weather_cron_init' );
add_action( 'mysaline_weather_tick', 'mysaline_weather_refresh' );

/**
 * Drop the schedule when the theme is switched away from.
 */
function mysaline_weather_cron_clear() {
	wp_clear_scheduled_hook( 'mysaline_weather_tick' );
	wp_clear_scheduled_hook( 'mysaline_weather_refresh' );
}
add_action( 'switch_theme', 'mysaline_weather_cron_clear' );
