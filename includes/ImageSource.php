<?php

class ImageSource
{
    public string $src;

    public string $alt;
    
    public function __construct(string $src, string $alt)
    {
        $this->src = $src;
        $this->alt = $alt;
    }
}