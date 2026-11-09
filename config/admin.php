<?php

return [
    'require_two_factor' => filter_var(
        env('ADMIN_REQUIRE_TWO_FACTOR', env('APP_ENV', 'production') === 'production'),
        FILTER_VALIDATE_BOOLEAN,
    ),
];
