<?php

// Load text domain for localization - load as early as possible
function stemlp_load_textdomain() {
    $loaded = load_child_theme_textdomain( 'stemlp', get_stylesheet_directory() . '/languages' );
    // Debug: Check if text domain loaded successfully
    if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
        error_log( 'stemlp text domain loaded: ' . ( $loaded ? 'YES' : 'NO' ) );
        error_log( 'Current locale: ' . get_locale() );
    }
}
add_action( 'after_setup_theme', 'stemlp_load_textdomain' );

// Load text domain on plugins_loaded for admin interface
function stemlp_load_textdomain_admin() {
    if ( ! is_textdomain_loaded( 'stemlp' ) ) {
        load_child_theme_textdomain( 'stemlp', get_stylesheet_directory() . '/languages' );
    }
}
add_action( 'plugins_loaded', 'stemlp_load_textdomain_admin' );

// Also try loading on init as backup
function stemlp_load_textdomain_init() {
    if ( ! is_textdomain_loaded( 'stemlp' ) ) {
        load_child_theme_textdomain( 'stemlp', get_stylesheet_directory() . '/languages' );
    }
}
add_action( 'init', 'stemlp_load_textdomain_init' );

/**
 * Register block pattern "Featured section" so it can be inserted into page content
 * (Template Part blocks often don't work when pasted in post content; patterns do).
 * Content is loaded from patterns/featured-section.html.
 */
function stemlp_register_featured_section_pattern() {
  register_block_pattern_category(
    'stemlp',
    array(
      'label'       => __( 'Stemlp', 'stemlp' ),
      'description' => __( 'Patterns for the Stemlp theme.', 'stemlp' ),
    )
  );

  $pattern_file = get_stylesheet_directory() . '/patterns/featured-section.html';
  if ( ! is_readable( $pattern_file ) ) {
    return;
  }
  register_block_pattern(
    'stemlp/featured-section',
    array(
      'title'       => __( 'Featured section', 'stemlp' ),
      'description' => _x( 'Featured section with top slope and gradient (image, title, excerpt). Insert into page content.', 'Block pattern description', 'stemlp' ),
      'categories'  => array( 'stemlp', 'theme' ),
      'filePath'    => $pattern_file,
    )
  );
}
add_action( 'init', 'stemlp_register_featured_section_pattern' );

/**
 * Register selectable button block styles (Outline, Ghost, Brand).
 * Users choose them from the Styles dropdown in the Button block sidebar.
 */
function stemlp_register_button_styles() {
  register_block_style( 'core/button', array(
    'name'  => 'outline',
    'label' => __( 'Outline', 'stemlp' ),
  ) );
  register_block_style( 'core/button', array(
    'name'  => 'ghost',
    'label' => __( 'Ghost', 'stemlp' ),
  ) );
  register_block_style( 'core/button', array(
    'name'  => 'brand',
    'label' => __( 'Brand gradient', 'stemlp' ),
  ) );
}
add_action( 'init', 'stemlp_register_button_styles' );

/**
 * Ensure featured section output uses only featured-* classes (no hero-*).
 * Rewrites legacy hero class names when rendering a group that has featured-section,
 * so content saved before the pattern used featured-* is corrected on output.
 */
function stemlp_featured_section_rewrite_hero_classes( $block_content, $block ) {
  if ( ! isset( $block['blockName'] ) || $block['blockName'] !== 'core/group' ) {
    return $block_content;
  }
  $class_name = isset( $block['attrs']['className'] ) ? $block['attrs']['className'] : '';
  if ( strpos( $class_name, 'featured-section' ) === false ) {
    return $block_content;
  }
  $replace = array(
    'hero-col-image'      => 'featured-col-image',
    'hero-col-content'    => 'featured-col-content',
    'hero-title-excerpt'  => 'featured-title-excerpt',
    'hero-default-image'  => 'featured-default-image',
  );
  return str_replace( array_keys( $replace ), array_values( $replace ), $block_content );
}
add_filter( 'render_block', 'stemlp_featured_section_rewrite_hero_classes', 10, 2 );

/**
 * Strip hero-section from the group wrapper when it also has featured-section
 * (legacy pattern had both classes on the outer div).
 */
function stemlp_featured_section_strip_hero_class( $content ) {
  $content = str_replace( array( 'hero-section featured-section', 'featured-section hero-section' ), 'featured-section', $content );
  return $content;
}
add_filter( 'the_content', 'stemlp_featured_section_strip_hero_class', 5 );

// Force load text domain for media library
function stemlp_force_load_textdomain() {
    load_child_theme_textdomain( 'stemlp', get_stylesheet_directory() . '/languages' );
}
add_action( 'admin_init', 'stemlp_force_load_textdomain' );
add_action( 'wp_ajax_query-attachments', 'stemlp_force_load_textdomain' );
add_action( 'wp_ajax_save-attachment', 'stemlp_force_load_textdomain' );

// Load Cover title positioning enhancements
function twentytwentyfive_child_enqueue_styles() {
    // Load parent theme CSS
    wp_enqueue_style(
        'twentytwentyfive-style',
        get_template_directory_uri() . '/style.css'
    );

    // Load child theme CSS
    wp_enqueue_style(
        'stemlp-style',
        get_stylesheet_directory_uri() . '/style.css',
        array('twentytwentyfive-style')
    );
    wp_enqueue_script( 'custom-js', get_stylesheet_directory_uri() . '/assets/js/custom.js', array(), null, true );

}

add_action('wp_enqueue_scripts', 'twentytwentyfive_child_enqueue_styles');

/**
 * Load theme styles in the block editor so footer (gradient, diagonal, columns) looks correct.
 */
function stemlp_editor_styles() {
  add_theme_support( 'editor-styles' );
  add_editor_style( 'style.css' );
}
add_action( 'after_setup_theme', 'stemlp_editor_styles', 11 );

/**
 * Enable excerpt field for pages (normally only available for posts).
 */
