<?php
require_once "ImageSource.php";

class Importer
{
    static function sync($scrapeId = null, $postId = null)
    {
        include 'Scrapper.php';
        include 'ScrapperSource.php';

        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/Importer.php';

        //$scraps = self::getScraps();
        //  foreach ($scraps as $scrap) {m
        // Get scrap settings
        $galleryScrapperSettings = get_option('gallery_scrapper_settings_option_name');
        $sourcesJson = $galleryScrapperSettings['sources_2'];
        $sources = json_decode($sourcesJson, true);
        if (!$sources || count($sources) == 0) {
            return;
        }

        if (!$scrapeId && $postId) {
            $post = get_post($postId);
            if (!$post) {
                return;
            }
            $scrapeId = get_post_meta($postId, '_scrape_task_id', true);
        }

        if ($scrapeId) {
            $filteredSources = [];
            foreach ($sources as $source) {
                if ($source['scrape'] === $scrapeId) {
                    $filteredSources = [$source];
                }
            }
            $sources = $filteredSources;
        }
        $errors = [];
        $syncedPosts = [];
        $imagesCount = 0;
        $message = '';
        
        if (count($sources) > 0) {
            foreach ($sources as $source) {

                $scrapperSource = new ScrapperSource();
                $scrapperSource->scrapeId = $source['scrape'];
                $scrapperSource->galleryContainerPath = $source['path'];
                $scrapperSource->galleryImageSrcAttributeName = $source['attribute'];
                $scrapperSource->galleryImageAltAttributeName = $source['alt'];

                $scrapper = new Scrapper($scrapperSource);

                if (!$postId) {
                    $posts = self::getPostsToScrapGallery($scrapperSource->scrapeId);
                } else {
                    $posts = [$post];
                }
                
                foreach ($posts as $post) {
                    $url = get_post_meta($post->ID, '_scrape_original_url', true);
                    if ($url) {

                        // Remove existing image
                        self::removeAttachedMedia($post->ID);

                        $images = $scrapper->getImages($url);
                        $i = 0;
                        foreach ($images as $image) {
                            try {
                                $thumbnailId = self::storeImageInMediaLibrary($image, $post->ID);
                                if (is_wp_error($thumbnailId)) {
                                    // Report error
                                } else {
                                    if ($i === 0) {
                                        set_post_thumbnail($post, $thumbnailId);
                                    }
                                }
                                $i++;
                                $imagesCount++;
                            } catch (Exception $e) {
                                $errors[] = $e->getMessage();
                            }
                        }
                        add_post_meta($post->ID, 'gallery_scrapped_at', time());
                        $syncedPosts = $post->ID;

                    }
                }
            }
        } else {
            $message = 'Gallery scrapper is not configured for scrape with id '. $scrapeId;
        }
        
        Header('Content-Type: application/json; charset=UTF8');

        if (!$message) {
                $message = 'Sync completed. Images synced: ' . $imagesCount;
                
                if ($errors) {
                    $message .= ' Please check console for errors'; 
                }

        }
        echo json_encode([
            'message' => $message,
            'errors' => $errors,
            'synced_posts' => $syncedPosts,
            'total_images_synced' => $imagesCount
        ]);
    }

    static function testSource()
    {
        // $sourceConfig, string $url
        $postId = intval($_POST['post_id']);
        $scrapeId = intval($_POST['scrape_id']);
        self::sync($scrapeId, $postId);
        die;
    }

    static function removeAttachedMedia($postId) {
        $attachments = get_attached_media('', $postId);
        foreach ($attachments as $attachment) {
            wp_delete_attachment( $attachment->ID, 'true' );
        }
    }
    
    function syncGalleries()
    {
        // Query to fetch all posts with specific flag (eg gSync_toSync and gSync_synced)
        // Get source url of post

        // Validate if gallery is already synced
    }

    static function downloadImagesToMediaLibrary($imageUrls): array
    {
        // Loop through each image tag and get the URL attribute
        foreach ($imageUrls as $url) {

            // Download the image to a temporary file
            $tmp = download_url($url);

            // Set up the file array for WordPress media library
            $file_array = array(
                'name' => basename($url),
                'tmp_name' => $tmp
            );

            // Insert the image into the media library
            $media_id = media_handle_sideload($file_array, $post_id);

            // If the media is successfully inserted, attach it to the post
            if (!is_wp_error($media_id)) {
                wp_update_post(array(
                    'ID' => $media_id,
                    'post_parent' => $post_id
                ));
            }

            // Delete the temporary file
            unlink($tmp);
        }


    }

