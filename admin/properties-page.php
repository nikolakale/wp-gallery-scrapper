<?php

function remove_quick_edit($actions, $post)
{
    $actions['gallery_scrape'] = '<a href="#" onClick="syncGalleries(' . $post->ID . ')" rel="bookmark" >Scrape gallery</a>';
    return $actions;
}
add_filter('post_row_actions', 'remove_quick_edit', 10, 2);

?>