function stemlp_page_excerpts() {
  add_post_type_support( 'page', 'excerpt' );
}
add_action( 'init', 'stemlp_page_excerpts' );

/**
 * Get event info string (date, time, location) same as events carousel. Returns empty string if not an event or no data.
 *
 * @param int $event_id Post ID of the event.
 * @return array{ date_label: string, show_time: bool, start_date: string, start_time: string, location_name: string, location_town: string }
 */
function stemlp_get_event_info( $event_id ) {
  $out = array(
    'date_label'     => '',
    'show_time'      => false,
    'start_date'     => '',
    'start_time'     => '',
    'location_name'  => '',
    'location_town'  => '',
  );
  $start_date = get_post_meta( $event_id, 'event_start_date', true ) ?: get_post_meta( $event_id, '_event_start_date', true );
  $start_time = get_post_meta( $event_id, 'event_start_time', true ) ?: get_post_meta( $event_id, '_event_start_time', true );
  $all_day   = get_post_meta( $event_id, '_event_all_day', true ) ?: get_post_meta( $event_id, 'event_all_day', true );
  if ( $start_date ) {
    $out['start_date']  = $start_date;
    $out['start_time']  = $start_time;
    $out['date_label']   = date_i18n( get_option( 'date_format' ), strtotime( $start_date ) );
    $out['show_time']   = ! $all_day && $start_time && $start_time !== '00:00:00' && $start_time !== '00:00';
    if ( $out['show_time'] ) {
      $out['date_label'] .= ' ' . date_i18n( get_option( 'time_format' ), strtotime( $start_date . ' ' . $start_time ) );
    }
  }
  if ( function_exists( 'em_get_event' ) && function_exists( 'em_get_location' ) ) {
    $em_event = em_get_event( $event_id, 'post_id' );
    if ( $em_event && ! empty( $em_event->location_id ) ) {
      $em_location = em_get_location( $em_event->location_id );
      if ( $em_location ) {
        $out['location_name'] = is_object( $em_location ) && isset( $em_location->location_name ) ? $em_location->location_name : '';
        $out['location_town']  = is_object( $em_location ) && isset( $em_location->location_town ) ? $em_location->location_town : '';
      }
    }
  }
  return $out;
}

/**
 * Get event info as a single line of text (date, time, location) for excerpt/display.
 */
function stemlp_get_event_info_text( $event_id ) {
  $info = stemlp_get_event_info( $event_id );
  $parts = array();
  if ( $info['date_label'] !== '' ) {
    $parts[] = $info['date_label'];
  }
  $loc = array_filter( array( $info['location_name'], $info['location_town'] ) );
  if ( ! empty( $loc ) ) {
    $parts[] = implode( ', ', $loc );
  }
  return implode( ' · ', $parts );
}

/**
 * Get excerpt for a post: manual excerpt or first paragraph of content, always single paragraph.
 * For events with no excerpt, uses date and location (same as events carousel).
 */
function stemlp_get_first_paragraph_excerpt( $post ) {
  if ( ! $post instanceof WP_Post ) {
    $post = is_numeric( $post ) ? get_post( (int) $post ) : null;
  }
  if ( ! $post instanceof WP_Post ) {
    return '';
  }
  $excerpt_trimmed = trim( (string) $post->post_excerpt );
  if ( $excerpt_trimmed !== '' ) {
    $text = $excerpt_trimmed;
  } elseif ( $post->post_type === 'event' ) {
    $text = stemlp_get_event_info_text( $post->ID );
  }
  if ( ! isset( $text ) || $text === '' ) {
    $content = $post->post_content;
    if ( trim( $content ) === '' ) {
      return '';
    }
    $content = do_blocks( $content );
    $content = strip_shortcodes( $content );
    if ( preg_match( '/<p[^>]*>([\s\S]*?)<\/p>/', $content, $m ) ) {
      $text = trim( strip_tags( $m[1] ) );
    } else {
      $content = strip_tags( $content );
      $content = preg_replace( '/\s+/', ' ', trim( $content ) );
      $text   = $content !== '' ? wp_trim_words( $content, 55 ) : '';
    }
  }
  if ( $text === '' ) {
    return '';
  }
  $text = strip_tags( $text );
  $text = preg_replace( '/\s*[\r\n]\s*[\r\n]\s*/s', "\n\n", $text );
  $parts = preg_split( '/\n\n+/', $text, 2 );
  return trim( $parts[0] );
}

/**
 * Excerpt = only the first paragraph. If no excerpt is set, use first paragraph of content.
 */
function stemlp_excerpt_first_paragraph_fallback( $excerpt, $post ) {
  $post_obj = $post;
  if ( ! $post_obj instanceof WP_Post && is_numeric( $post_obj ) ) {
    $post_obj = get_post( (int) $post_obj );
  }
  if ( ! $post_obj instanceof WP_Post ) {
    global $post;
    $post_obj = $post;
  }
  if ( ! $post_obj instanceof WP_Post ) {
    return $excerpt;
  }
  $text = stemlp_get_first_paragraph_excerpt( $post_obj );
  return $text !== '' ? $text : $excerpt;
}
add_filter( 'get_the_excerpt', 'stemlp_excerpt_first_paragraph_fallback', 10, 2 );

/**
 * Post Excerpt block: ensure excerpt is first paragraph (block may not pass post to get_the_excerpt).
 */
function stemlp_post_excerpt_block_excerpt( $block_content, $block ) {
  if ( ! is_string( $block_content ) || strpos( $block_content, 'wp-block-post-excerpt' ) === false ) {
    return $block_content;
  }
  $post_id = isset( $block['context']['postId'] ) ? (int) $block['context']['postId'] : 0;
  if ( ! $post_id ) {
    global $post;
    $post_id = $post ? (int) $post->ID : 0;
  }
  if ( ! $post_id ) {
    return $block_content;
  }
  $excerpt = stemlp_get_first_paragraph_excerpt( $post_id );
  if ( $excerpt === '' ) {
    return $block_content;
  }
  // Replace the excerpt paragraph content with our single-paragraph excerpt
  $block_content = preg_replace( '/(<p[^>]*wp-block-post-excerpt__excerpt[^>]*>)[\s\S]*?(<\/p>)/', '$1' . esc_html( $excerpt ) . '$2', $block_content, 1 );
  return $block_content;
}
add_filter( 'render_block_core/post-excerpt', 'stemlp_post_excerpt_block_excerpt', 10, 2 );

