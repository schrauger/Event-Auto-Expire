<?php

/*
Plugin Name: Event Auto Expire
Plugin URI: https://github.com/schrauger/Event-Auto-Expire
Description: Based on the event date, this plugin automatically adds the category 'expired' to events after the event has passed.
Version: 1.0
Author: Stephen Schrauger
Author URI: https://www.schrauger.com
License: GPL2
*/


class event_auto_expire {

	//------------------------------------------------------------Expiring events
	public function __construct() {
		register_activation_hook( __FILE__, array(
			$this,
			'on_activation'
		) ); //call the 'on_activation' function when plugin is first activated
		register_deactivation_hook( __FILE__, array(
			$this,
			'on_deactivation'
		) ); //call the 'on_deactivation' function when plugin is deactivated
		register_uninstall_hook( __FILE__, array(
				$this,
				'on_uninstall'
			) ); //call the 'uninstall' function when plugin is uninstalled completely

		add_action( 'auto_expire_event_hook', array( $this, 'auto_expire_event' ) );
		// must be added every time. it isn't stored in the database.
		// add_action events can't be placed in activation hooks.
	}

	/**
	 * Function that is run when the plugin is activated via the plugins page
	 */
	public function on_activation() {
		// even though this should only run when first installed, still check to see if there is a scheduled
		// cron job called 'auto_expire_event_hook'. if so, don't add.
		if ( ! wp_next_scheduled( 'auto_expire_event_hook' ) ) {
			wp_schedule_event( time(), 'hourly', 'auto_expire_event_hook' );
			// run every hour (not really needed now, but later we may expire events with more granularity)
		}
	}

	public function on_deactivation() {
		// stub
		// should probably just disable the cron job
		wp_clear_scheduled_hook( 'auto_expire_event_hook');
	}

	public function on_uninstall() {
		// stub
		// don't really have to do anything special. the deactivation function already removes the cron job.
	}


	public function auto_expire_event() {

		/**
		 * Get a list of all events that are not already marked expired.
		 */
		$args = array(
			'post_type' => 'events',
			'posts_per_page' => -1,
			'tax_query' => array(
				array(
					'taxonomy' => 'events_category',
					'field'    => 'slug',
					'terms'    => 'expired',
					'operator' => 'NOT IN',

				)
			)
		);
		$event_list = new WP_Query( $args );

		$current_date = strtotime( 'today' ); // convert today (just date, not time) into unix timestamp

		// loop through each event and expire as needed
		while ( $event_list->have_posts() ) {
			$event_list->the_post(); // this sets the global variable to the next post and increments the WordPress loop counter
			$id = get_the_ID();

			$event_date = strtotime( get_field( 'event_date' ) ); // convert date into unix timestamp

			if ( $current_date > $event_date ) {

				// date is in the past. expire the event.
				wp_set_object_terms( $id, 'expired', 'events_category', true );
			}
		}
	}
}

$event_auto_expire_object = new event_auto_expire();
