<?php
/**
 * Render the Events carousel block: query events from Events Manager, output title + date + image.
 * Used when block.json has "render": "file:./render.php". Variables: $block, $content, $attributes.
 * Requires Events Manager plugin. Uses post type "event".
 */
$limit = isset( $attributes['limit'] ) ? (int) $attributes['limit'] : 10;
$limit = max( 1, min( 30, $limit ) );
$order = isset( $attributes['order'] ) && $attributes['order'] === 'start_desc' ? 'DESC' : 'ASC';

$post_type = 'event';
if ( ! post_type_exists( $post_type ) ) {
  echo '<div ' . get_block_wrapper_attributes() . '>';
  echo '<div class="stemlp-carousel stemlp-carousel--empty stemlp-events-carousel"><p class="stemlp-carousel__empty">' . esc_html__( 'Events Manager plugin is required for the events carousel.', 'stemlp' ) . '</p></div>';
  echo '</div>';
  return;
}

$query_args = array(
  'post_type'      => $post_type,
  'post_status'    => 'publish',
  'posts_per_page' => $limit,
  'no_found_rows'  => true,
);

$start_meta = '_event_start_date';
$query_args['meta_key']   = $start_meta;
$query_args['orderby']    = 'meta_value';
$query_args['order']      = $order;
$query_args['meta_type']  = 'DATETIME';

if ( $order === 'ASC' ) {
  $query_args['meta_query'] = array(
    array(
      'key'     => $start_meta,
      'value'   => current_time( 'Y-m-d H:i:s' ),
      'compare' => '>=',
      'type'    => 'DATETIME',
    ),
  );
}

$query = new WP_Query( $query_args );

if ( ! $query->have_posts() ) {
  $query_args['meta_key']   = 'event_start_date';
  $query_args['meta_type']  = 'DATE';
  if ( $order === 'ASC' ) {
    $query_args['meta_query'] = array(
      array(
        'key'     => 'event_start_date',
        'value'   => current_time( 'Y-m-d' ),
        'compare' => '>=',
        'type'    => 'DATE',
      ),
    );
  } else {
    unset( $query_args['meta_query'] );
  }
  $query = new WP_Query( $query_args );
}

if ( ! $query->have_posts() && $order === 'ASC' ) {
  $query_args = array(
    'post_type'      => $post_type,
    'post_status'    => 'publish',
    'posts_per_page' => $limit,
    'no_found_rows'  => true,
    'orderby'        => 'date',
    'order'          => 'DESC',
  );
  $query = new WP_Query( $query_args );
}

if ( ! $query->have_posts() ) {
  echo '<div ' . get_block_wrapper_attributes() . '>';
  echo '<div class="stemlp-carousel stemlp-carousel--empty stemlp-events-carousel"><p class="stemlp-carousel__empty">' . esc_html__( 'No upcoming events.', 'stemlp' ) . '</p></div>';
  echo '</div>';
  return;
}

echo '<div ' . get_block_wrapper_attributes() . '>';
echo '<div class="stemlp-carousel stemlp-events-carousel" role="region" aria-label="' . esc_attr__( 'Events carousel', 'stemlp' ) . '">';
echo '<div class="stemlp-carousel__track">';

while ( $query->have_posts() ) {
  $query->the_post();
  $permalink  = get_the_permalink();
  $title      = get_the_title();
  $thumb_id   = get_post_thumbnail_id();
  $event_id   = get_the_ID();
  $event_info = stemlp_get_event_info( $event_id );
  $date_label   = $event_info['date_label'];
  $show_time    = $event_info['show_time'];
  $start_date   = $event_info['start_date'];
  $start_time   = $event_info['start_time'];
  $location_name = $event_info['location_name'];
  $location_town = $event_info['location_town'];

  $thumb_url = $thumb_id ? wp_get_attachment_image_url( $thumb_id, 'medium_large' ) : '';
  $article_style = $thumb_url ? ' style="background-image: url(' . esc_url( $thumb_url ) . ');"' : '';
  echo '<article class="stemlp-carousel__item stemlp-carousel__item--event"' . $article_style . '>';
  echo '<a href="' . esc_url( $permalink ) . '" class="stemlp-carousel__link">';
  echo '<h3 class="stemlp-carousel__title">' . esc_html( $title ) . '</h3>';
  if ( $date_label || $location_name || $location_town ) {
    echo '<div class="stemlp-carousel__date-block">';
    if ( $date_label ) {
      $datetime = $start_date . ( $show_time ? 'T' . $start_time : '' );
      echo '<time class="stemlp-carousel__date" datetime="' . esc_attr( $datetime ) . '">' . esc_html( $date_label ) . '</time>';
    }
    if ( $location_name || $location_town ) {
      $location_parts = array_filter( array( $location_name, $location_town ) );
      echo '<span class="stemlp-carousel__location">' . esc_html( implode( ', ', $location_parts ) ) . '</span>';
    }
    echo '</div>';
  }
  echo '</a></article>';
}

wp_reset_postdata();
echo '</div></div></div>';