    static function attachImagesToPost(int $parent_post, $images)
    {
        wp_update_post([
            'ID' => $value,
            'post_parent' => $parent_post
            //'menu_order'    =>  $order
        ]);
    }


    static function storeImageInMediaLibrary(ImageSource $imageSource, $post_id = 0)
    {

        // URL Validation
        if (!wp_http_validate_url($imageSource->src)) {
            return new WP_Error('invalid_url', 'File URL is invalid', array('status' => 400));
        }

        // Gives us access to the download_url() and media_handle_sideload() functions.
        if (!function_exists('download_url') || !function_exists('media_handle_sideload')) {
            require_once ABSPATH . 'wp-includes/pluggable.php';
            require_once ABSPATH . 'wp-admin/includes/image.php';
            require_once ABSPATH . 'wp-admin/includes/file.php';
            require_once ABSPATH . 'wp-admin/includes/media.php';
        }

        // Download file to temp dir.
        $temp_file = download_url($imageSource->src);

        if (is_wp_error($temp_file)) {
            @unlink($temp_file);
            return $temp_file;
        }

        // An array similar to that of a PHP `$_FILES` POST array
        $file_url_path = parse_url($imageSource->src, PHP_URL_PATH);
        $file_info = wp_check_filetype($file_url_path);
        $noExtension = false;

        // Fix image paths without extensions
        if (!$file_info['ext']) {
            $file_info = self::fixFileExtension($file_info, $temp_file);
            $noExtension = true;

            // mime_content_type($temp_file)
        }

        $file = array(
            'tmp_name' => $temp_file,
            //   'type' => $file_info['type'],
            'name' => basename($file_url_path) . ($noExtension ? '.' . $file_info['ext'] : ''),
            'size' => filesize($temp_file),
        );

        // Move the temporary file into the uploads directory.
        $attachment_id = media_handle_sideload($file, $post_id);
        update_post_meta($attachment_id, '_wp_attachment_image_alt', $imageSource->alt);

        @unlink($temp_file);
        return $attachment_id;
    }

    static function getPostsToScrapGallery($scrapeId)
    {
        $args = [
            'meta_query' => [
                'relation' => 'AND',
                [
                    'key' => 'gallery_scrapped_at',
                    'compare' => 'NOT EXISTS',
                ],
                [
                    'key' => '_scrape_original_url',
                    'compare' => 'EXISTS'
                ],
                [
                    'key' => '_scrape_task_id',
                    'value' => $scrapeId,
                    'compare' => '=',
                    'type' => 'NUMERIC',
                ]
            ],
            'orderby' => 'meta_value_num',
            //           'meta_key' => '_scrape_task_id',
            'orderby' => 'id',
            'order' => 'DESC',
            'post_status' => 'any',
            'posts_per_page' => -1,
            'post_type' => 'any',

        ];
        require_once ABSPATH . 'wp-includes/pluggable.php';
        $query = new WP_Query($args);

        return $query->get_posts();
    }

    static function getPostToScrapGallery($scrapeId, $postId)
    {
        $post = get_post($postId);
        $postScrapeId = get_post_meta($postId, '_scrape_task_id', true);

        if (!$postScrapeId || $postScrapeId != $scrapeId) {
            return [];
        }

        return $post;
    }


    static function getScraps()
    {
        $args = [
            'post_type' => ['scrape'],
            'posts_per_page' => -1
        ];
        require_once ABSPATH . 'wp-includes/pluggable.php';
        $query = new WP_Query($args);

        return $query->get_posts();
    }

    static function fixFileExtension($fileInfo, $tmpFile)
    {
        if (!$fileInfo['ext'] || !$tmpFile['type']) {
            $mime = mime_content_type($tmpFile);
            $fileInfo['ext'] = explode('/', $mime)[1];
            $tmpFile['type'] = $mime;
        }
        return $fileInfo;
    }
}