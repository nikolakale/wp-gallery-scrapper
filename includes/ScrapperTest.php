<?php

include 'Scrapper.php';
include 'ScrapperSource.php';

$scrapperSource = new ScrapperSource();
$scrapperSource->galleryContainerPath = '//*[@id="ic-photoGallery"]/div/a'; 
$scrapperSource->galleryImageSrcAttributeName = "href";

$scrapper =  new Scrapper($scrapperSource);

$images = $scrapper->getImageUrls("https://www.vogueproperties.com/en/property/id/255681-Mallorca-house-Alcudia.html");

print_r($images);