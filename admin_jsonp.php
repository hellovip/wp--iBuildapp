<?php

/*
 * JSONP
 */

function ibuildapp_jsonp_ajax()
{
    if (!headers_sent()) {
        header('Content-type: application/javascript');
    }

    if (!isset($_GET['callback'], $_GET['token'], $_GET['jsonpAction'], $_GET['jsonpData'])) {
        exit();
    }

    $fname = 'ibuildapp_jsonp_' . $_GET['jsonpAction'] . 'Action';
    if (!Ibuildapp::validateJsonpToken($_GET['token'])) {
        $fname = 'ibuildapp_jsonp_noActionFound';
    } elseif (!function_exists($fname)) {
        $fname = 'ibuildapp_jsonp_noActionFound';
    }

    printf(
        '%s(%s);',
        $_GET['callback'],
        json_encode($fname($_GET['jsonpData']))
    );
    exit();
}
add_action('wp_ajax_ibuildapp_jsonp_ajax', 'ibuildapp_jsonp_ajax');

function ibuildapp_jsonp_noActionFound()
{
    return array(
        'error' => true
    );
}

/**
 * Returns version info
 * @since 0.1.0
 */
function ibuildapp_jsonp_versionAction($data)
{
    return array(
        'type' => IBUILDAPP_PLUGIN_TYPE,
        'version' => IBUILDAPP_VERSION,
        'supports' => array(
            'goto' => 1,
            'feeds' => 1,
            'pages' => 1,
            'posts' => 1,
            'currentUser' => 1
        )
    );
}

/**
 * Echoes back all the data as-is
 * @since 0.1.0
 */
function ibuildapp_jsonp_echoAction($data)
{
    return array(
        'data' => $data
    );
}

/**
 * Returns info about ways to communicate with WP
 * @since 0.1.0
 */
function ibuildapp_jsonp_initAction($data)
{
    return array(
        'version' => ibuildapp_jsonp_versionAction($data),
        'goto' => array(
            'dashboard' => admin_url('admin.php?page=ibuildapp-iframe-redirect&noheader=true&targetPage=ibuildapp'),
            'management' => admin_url('admin.php?page=ibuildapp-iframe-redirect&noheader=true&targetPage=ibuildapp-app-management'),
            'publishing' => admin_url('admin.php?page=ibuildapp-iframe-redirect&noheader=true&targetPage=ibuildapp-app-publishing'),
            'notifications' => admin_url('admin.php?page=ibuildapp-iframe-redirect&noheader=true&targetPage=ibuildapp-app-notifications'),
            'monetize' => admin_url('admin.php?page=ibuildapp-iframe-redirect&noheader=true&targetPage=ibuildapp-app-monetize')
        )
    );
}

/**
 * Returns RSS feed url (rss2)
 * @see http://codex.wordpress.org/WordPress_Feeds
 * @since 0.1.0
 */
function ibuildapp_jsonp_feedsAction($data)
{
    $feeds = array();

    $feeds[] = array(
        'id' => 0,
        'title' => get_bloginfo('name'),
        'url' => get_bloginfo('rss2_url')
    );

    return array(
        'feeds' => $feeds
    );
}

/**
 * Returns all the pages
 * @see http://codex.wordpress.org/Function_Reference/get_pages
 * @since 0.1.0
 */
function ibuildapp_jsonp_pagesAction($data)
{
    $records = get_pages(array(
        'post_type' => 'page',
        'post_status' => 'publish'
    ));

    $pages = array();
    foreach ($records as $rec) {
        $pages[] = array(
            'id' => $rec->ID,
            'title' => $rec->post_title,
            'url' => $rec->guid
        );
    }

    return array(
        'pages' => $pages
    );
}

/**
 * Returns all the pages
 * @see http://codex.wordpress.org/Function_Reference/get_posts
 * @since 0.1.0
 */
function ibuildapp_jsonp_postsAction($data)
{
    $records = get_posts(array(
        'post_type' => 'post',
        'post_status' => 'publish'
    ));

    $posts = array();
    foreach ($records as $rec) {
        $posts[] = array(
            'id' => $rec->ID,
            'title' => $rec->post_title,
            'url' => $rec->guid
        );
    }

    return array(
        'posts' => $posts
    );
}

/**
 * Returns info about current user
 * @see
 * @since 0.1.0
 */
function ibuildapp_jsonp_currentUserAction($data)
{
    $user = wp_get_current_user();

    return array(
        'user' => array(
            'name' => $user->data->display_name,
            'email' => $user->data->user_email,
            'website' => get_site_url()
        )
    );
}
