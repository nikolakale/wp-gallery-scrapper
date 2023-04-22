<?php

class Scrapper
{
    private ScrapperSource $scrapeSourceConfig;
    
    public function __construct(ScrapperSource $scrapeSourceConfig)
    {
        $this->scrapeSourceConfig = $scrapeSourceConfig;
    }

    function getImages(string $pageUrl): array
    {
        // Load the HTML page
        $html = file_get_contents($pageUrl);

        // Create a new DOMDocument object and load the HTML
        $dom = new DOMDocument();
        @$dom->loadHTML($html);
        $xpath = new DOMXPath($dom);

        // Find the all image elements
        $imageElements = $xpath->query($this->scrapeSourceConfig->galleryContainerPath);
        
        $images = [];
        
        // Loop through each image tag and get the attribute by name
        foreach ($imageElements as $image) {
            $url = $image->getAttribute($this->scrapeSourceConfig->galleryImageSrcAttributeName);
            $alt = $image->getAttribute($this->scrapeSourceConfig->galleryImageAltAttributeName);

            if ($url) {
                // Check if the URL is a relative URL
                if (strpos($url, 'http') !== 0) {
                    // If it is a relative URL, append the base URL to it
                    $url = $pageUrl . '/' . $url;
                }

                $images[] = new ImageSource($url, $alt);
            }
        }
        
        return  $images;
    }

}