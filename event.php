<?php
/*
  Plugin Name: Events
  Description: Plugin de gestion d'évènements
  Author: Jean Masso
  Version: 1.0.0
*/

function events_init() {
  // CPT Event
  $labels = array(
    'name' => 'Events',
    'all_items' => 'Tous les évènements',
    'singular_name' => 'Event',
    'add_new_item' => 'Ajouter un évènement',
    'menu_name' => 'Events'
  );

  $args = array(
    'labels' => $labels,
    'public' => true,
    'show_in_rest' => true,
    'has_archive' => true,
    'rewrite' => array("slug" => "events"),
    'supports' => array('title', 'editor','thumbnail'),
    'menu_position' => 5,
    'menu_icon' => 'dashicons-calendar',
  );

  register_post_type( 'events', $args );
}

add_action('init', 'events_init');

// Ajout d'une meta box de date de l'évènement
function add_event_date_meta_box() {
  function event_date($post) {
    $date = get_post_meta($post->ID, 'event_date', true);

    if (empty($date))
      $date = the_date();

    echo '<input type="date" name="event_date" value="' . $date  . '" />';
  }

  add_meta_box('event_date_meta_boxes', 'Date', 'event_date', 'events', 'side', 'default');
}

add_action('add_meta_boxes', 'add_event_date_meta_box');

// Short code to display event date meta data
function show_event_date() {
  ob_start();
  $date = get_post_meta(get_the_ID(), 'event_date', true);
  echo "<date>$date</date>";
  return ob_get_clean();
}

// Add meta box ticket to event

function add_event_ticket_meta_box() {
  function event_ticket($post) {
    $ticket = get_post_meta($post->ID, 'event_ticket', true);

    if (empty($ticket)) $ticket = 0;

    echo '<input type="number" name="event_ticket" value="' . $ticket  . '" />';
  }

  add_meta_box('event_ticket_meta_boxes', 'Places', 'event_ticket', 'events', 'side', 'default');
}

add_action('add_meta_boxes', 'add_event_ticket_meta_box');


// Short code to display event ticket meta data

function show_event_ticket() {
  ob_start();
  $ticket = get_post_meta(get_the_ID(), 'event_ticket', true);
  echo "<span>$ticket</span>";
  return ob_get_clean();
}

add_shortcode('show_event_ticket', 'show_event_ticket');

// Update meta on event post save

##############################

// Add meta box ticket to event

/*function add_event_slots_meta_box() {
  function event_slots($post) {
    $slots = get_post_meta($post->ID, 'event_slot', true);

    if (empty($slots)) $slots = [];

    echo '<input type="date" name="slot" />
    <button type="button" id="slotAddButton" value="' . $slots  . '" />';
  }

  add_meta_box('event_ticket_meta_boxes', 'Places', 'event_ticket', 'events', 'side', 'default');
}

add_action('add_meta_boxes', 'add_event_ticket_meta_box');


// Short code to display event ticket meta data

function show_event_ticket() {
  ob_start();
  $ticket = get_post_meta(get_the_ID(), 'event_ticket', true);
  echo "<span>$ticket</span>";
  return ob_get_clean();
}*/

##############################

function events_post_save_meta($post_id) {
  if(isset($_POST['event_date']) && $_POST['event_date'] !== "") {
    update_post_meta($post_id, 'event_date', $_POST['event_date']);
  }

  if(isset($_POST['event_ticket']) && $_POST['event_ticket'] !== "") {
    update_post_meta($post_id, 'event_ticket', $_POST['event_ticket']);
  }
}

add_action('save_post', 'events_post_save_meta');

// Add event post type to home and main query

function add_event_post_type($query) {
  if (is_home() && $query->is_main_query()) {
    $query->set('post_type', array('post', 'events'));
    return $query;
  }
}

add_action('pre_get_posts', 'add_event_post_type');

// Étape 1: Créer la base de données
function reservation_database() {
  global $wpdb;

  $table_name = $wpdb->prefix . 'reservations';
  $charset_collate = $wpdb->get_charset_collate();

  $sql = "CREATE TABLE IF NOT EXISTS $table_name (
    id mediumint(9) NOT NULL AUTO_INCREMENT,
    first_name VARCHAR(55) NOT NULL,
    last_name VARCHAR(55) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    event_id VARCHAR(55) NOT NULL,
    event_date DATE NOT NULL,
    event_tickets_reserved INTEGER (9) NOT NULL,
    PRIMARY KEY (id)
  ) $charset_collate;";

  require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
  dbDelta($sql);
  add_option('contact_db_version', '1.0');
}

