<?php
return [
    "user" => [
        ["GET", "/", 'index'],
        ["GET", "/[:id]", 'show'],
        ["POST", "/", 'create'],
        ["PUT", "/[:id]", "update"],
        ["DELETE", "/[:id]", "delete"],
        ["POST", "/login", "login"],
        ["POST", "/deposit", "deposit"],
        ['GET', "/reset", "reset"]
    ],
    "product" => [
        ["GET", "/", 'index'],
        ["GET", "/[:id]", 'show'],
        ["POST", "/", 'create'],
        ["PUT", "/[:id]", "update"],
        ["DELETE", "/[:id]", "delete"]
    ],
];