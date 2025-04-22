<?php
// Temporary mock version to test frontend rendering

header('Content-Type: application/json');

echo json_encode([
    [
        "id" => 1,
        "name" => "Heating Systems",
        "filters" => [
            [
                "id" => 101,
                "name" => "Fuel Type",
                "options" => [
                    ["id" => 1001, "value" => "Natural Gas"],
                    ["id" => 1002, "value" => "Propane"],
                    ["id" => 1003, "value" => "Electric"]
                ]
            ],
            [
                "id" => 102,
                "name" => "Brand",
                "options" => [
                    ["id" => 1004, "value" => "Goodman"],
                    ["id" => 1005, "value" => "Carrier"]
                ]
            ]
        ]
    ],
    [
        "id" => 2,
        "name" => "Air Conditioners",
        "filters" => [
            [
                "id" => 103,
                "name" => "Cooling Capacity",
                "options" => [
                    ["id" => 1006, "value" => "1 Ton"],
                    ["id" => 1007, "value" => "2 Ton"]
                ]
            ]
        ]
    ]
]);
