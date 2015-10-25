<?php
/*
Plugin Name: Fast WP_Query
Description: WP_Query MySQL optimization by using object cache
Version: 0.1
Plugin URI: https://github.com/shtrihstr/fast-wp-query
Author: Oleksandr Strikha
Author URI: https://github.com/shtrihstr
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
*/

if( ! wp_using_ext_object_cache() ) {
    return;
}

// ORDER BY RAND() optimization
add_action( 'pre_get_posts', function( $query ) {

    if( isset( $query->query_vars['orderby'] ) &&  'rand' == $query->query_vars['orderby'] ) {

        if( empty( $query->query_vars['post__in'] ) ) {

            $global_invalidate_time = wp_cache_get( 'invalidate-time', 'query' );
            $local_invalidate_time  = wp_cache_get( 'time-random-posts-' . $query->query_vars_hash, 'query' );

            if( $local_invalidate_time && $local_invalidate_time < $global_invalidate_time ) {
                wp_cache_delete( 'random-posts-' . $query->query_vars_hash, 'query' );
                wp_cache_delete( 'time-random-posts-' . $query->query_vars_hash, 'query' );
            }

            if( false === ( $ids = wp_cache_get( 'random-posts-' . $query->query_vars_hash, 'query' ) ) ) {
                $vars = $query->query_vars;
                $vars['orderby'] = 'date';
                $vars['order'] = 'DESC';
                $vars['posts_per_page'] = 1000; // do you really need more?
                $vars['showposts'] = null;
                $vars['posts_per_archive_page'] = null;
                $vars['fields'] = 'ids';
                $vars['no_found_rows'] = true;
                $vars['ignore_sticky_posts'] = true;
                $ids = get_posts($vars);
                wp_cache_set( 'random-posts-' . $query->query_vars_hash, $ids, 'query' );
                wp_cache_set( 'time-random-posts-' . $query->query_vars_hash, time(), 'query' );
            }
            shuffle( $ids );
            
            $limit = empty( $query->query_vars['posts_per_page'] ) ? get_option( 'posts_per_page' ) : $query->query_vars['posts_per_page'];

            if( $limit > 0 ) {
                $ids =  array_slice( $ids, 0, $limit );
            }

            $query->query_vars['post__in'] = $ids;
        }
        else {
            shuffle( $query->query_vars['post__in'] );
        }

        $query->query_vars['orderby'] = 'post__in';
        $query->query_vars['no_found_rows'] = true;
        $query->found_posts = count( $query->query_vars['post__in'] );
    }

} );


// SQL_CALC_FOUND_ROWS optimization
add_filter( 'posts_orderby', function( $orderby, $query ) {

    if( empty( $query->query_vars['no_found_rows'] ) ) {

        $global_invalidate_time = wp_cache_get( 'invalidate-time', 'query' );
        $local_invalidate_time  = wp_cache_get( 'time-found-posts-' . $query->query_vars_hash, 'query' );

        if( $local_invalidate_time && $local_invalidate_time < $global_invalidate_time ) {
            wp_cache_delete('found-posts-' . $query->query_vars_hash, 'query' );
            wp_cache_delete('time-found-posts-' . $query->query_vars_hash, 'query' );
            return $orderby;
        }

        if( false !== ( $found_posts = wp_cache_get( 'found-posts-' . $query->query_vars_hash, 'query' ) ) ) {
            $query->found_posts = $found_posts;
            $query->max_num_pages = ceil( $query->found_posts / $query->query_vars['posts_per_page'] );
            $query->query_vars['no_found_rows'] = true;
        }
    }
    return $orderby;
}, 10, 2 );

// SQL_CALC_FOUND_ROWS caching
add_filter( 'found_posts', function( $found_posts, $query ) {
    wp_cache_set( 'found-posts-' . $query->query_vars_hash, $found_posts, 'query' );
    wp_cache_set( 'time-found-posts-' . $query->query_vars_hash, time(), 'query' );
    return $found_posts;
}, 99, 2 );


// flush cache
add_action( 'save_post', function( $post_id ) {
    wp_cache_set( 'invalidate-time', time(), 'query' );
} );