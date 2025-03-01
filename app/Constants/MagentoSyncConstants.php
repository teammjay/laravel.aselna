<?php

namespace App\Constants;

class MagentoSyncConstants
{
    public static $neccesaryAttributes = [
        'brands' => [
            "default_frontend_label" => "Brands",
            "frontend_input" => "multiselect"
        ],
        'devices' => [
            "default_frontend_label" => "Devices",
            "frontend_input" => "multiselect"
        ],
        'bullet_points' => [
            "default_frontend_label" => "Bullet Points",
            "frontend_input" => "textarea"
        ],
        'width' => [
            "default_frontend_label" => "Width",
            "frontend_input" => "text"
        ],
        'height' => [
            "default_frontend_label" => "Height",
            "frontend_input" => "text"
        ],
        'depth' => [
            "default_frontend_label" => "Depth",
            "frontend_input" => "text"
        ],
        'screen_length' => [
            "default_frontend_label" => "Screen Length",
            "frontend_input" => "text"
        ],
        'case_size' => [
            "default_frontend_label" => "Case Size",
            "frontend_input" => "textarea"
        ]
    ];
}