/**
 * Allow SVG uploads in the media library (WordPress blocks them by default for security).
 * Uploaded SVGs are sanitized to remove scripts and event handlers.
 */
function stemlp_allow_svg_upload( $mimes ) {
  $mimes['svg']  = 'image/svg+xml';
  $mimes['svgz'] = 'image/svg+xml';
  return $mimes;
}
add_filter( 'upload_mimes', 'stemlp_allow_svg_upload' );

/**
 * Sanitize uploaded SVG to prevent XSS (strip script, event handlers, javascript: URLs).
 */
function stemlp_sanitize_svg_on_upload( $file ) {
  $ext = strtolower( pathinfo( $file['name'], PATHINFO_EXTENSION ) );
  $is_svg = ( $file['type'] === 'image/svg+xml' || in_array( $ext, array( 'svg', 'svgz' ), true ) );
  if ( ! $is_svg || empty( $file['tmp_name'] ) ) {
    return $file;
  }
  $content = file_get_contents( $file['tmp_name'] );
  if ( $content === false ) {
    return $file;
  }
  // Remove script tags and their content
  $content = preg_replace( '/<script\b[^>]*>[\s\S]*?<\/script>/i', '', $content );
  // Remove style tags that could contain expression() or url(javascript:...)
  $content = preg_replace( '/<style\b[^>]*>[\s\S]*?<\/style>/i', '', $content );
  // Remove event handlers (onclick, onload, etc.) and javascript: URLs in attributes
  $content = preg_replace( '/\s+on\w+\s*=\s*["\'][^"\']*["\']/i', '', $content );
  $content = preg_replace( '/\s+on\w+\s*=\s*[^\s>]+/i', '', $content );
  $content = preg_replace( '/\b(href|xlink:href)\s*=\s*["\']?\s*javascript:/i', '$1="#"', $content );
  if ( file_put_contents( $file['tmp_name'], $content ) === false ) {
    $file['error'] = __( 'SVG could not be sanitized.', 'stemlp' );
  }
  return $file;
}
add_filter( 'wp_handle_upload_pre', 'stemlp_sanitize_svg_on_upload' );

/**
 * Show only this theme's template parts in the editor sidebar (e.g. footer/header picker).
 * Hides parent theme's parts (e.g. "Footer newsletter", "Footer columns") so only ours is visible.
 * Does NOT filter when resolving a single template part (e.g. loading footer for display).
 */
function stemlp_filter_template_parts_in_editor( $query_result, $query, $template_type ) {
  if ( $template_type !== 'wp_template_part' ) {
    return $query_result;
  }
  // Do not filter when resolving a specific template part (slug or wp_id) — keeps footer/header loadable.
  if ( ! empty( $query['slug__in'] ) || ! empty( $query['wp_id'] ) ) {
    return $query_result;
  }
  // Only filter in editor context (block variations and REST list are used there).
  $is_editor = ( defined( 'REST_REQUEST' ) && REST_REQUEST ) || is_admin();
  if ( ! $is_editor ) {
    return $query_result;
  }
  $current_theme = get_stylesheet();
  $theme_basename = basename( get_stylesheet_directory() );
  $allowed_themes = array_unique( array( $current_theme, $theme_basename ) );
  $filtered = array_filter( $query_result, function ( $template ) use ( $allowed_themes ) {
    return isset( $template->theme ) && in_array( $template->theme, $allowed_themes, true );
  } );
  // If filtering removed everything (e.g. theme slug mismatch), exclude parent instead so ours can show.
  if ( empty( $filtered ) && ! empty( $query_result ) ) {
    $parent = get_template();
    $filtered = array_filter( $query_result, function ( $template ) use ( $parent ) {
      return ! isset( $template->theme ) || $template->theme !== $parent;
    } );
  }
  return array_values( $filtered );
}
add_filter( 'get_block_templates', 'stemlp_filter_template_parts_in_editor', 10, 3 );

/**
 * Template parts that always render from theme files (footer only).
 * Header is editable in the Site Editor; saves are stored in the database.
 */
function stemlp_file_backed_template_parts() {
  return array( 'footer' );
}

/**
 * REST API: filter parent theme parts and inject file content for file-backed template parts.
 */
function stemlp_rest_filter_template_parts_list( $response, $server, $request ) {
  if ( ! $response instanceof WP_REST_Response || $response->get_status() !== 200 ) {
    return $response;
  }
  $route = $request->get_route();
  if ( strpos( $route, '/wp/v2/template-parts' ) !== 0 || $request->get_method() !== 'GET' ) {
    return $response;
  }
  $data = $response->get_data();
  if ( ! is_array( $data ) ) {
    return $response;
  }

  $our_theme     = get_stylesheet();
  $parts_dir     = get_stylesheet_directory() . '/parts/';
  $file_backed   = stemlp_file_backed_template_parts();

  // Single template part response (slug/theme at top level): inject theme file content when file-backed.
  if ( array_key_exists( 'slug', $data ) && isset( $data['theme'] ) && $data['theme'] === $our_theme ) {
    $slug = $data['slug'];
    $file = $parts_dir . $slug . '.html';
    if ( $slug && in_array( $slug, $file_backed, true ) && is_readable( $file ) ) {
      $raw = (string) file_get_contents( $file );
      $data['content'] = is_array( $data['content'] ?? null )
        ? array_merge( (array) $data['content'], array( 'raw' => $raw ) )
        : array( 'raw' => $raw );
      $response->set_data( $data );
    }
    return $response;
  }

  // Collection (list): filter out parent theme parts and use theme file content for ours.
  $parent_theme = get_template();
  $filtered     = array();
  foreach ( $data as $item ) {
    if ( ! is_array( $item ) ) {
      $filtered[] = $item;
      continue;
    }
    $theme = isset( $item['theme'] ) ? $item['theme'] : '';
    if ( $theme === $parent_theme ) {
      continue;
    }
    if ( $theme === $our_theme && ! empty( $item['slug'] ) ) {
      $file = $parts_dir . $item['slug'] . '.html';
      if ( in_array( $item['slug'], $file_backed, true ) && is_readable( $file ) ) {
        $raw = (string) file_get_contents( $file );
        $item['content'] = is_array( $item['content'] ?? null )
          ? array_merge( (array) $item['content'], array( 'raw' => $raw ) )
          : array( 'raw' => $raw );
      }
    }
    $filtered[] = $item;
  }
  $response->set_data( array_values( $filtered ) );
  return $response;
}
add_filter( 'rest_post_dispatch', 'stemlp_rest_filter_template_parts_list', 10, 3 );

