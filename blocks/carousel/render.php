<?php
/**
 * Render the carousel block: query posts by category, output title + image per item.
 * Used when block.json has "render": "file:./render.php". Variables: $block, $content, $attributes.
 */
$category_id = isset( $attributes['categoryId'] ) ? (int) $attributes['categoryId'] : 0;
if ( ! $category_id ) {
  return;
}

$query = new WP_Query(
  array(
    'cat'              => $category_id,
    'post_type'        => 'post',
    'post_status'      => 'publish',
    'posts_per_page'   => 20,
    'orderby'          => 'date',
    'order'            => 'DESC',
    'no_found_rows'    => true,
  )
);

if ( ! $query->have_posts() ) {
  echo '<div ' . get_block_wrapper_attributes() . '>';
  echo '<div class="stemlp-carousel stemlp-carousel--empty"><p class="stemlp-carousel__empty">' . esc_html__( 'No posts in this category.', 'stemlp' ) . '</p></div>';
  echo '</div>';
  return;
}

echo '<div ' . get_block_wrapper_attributes() . '>';
echo '<div class="stemlp-carousel" role="region" aria-label="' . esc_attr__( 'Posts carousel', 'stemlp' ) . '">';
echo '<div class="stemlp-carousel__track">';

while ( $query->have_posts() ) {
  $query->the_post();
  $permalink = get_the_permalink();
  $title    = get_the_title();
  $thumb_id = get_post_thumbnail_id();
  echo '<article class="stemlp-carousel__item">';
  echo '<a href="' . esc_url( $permalink ) . '" class="stemlp-carousel__link">';
  if ( $thumb_id ) {
    $img_attrs = array( 'class' => 'stemlp-carousel__image' );
    $focal_style = stemlp_featured_image_focal_point_style( get_the_ID() );
    if ( $focal_style ) {
      $img_attrs['style'] = $focal_style;
    }
    echo wp_get_attachment_image( $thumb_id, 'medium_large', false, $img_attrs );
  } else {
    echo '<span class="stemlp-carousel__image stemlp-carousel__image--placeholder"></span>';
  }
  echo '<h3 class="stemlp-carousel__title">' . esc_html( $title ) . '</h3>';
  echo '</a></article>';
}

wp_reset_postdata();
echo '</div></div></div>';
