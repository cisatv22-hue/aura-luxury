<?php

return [
    'require_two_factor' => filter_var(env('ADMIN_REQUIRE_TWO_FACTOR', true), FILTER_VALIDATE_BOOLEAN),
];