/**
 * Use theme file content for file-backed template parts (footer).
 */
function stemlp_force_template_part_content_from_file( $block_template, $id, $template_type ) {
  if ( $template_type !== 'wp_template_part' || ! $block_template instanceof WP_Block_Template ) {
    return $block_template;
  }
  $our_theme = get_stylesheet();
  if ( empty( $block_template->theme ) || $block_template->theme !== $our_theme ) {
    return $block_template;
  }
  $slug = $block_template->slug;
  if ( ! in_array( $slug, stemlp_file_backed_template_parts(), true ) ) {
    return $block_template;
  }
  $file = get_stylesheet_directory() . '/parts/' . $slug . '.html';
  if ( is_readable( $file ) ) {
    $block_template->content = (string) file_get_contents( $file );
  }
  return $block_template;
}
add_filter( 'get_block_template', 'stemlp_force_template_part_content_from_file', 10, 3 );

/**
 * Render file-backed template parts from theme files on the front end (footer).
 */
function stemlp_render_template_part_from_theme_file( $block_content, $block ) {
  if ( ! is_array( $block ) ) {
    return $block_content;
  }
  $slug  = $block['attrs']['slug'] ?? '';
  $theme = $block['attrs']['theme'] ?? get_stylesheet();
  if ( $theme !== get_stylesheet() || ! in_array( $slug, stemlp_file_backed_template_parts(), true ) ) {
    return $block_content;
  }
  $file = get_stylesheet_directory() . '/parts/' . $slug . '.html';
  if ( ! is_readable( $file ) ) {
    return $block_content;
  }

  $content = (string) file_get_contents( $file );
  $content = shortcode_unautop( $content );
  $content = do_shortcode( $content );
  $content = do_blocks( $content );
  $content = wptexturize( $content );
  $content = convert_smilies( $content );
  $content = wp_filter_content_tags( $content, 'template_part_' . ( $block['attrs']['area'] ?? $slug ) );
  global $wp_embed;
  if ( $wp_embed instanceof WP_Embed ) {
    $content = $wp_embed->autoembed( $content );
  }

  if ( preg_match( '/^<(header|div)\b([^>]*)>/', $block_content, $matches ) ) {
    $tag = $matches[1];
    return '<' . $tag . $matches[2] . '>' . $content . '</' . $tag . '>';
  }

  return $content;
}
add_filter( 'render_block_core/template-part', 'stemlp_render_template_part_from_theme_file', 5, 2 );

/**
 * Ensure header logo Image block link is rendered (template part content from file
 * can leave core/image link attributes unapplied, so the anchor is missing in output).
 */
function stemlp_ensure_header_logo_link( $block_content, $block ) {
  if ( ! is_string( $block_content ) || $block_content === '' ) {
    return $block_content;
  }
  // Detect header logo: by block (core/image with header-logo class) or by output HTML
  $is_header_logo = false;
  $href           = home_url( '/' );
  if ( isset( $block['blockName'] ) && $block['blockName'] === 'core/image' ) {
    $attrs = $block['attrs'] ?? array();
    if ( isset( $attrs['className'] ) && strpos( $attrs['className'], 'header-logo' ) !== false ) {
      $is_header_logo = true;
      if ( ! empty( $attrs['href'] ) && is_string( $attrs['href'] ) ) {
        $href = $attrs['href'];
      }
    }
  }
  if ( ! $is_header_logo && strpos( $block_content, 'header-logo' ) !== false && strpos( $block_content, 'wp-block-image' ) !== false ) {
    $is_header_logo = true;
  }
  if ( ! $is_header_logo ) {
    return $block_content;
  }
  // If output already contains a link, leave it as is.
  if ( preg_match( '/<a\s[^>]*href\s*=/', $block_content ) ) {
    return $block_content;
  }
  $href = esc_url( $href );
  // Wrap the single <img> in <a href="...">…</a>
  $block_content = preg_replace( '/<img\s/', '<a href="' . $href . '"><img ', $block_content, 1 );
  $block_content = preg_replace( '/<\/figure>/', '</a></figure>', $block_content, 1 );
  return $block_content;
}
add_filter( 'render_block', 'stemlp_ensure_header_logo_link', 20, 2 );

/**
 * When header template part is rendered, ensure logo figure contains a working link
 * (fixes cases where inner block render_block did not run or link was stripped).
 */
