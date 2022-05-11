<?php

if (! function_exists('check_exists')) {
    function check_exists($variable)
    {
        return !empty($variable) && isset($variable) && !is_null($variable);
    }
}


if (! function_exists('generate_random_strings')) {
    function generate_random_strings($strength = 3): string
    {
        $input = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $input_length = strlen($input);
        $random_string = '';
        for ($i = 0; $i < $strength; $i++) {
            $random_character = $input[mt_rand(0, $input_length - 1)];
            $random_string .= $random_character;
        }
 
        return $random_string;
    }
}