register_activation_hook(__FILE__, 'reservation_database');

// Étape 2: Insertion dans la base de données
function insert_reservation() {
  global $wpdb;

  $table_name = $wpdb->prefix . 'reservations';

  $wpdb->insert(
    $table_name, array(
      'first_name' => 'John',
      'last_name' => 'Doe',
      'phone' => '43.05.50',
      'event_id' => '30',
      'event_date' => '2021-11-13',
      'event_tickets_reserved' => 2
    )
  );
}

register_activation_hook(__FILE__, 'insert_reservation');

// Étape 3: Ajouter le plugin  à l'admin
function add_plugin_reservation_to_admin() {
  function reservation_content() {
    echo '<h1>Réservation</h1>';
    echo '<div style="margin-left: 20px;">';

    if (class_exists('WP_List_Table')) {
      require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
      require_once(plugin_dir_path(__FILE__) . 'event-reservation-list-table.php');
      $contactListTable = new ReservationListTable();
      $contactListTable->prepare_items();
      $contactListTable->display();
    } else {
      echo "WP_List_Table n'est pas disponible.";
    }

    echo "</div>";
  }

  add_menu_page('Reservations', 'Reservations', 'manage_options', 'reservations-plugin', 'reservation_content');
}

add_action('admin_menu', 'add_plugin_reservation_to_admin');

// Étape 4:
function show_reservation_form() {
  ob_start();
  global $wpdb;
  $table_name = $wpdb->prefix . 'reservations';
  $event_id = get_the_ID();
  $all_event_tickets = implode(',', get_post_meta($event_id, 'event_ticket'));
  $event_sum_tickets_reserved = $wpdb->get_results("SELECT SUM(event_tickets_reserved) as tickets_reserved  FROM $table_name WHERE event_id = $event_id;", ARRAY_A);
  $all_tickets_reserved = $event_sum_tickets_reserved[0]["tickets_reserved"];

  if (is_null($all_tickets_reserved))
    $all_tickets_reserved = 0;

  if (isset($_POST['reservation'])) {
    $first_name = sanitize_text_field($_POST['first_name']);
    $last_name = sanitize_text_field($_POST['last_name']);
    $phone = sanitize_text_field($_POST['phone']);
    $event_tickets_reserved = sanitize_text_field($_POST['event_tickets_reserved']);

    if (!empty($first_name) && !empty($last_name) && !empty($phone) && !empty($event_tickets_reserved)) {
      $wpdb->insert(
        $table_name, array(
          'first_name' => $first_name,
          'last_name' => $last_name,
          'phone' => $phone,
          'event_id' => $event_id,
          'event_date' => implode(',', get_post_meta($event_id, 'event_date')),
          'event_tickets_reserved' => $event_tickets_reserved
        )
      );
      echo '<h3>Votre réservation a bien été enregistré.</h3>';
    }
  }

  echo '<fieldset class="border rounded" style="margin: 0 35%; padding: 15px;">
    <form method="POST">
      <h4 class="text-white mb-3">Réservez votre place pour l\'évènement</h4>
      <div class="row mx-0 g-3">
        <div class="col-12">Places réservées: ' . $all_tickets_reserved . ' / ' . $all_event_tickets . '</div>
        <input type="text" name="first_name" class="form-control col-12" placeholder="Prénom" required/>
        <input type="text" name="last_name" class="form-control col-12" placeholder="Nom de famille" required/>
        <input type="tel" name="phone" class="form-control col-12" placeholder="N° de téléphone" required/>
        <input type="number" name="event_tickets_reserved" class="form-control col-12" placeholder="N° de places" required/>
        <input type="submit" name="reservation" class="btn btn-primary  col-12" value="Réserver"/>
      </div>
    </form>
  </fieldset>';

  return ob_get_clean();
}

add_shortcode('show_reservation_form', 'show_reservation_form');