function stemlp_ensure_header_part_logo_link( $block_content, $block ) {
  if ( ! is_string( $block_content ) || $block_content === '' ) {
    return $block_content;
  }
  $slug = isset( $block['attrs']['slug'] ) ? $block['attrs']['slug'] : '';
  if ( $slug !== 'header' ) {
    return $block_content;
  }
  if ( strpos( $block_content, 'header-logo' ) === false ) {
    return $block_content;
  }
  // Logo figure already has a link (pattern: figure...header-logo...><a)
  if ( preg_match( '/<figure\s[^>]*header-logo[^>]*>\s*<a\s[^>]*href/', $block_content ) ) {
    return $block_content;
  }
  // Wrap logo img in <a href="/"> and close before </figure>
  $home = esc_url( home_url( '/' ) );
  $block_content = preg_replace( '/(<figure\s[^>]*class="[^"]*header-logo[^"]*"[^>]*>)\s*<img\s/', '$1<a href="' . $home . '"><img ', $block_content, 1 );
  $block_content = preg_replace( '/<\/figure>/', '</a></figure>', $block_content, 1 );
  return $block_content;
}
add_filter( 'render_block_core/template-part', 'stemlp_ensure_header_part_logo_link', 10, 2 );

/**
 * Get a published wp_navigation post by slug (footer menu slots).
 *
 * @param string $slug Navigation post slug, e.g. footer-menu-1.
 */
function stemlp_get_footer_navigation_post( $slug ) {
  static $cache = array();
  $slug         = sanitize_title( $slug );
  if ( $slug === '' ) {
    return null;
  }
  if ( ! array_key_exists( $slug, $cache ) ) {
    $posts = get_posts(
      array(
        'name'                   => $slug,
        'post_type'              => 'wp_navigation',
        'post_status'            => 'publish',
        'posts_per_page'         => 1,
        'no_found_rows'          => true,
        'update_post_meta_cache' => false,
        'update_post_term_cache' => false,
      )
    );
    $cache[ $slug ] = ! empty( $posts[0] ) ? $posts[0] : null;
  }
  return $cache[ $slug ];
}

/**
 * Extract footer navigation slug from stemlp-nav-* or stemlp-nav-title-* class names.
 */
function stemlp_footer_navigation_slug_from_class( $class_name ) {
  if ( preg_match( '/stemlp-nav-title-([a-z0-9-]+)/', (string) $class_name, $matches ) ) {
    return $matches[1];
  }
  if ( preg_match( '/stemlp-nav-([a-z0-9-]+)/', (string) $class_name, $matches ) ) {
    return $matches[1];
  }
  return '';
}

/**
 * Point footer Navigation blocks at wp_navigation posts by slug (sets ref before render).
 */
function stemlp_footer_navigation_block_data( $parsed_block ) {
  if ( ( $parsed_block['blockName'] ?? '' ) !== 'core/navigation' ) {
    return $parsed_block;
  }
  $slug = stemlp_footer_navigation_slug_from_class( $parsed_block['attrs']['className'] ?? '' );
  if ( $slug === '' ) {
    return $parsed_block;
  }
  $navigation_post = stemlp_get_footer_navigation_post( $slug );
  if ( ! $navigation_post ) {
    return $parsed_block;
  }
  $parsed_block['attrs']['ref'] = (int) $navigation_post->ID;
  return $parsed_block;
}
add_filter( 'render_block_data', 'stemlp_footer_navigation_block_data', 10, 1 );

/**
 * Footer heading: use the linked navigation post title; hide when navigation is missing.
 */
function stemlp_footer_navigation_title( $block_content, $block ) {
  if ( ( $block['blockName'] ?? '' ) !== 'core/heading' ) {
    return $block_content;
  }
  $class_name = $block['attrs']['className'] ?? '';
  if ( strpos( $class_name, 'stemlp-nav-title-' ) === false ) {
    return $block_content;
  }
  $slug = stemlp_footer_navigation_slug_from_class( $class_name );
  if ( $slug === '' ) {
    return $block_content;
  }
  $navigation_post = stemlp_get_footer_navigation_post( $slug );
  if ( ! $navigation_post ) {
    return '';
  }
  return preg_replace(
    '/(<h3[^>]*>)([\s\S]*?)(<\/h3>)/',
    '$1' . esc_html( $navigation_post->post_title ) . '$3',
    $block_content,
    1
  );
}
add_filter( 'render_block_core/heading', 'stemlp_footer_navigation_title', 10, 2 );

/**
 * Hide footer Navigation blocks when their wp_navigation post does not exist.
 */
function stemlp_footer_navigation_render( $block_content, $block ) {
  if ( ( $block['blockName'] ?? '' ) !== 'core/navigation' ) {
    return $block_content;
  }
  $slug = stemlp_footer_navigation_slug_from_class( $block['attrs']['className'] ?? '' );
  if ( $slug === '' ) {
    return $block_content;
  }
  if ( ! stemlp_get_footer_navigation_post( $slug ) ) {
    return '';
  }
  return $block_content;
}
add_filter( 'render_block_core/navigation', 'stemlp_footer_navigation_render', 10, 2 );

/**
 * Allowed HTML for raw newsletter form markup stored in the customizer.
 */
function stemlp_newsletter_allowed_html() {
  return array(
    'form'     => array(
      'action'   => true,
      'method'   => true,
      'class'    => true,
      'id'       => true,
      'target'   => true,
      'enctype'  => true,
      'novalidate' => true,
    ),
    'input'    => array(
      'type'         => true,
      'name'         => true,
      'value'        => true,
      'class'        => true,
      'id'           => true,
      'placeholder'  => true,
      'required'     => true,
      'checked'      => true,
      'autocomplete' => true,
    ),
    'label'    => array(
      'for'   => true,
      'class' => true,
    ),
    'button'   => array(
      'type'  => true,
      'class' => true,
      'name'  => true,
      'value' => true,
    ),
    'select'   => array(
      'name'     => true,
      'class'    => true,
      'id'       => true,
      'required' => true,
    ),
    'option'   => array(
      'value'    => true,
      'selected' => true,
    ),
    'textarea' => array(
      'name'        => true,
      'class'       => true,
      'id'          => true,
      'placeholder' => true,
      'required'    => true,
      'rows'        => true,
      'cols'        => true,
    ),
    'p'        => array( 'class' => true ),
    'div'      => array( 'class' => true ),
    'span'     => array( 'class' => true ),
    'a'        => array(
      'href'   => true,
      'class'  => true,
      'target' => true,
      'rel'    => true,
    ),
  );
}

