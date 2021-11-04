<?php

class ReservationListTable extends WP_List_Table {
  function get_data() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'reservations';
    return $wpdb->get_results("SELECT * FROM $table_name", ARRAY_A);
  }

  function column_event_id($item) {
    global $wpdb;
    $event_id = $item['event_id'];
    $table_name = $wpdb->prefix . 'posts';
    $event = $wpdb->get_row("SELECT * FROM $table_name WHERE ID=$event_id");
    return '<a href="'. $event->post_title .'">'. $event->post_title . '</a>';
  }

  function get_columns() {
    return array(
      'id' => 'ID',
      'first_name' => 'Prénom',
      'last_name' => 'Nom de famille',
      'phone' => 'Numéro de téléphone',
      'event_id' => 'Évènement',
      'event_date' => 'Date de l\'évènement',
      'event_tickets_reserved' => 'Places réservées',
    );
  }

  function get_sortable_columns() {
    return array(
      'id' => array('id', false),
      'event_id' => array('event_id', false),
      'event_date' => array('event_date', false),
    );
  }

  function column_default($item, $column_name) {
    return $item[$column_name];
  }

  function usort_reorder($a, $b) {
    $orderby = (!empty($_GET['orderby'])) ? $_GET['orderby'] : 'id';
    $order = (!empty($_GET['order'])) ? $_GET['order'] : 'asc';
    $result = strcmp($a[$orderby], $b[$orderby]);
    return ($order === 'asc') ? $result : -$result;
  }

  function prepare_items() {
    $data = $this->get_data();
    $columns = $this->get_columns();
    $sortable = $this->get_sortable_columns();
    $this->_column_headers = array($columns, array(), $sortable);
    usort($data, array($this,'usort_reorder'));
    $this->items = $data;
  }
}