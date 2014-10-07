<?php

/*
Plugin Name: Event Auto Expire
Plugin URI:
Description: Based on the event date, this plugin automatically adds the category 'expired' to events after the event has passed.
Version: 1.0
Author: stephen
Author URI: https://www.schrauger.com
License: GPL2
*/

class event_auto_expire {

	//------------------------------------------------------------Expiring events
	public function __construct() {
		if ( ! wp_next_scheduled('auto_expire_event_hook')) {
			wp_schedule_event( time(), 'hourly', 'auto_expire_event_hook' );
			// run every hour (not really needed now, but later we may expire events with more granularity)
		}

		add_action( 'auto_expire_event_hook', 'auto_expire_event' );
	}

	public function auto_expire_event() {

		/**
		 * Get a list of all events that are not already marked expired.
		 */
		$args       = array(
			'post_type' => 'events',
			'tax_query' => array(
				array(
					'taxonomy' => 'events_category',
					'field'    => 'slug',
					'terms'    => 'expired',
					'operator' => 'NOT IN'
				)
			)
		);
		$event_list = new WP_Query( $args );

		$current_date = strtotime( 'today' ); // convert today (just date, not time) into unix timestamp

		// loop through each event and expire as needed
		while ( $event_list->have_posts() ) {
			the_post(); // this sets the global variable to the next post and increments the WordPress loop counter
			$id = get_the_ID();

			$event_date = strtotime( get_field( 'event_date' ) ); // convert date into unix timestamp
			if ( $current_date > $event_date ) {
				// date is in the past. expire the event.
				wp_set_object_terms( $id, 'expired', 'events_category', true );
			}
		}
	}
}