/**
 * Return configured newsletter form markup (shortcode or HTML), or empty string if unset.
 */
function stemlp_get_newsletter_form_markup() {
  $form = trim( (string) get_theme_mod( 'stemlp_newsletter_form', '' ) );
  if ( $form === '' ) {
    return '';
  }
  if ( strpos( $form, '[' ) !== false ) {
    return do_shortcode( $form );
  }
  return wp_kses( $form, stemlp_newsletter_allowed_html() );
}

/**
 * Shortcode for the footer newsletter slot.
 */
function stemlp_newsletter_form_shortcode() {
  return stemlp_get_newsletter_form_markup();
}
add_shortcode( 'stemlp_newsletter_form', 'stemlp_newsletter_form_shortcode' );

/**
 * Hide the stay-informed strip when no newsletter form is configured.
 */
function stemlp_footer_stay_informed_columns( $block_content, $block ) {
  if ( ! is_string( $block_content ) || $block_content === '' ) {
    return $block_content;
  }
  $class_name = isset( $block['attrs']['className'] ) ? $block['attrs']['className'] : '';
  if ( strpos( $class_name, 'footer-stay-informed' ) === false ) {
    return $block_content;
  }
  if ( stemlp_get_newsletter_form_markup() === '' ) {
    return '';
  }
  return $block_content;
}
add_filter( 'render_block_core/columns', 'stemlp_footer_stay_informed_columns', 10, 2 );

/**
 * Customizer: newsletter form for the footer stay-informed strip.
 */
function stemlp_customize_register( $wp_customize ) {
  $wp_customize->add_section(
    'stemlp_footer',
    array(
      'title'       => __( 'Footer', 'stemlp' ),
      'description' => __( 'Configure the newsletter form shown in the footer.', 'stemlp' ),
      'priority'    => 160,
    )
  );

  $wp_customize->add_setting(
    'stemlp_newsletter_form',
    array(
      'default'           => '',
      'sanitize_callback' => 'stemlp_sanitize_newsletter_form',
      'transport'         => 'refresh',
    )
  );

  $wp_customize->add_control(
    'stemlp_newsletter_form',
    array(
      'label'       => __( 'Newsletter form', 'stemlp' ),
      'description' => __( 'Shortcode (e.g. [contact-form-7 id="123"]) or HTML for the footer signup form. Leave empty to hide the strip.', 'stemlp' ),
      'section'     => 'stemlp_footer',
      'type'        => 'textarea',
    )
  );
}
add_action( 'customize_register', 'stemlp_customize_register' );

/**
 * Allow shortcodes and safe HTML in the newsletter form setting.
 */
function stemlp_sanitize_newsletter_form( $value ) {
  return is_string( $value ) ? trim( $value ) : '';
}

/**
 * Replace merged theme.json templateParts with only ours, so the editor does not show
 * parent options (Footer newsletter, Footer columns, Vertical header, etc.).
 */
function stemlp_theme_json_only_our_template_parts( $theme_json ) {
  $ours = array(
    array(
      'area'        => 'header',
      'name'        => 'header',
      'title'       => __( 'Header', 'stemlp' ),
      'description' => __( 'Default site header with logo, buttons, and page hero.', 'stemlp' ),
    ),
    array(
      'area'        => 'footer',
      'name'        => 'footer',
      'title'       => __( 'Footer', 'stemlp' ),
      'description' => __( 'Default site footer with diagonal and columns.', 'stemlp' ),
    ),
  );
  return $theme_json->update_with( array( 'templateParts' => $ours ) );
}
add_filter( 'wp_theme_json_data_theme', 'stemlp_theme_json_only_our_template_parts', 99, 1 );

/**
 * Post ID whose title, excerpt, and featured image feed the header hero.
 */
function stemlp_get_header_hero_post_id() {
  if ( is_singular( array( 'post', 'page' ) ) ) {
    $post_id = get_queried_object_id();
    if ( $post_id ) {
      return (int) $post_id;
    }
  }

  if ( get_option( 'show_on_front' ) !== 'page' ) {
    return 0;
  }

  $front_page_id = (int) get_option( 'page_on_front' );
  if ( ! $front_page_id ) {
    return 0;
  }

  if ( is_front_page() ) {
    return $front_page_id;
  }

  global $wp;
  if ( isset( $wp ) && ( ! isset( $wp->request ) || $wp->request === '' ) ) {
    return $front_page_id;
  }

  return 0;
}

/**
 * Whether the header hero should render on the current request.
 */
function stemlp_should_show_header_hero() {
  if ( is_404() ) {
    return true;
  }
  $post_id = stemlp_get_header_hero_post_id();
  if ( ! $post_id ) {
    return false;
  }
  $title_position = get_post_meta( $post_id, 'title_position', true );
  return $title_position !== 'off';
}

/**
 * Parent theme hero image used on 404 pages.
 */
function stemlp_get_404_hero_image_url() {
  return get_template_directory_uri() . '/assets/images/404-image.webp';
}

/**
 * Hero title, excerpt, and image output on 404 (uses parent theme 404 image).
 */
function stemlp_header_hero_404_blocks( $block_content, $block ) {
  if ( ! is_404() ) {
    return $block_content;
  }
  $block_name = $block['blockName'] ?? '';
  if ( $block_name === 'core/image' ) {
    $class_name = $block['attrs']['className'] ?? '';
    if ( strpos( $class_name, 'hero-default-image' ) === false ) {
      return $block_content;
    }
    $url = stemlp_get_404_hero_image_url();
    $alt = __( 'Small totara tree on ridge above Long Point', 'twentytwentyfive' );
    return sprintf(
      '<figure class="wp-block-image size-full hero-default-image"><img src="%s" alt="%s" decoding="async"/></figure>',
      esc_url( $url ),
      esc_attr( $alt )
    );
  }
  if ( $block_name === 'core/post-featured-image' ) {
    $class_name = $block['attrs']['className'] ?? '';
    if ( strpos( $class_name, 'hero-featured-image' ) !== false ) {
      return '';
    }
    return $block_content;
  }
  if ( ( $block['attrs']['textAlign'] ?? '' ) !== 'right' ) {
    return $block_content;
  }
  if ( $block_name === 'core/post-title' ) {
    return '<h1 class="has-text-align-right wp-block-post-title">' . esc_html__( 'Page not found', 'stemlp' ) . '</h1>';
  }
  if ( $block_name === 'core/post-excerpt' ) {
    return '<div class="has-text-align-right wp-block-post-excerpt"><p class="wp-block-post-excerpt__excerpt">' . esc_html__( "The page you are looking for doesn't exist, or it has been moved.", 'stemlp' ) . '</p></div>';
  }
  return $block_content;
}
add_filter( 'render_block_core/image', 'stemlp_header_hero_404_blocks', 10, 2 );
add_filter( 'render_block_core/post-title', 'stemlp_header_hero_404_blocks', 10, 2 );
add_filter( 'render_block_core/post-excerpt', 'stemlp_header_hero_404_blocks', 10, 2 );
add_filter( 'render_block_core/post-featured-image', 'stemlp_header_hero_404_blocks', 10, 2 );

/**
 * Give header hero post blocks the front-page/singular post context they need.
 */
function stemlp_header_hero_post_context( $context, $parsed_block, $parent_block ) {
  if ( ! empty( $context['postId'] ) ) {
    return $context;
  }
  $post_id = stemlp_get_header_hero_post_id();
  if ( ! $post_id ) {
    return $context;
  }
  $post_blocks = array( 'core/post-title', 'core/post-excerpt', 'core/post-featured-image' );
  if ( ! in_array( $parsed_block['blockName'] ?? '', $post_blocks, true ) ) {
    return $context;
  }
  $context['postId']   = $post_id;
  $context['postType'] = get_post_type( $post_id );
  return $context;
}
add_filter( 'render_block_context', 'stemlp_header_hero_post_context', 10, 3 );

/**
 * Featured image focal point (Post sidebar picker + object-position on the front end).
 */
function stemlp_setup_featured_image_focal_point() {
  add_theme_support( 'featured-image-focal-point' );

  foreach ( array( 'post', 'page' ) as $post_type ) {
    add_post_type_support( $post_type, 'custom-fields' );

    register_post_meta(
      $post_type,
      'title_position',
      array(
        'show_in_rest'  => true,
        'single'        => true,
        'type'          => 'string',
        'description'   => __( 'Header hero visibility.', 'stemlp' ),
        'auth_callback' => function () {
          return current_user_can( 'edit_posts' );
        },
      )
    );

    register_post_meta(
      $post_type,
      'featured_image_focal_point',
      array(
        'show_in_rest'  => array(
          'schema' => array(
            'type'       => 'object',
            'properties' => array(
              'x' => array(
                'type' => 'number',
              ),
              'y' => array(
                'type' => 'number',
              ),
            ),
          ),
        ),
        'single'        => true,
        'type'          => 'object',
        'description'   => __( 'Featured image focal point.', 'stemlp' ),
        'auth_callback' => function () {
          return current_user_can( 'edit_posts' );
        },
      )
    );
  }
}
add_action( 'init', 'stemlp_setup_featured_image_focal_point' );

/**
 * CSS object-position value from featured_image_focal_point post meta.
 */
function stemlp_get_featured_image_focal_point_css( $post_id = 0 ) {
  if ( is_404() ) {
    return '50% 50%';
  }

  if ( ! $post_id ) {
    $post_id = stemlp_get_header_hero_post_id();
  }
  if ( ! $post_id ) {
    return '50% 50%';
  }

  $focal = get_post_meta( $post_id, 'featured_image_focal_point', true );
  if ( is_array( $focal ) && isset( $focal['x'], $focal['y'] ) ) {
    return round( (float) $focal['x'] * 100, 2 ) . '% ' . round( (float) $focal['y'] * 100, 2 ) . '%';
  }

  return has_post_thumbnail( $post_id ) ? '50% 50%' : '50% 0%';
}

/**
 * Merge CSS declarations into an existing inline style attribute.
 */
function stemlp_merge_inline_style( $existing, $declarations ) {
  $style = is_string( $existing ) ? trim( $existing ) : '';
  if ( $style !== '' && ! str_ends_with( $style, ';' ) ) {
    $style .= ';';
  }
  foreach ( $declarations as $property => $value ) {
    $style = preg_replace( '/\b' . preg_quote( $property, '/' ) . '\s*:[^;]*;?/', '', $style );
    $style .= $property . ':' . $value . ';';
  }
  return trim( $style );
}

/**
 * Inline styles that keep focal point cropping active in all layouts.
 */
function stemlp_featured_image_focal_point_style( $post_id ) {
  $position = stemlp_get_featured_image_focal_point_css( $post_id );
  if ( ! $position ) {
    return '';
  }
  return stemlp_merge_inline_style(
    '',
    array(
      'object-fit'      => 'cover',
      'object-position' => $position,
    )
  );
}

/**
 * Add object-position to a featured image img tag HTML.
 */
function stemlp_apply_focal_point_to_img_html( $html, $post_id ) {
  if ( ! $html || ! $post_id ) {
    return $html;
  }

  $style = stemlp_featured_image_focal_point_style( $post_id );
  if ( ! $style ) {
    return $html;
  }

  if ( ! class_exists( 'WP_HTML_Tag_Processor' ) ) {
    return $html;
  }

  $processor = new WP_HTML_Tag_Processor( $html );
  if ( ! $processor->next_tag( 'img' ) ) {
    return $html;
  }

  $processor->set_attribute( 'style', stemlp_merge_inline_style( $processor->get_attribute( 'style' ) ?? '', array(
    'object-fit'      => 'cover',
    'object-position' => stemlp_get_featured_image_focal_point_css( $post_id ),
  ) ) );
  return $processor->get_updated_html();
}

/**
 * Set --hero-image-position on the first matching element in HTML.
 */
function stemlp_set_hero_image_position_var( $html, $class_fragment ) {
  $position = stemlp_get_featured_image_focal_point_css();
  if ( ! $position || ! class_exists( 'WP_HTML_Tag_Processor' ) ) {
    return $html;
  }

  $processor = new WP_HTML_Tag_Processor( $html );
  while ( $processor->next_tag() ) {
    $class = $processor->get_attribute( 'class' );
    if ( ! is_string( $class ) || strpos( $class, $class_fragment ) === false ) {
      continue;
    }
    $processor->set_attribute(
      'style',
      stemlp_merge_inline_style(
        $processor->get_attribute( 'style' ) ?? '',
        array( '--hero-image-position' => $position )
      )
    );
    return $processor->get_updated_html();
  }

  return $html;
}

/**
 * Featured image focal point for templated thumbnails (carousels, etc.).
 */
function stemlp_post_thumbnail_focal_point( $html, $post_id ) {
  return stemlp_apply_focal_point_to_img_html( $html, $post_id );
}
add_filter( 'post_thumbnail_html', 'stemlp_post_thumbnail_focal_point', 10, 2 );

/**
 * Featured image focal point for Post Featured Image blocks.
 */
function stemlp_post_featured_image_block_focal_point( $block_content, $block ) {
  if ( ( $block['blockName'] ?? '' ) !== 'core/post-featured-image' || ! $block_content ) {
    return $block_content;
  }

  $post_id = (int) ( $block['context']['postId'] ?? 0 );
  if ( ! $post_id ) {
    $post_id = stemlp_get_header_hero_post_id();
  }

  return stemlp_apply_focal_point_to_img_html( $block_content, $post_id );
}
add_filter( 'render_block_core/post-featured-image', 'stemlp_post_featured_image_block_focal_point', 20, 2 );

/**
 * Block editor: header hero panel + featured image focal point picker.
 */
function stemlp_enqueue_editor_assets() {
  $panel_file = get_stylesheet_directory() . '/assets/js/media-meta.js';
  if ( is_readable( $panel_file ) ) {
    wp_enqueue_script(
      'stemlp-header-hero-panel',
      get_stylesheet_directory_uri() . '/assets/js/media-meta.js',
      array( 'wp-plugins', 'wp-edit-post', 'wp-components', 'wp-data', 'wp-i18n', 'wp-element' ),
      filemtime( $panel_file ),
      true
    );
  }

  $focal_file = get_stylesheet_directory() . '/assets/js/featured-image-focal-point.js';
  if ( is_readable( $focal_file ) ) {
    wp_enqueue_script(
      'stemlp-featured-image-focal-point',
      get_stylesheet_directory_uri() . '/assets/js/featured-image-focal-point.js',
      array( 'wp-hooks', 'wp-compose', 'wp-element', 'wp-components', 'wp-core-data', 'wp-data', 'wp-i18n' ),
      filemtime( $focal_file ),
      true
    );
  }

  $focal_css = get_stylesheet_directory() . '/assets/css/focal-point-editor.css';
  if ( is_readable( $focal_css ) ) {
    wp_enqueue_style(
      'stemlp-focal-point-editor',
      get_stylesheet_directory_uri() . '/assets/css/focal-point-editor.css',
      array(),
      filemtime( $focal_css )
    );
  }
}
add_action( 'enqueue_block_editor_assets', 'stemlp_enqueue_editor_assets' );

/**
 * Add has-image / no-image modifier classes to the header hero.
 */
function stemlp_header_hero_block_data( $parsed_block ) {
  if ( ( $parsed_block['blockName'] ?? '' ) !== 'core/group' ) {
    return $parsed_block;
  }
  $class_name = $parsed_block['attrs']['className'] ?? '';
  if ( strpos( $class_name, 'hero-section' ) === false || strpos( $class_name, 'featured-section' ) !== false ) {
    return $parsed_block;
  }
  if ( ! stemlp_should_show_header_hero() ) {
    return $parsed_block;
  }
  if ( is_404() ) {
    $modifier = 'hero-section--no-image hero-section--404';
  } else {
    $modifier = has_post_thumbnail( stemlp_get_header_hero_post_id() ) ? 'hero-section--has-image' : 'hero-section--no-image';
  }
  $parsed_block['attrs']['className'] = trim( $class_name . ' ' . $modifier );
  return $parsed_block;
}
add_filter( 'render_block_data', 'stemlp_header_hero_block_data', 10, 1 );

/**
 * Hide the header hero on archives and when title position is set to off.
 */
function stemlp_header_hero_render( $block_content, $block ) {
  if ( ( $block['blockName'] ?? '' ) !== 'core/group' ) {
    return $block_content;
  }
  $class_name = $block['attrs']['className'] ?? '';
  if ( strpos( $class_name, 'hero-section' ) === false || strpos( $class_name, 'featured-section' ) !== false ) {
    return $block_content;
  }
  if ( ! stemlp_should_show_header_hero() ) {
    return '';
  }

  $position = stemlp_get_featured_image_focal_point_css();
  if ( $position ) {
    $block_content = stemlp_set_hero_image_position_var( $block_content, 'hero-section' );
    $block_content = stemlp_set_hero_image_position_var( $block_content, 'hero-col-image' );
  }

  return $block_content;
}
add_filter( 'render_block', 'stemlp_header_hero_render', 10, 2 );

/**
 * Register block types from block.json (recommended since WordPress 5.8).
 * Each block's "render": "file:./render.php" in block.json is used for server-side output (WP 6.1+).
 */
function stemlp_register_blocks() {
  $blocks_dir = get_stylesheet_directory() . '/blocks';
  $blocks     = array( 'carousel', 'events-carousel' );
  foreach ( $blocks as $name ) {
    $block_dir = $blocks_dir . '/' . $name;
    if ( is_readable( $block_dir . '/block.json' ) ) {
      register_block_type( $block_dir );
    }
  }
}
add_action( 'init', 'stemlp_register_blocks' );
