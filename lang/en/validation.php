<?php

return [
    'required' => 'The :attribute field is required.',
    'array' => 'The :attribute must be a array.',
    'string' => 'The :attribute must be a string.',
    'max' => [
        'string' => 'The :attribute may not be greater than :max characters.',
    ],
    'email' => 'The :attribute must be a valid email address.',
    'unique' => 'The :attribute has already been taken.',
    'min' => [
        'string' => 'The :attribute must be at least :min characters.',
    ],
    'confirmed' => 'The :attribute confirmation does not match.',

    'attributes' => [
        'name' => 'name',
        'email' => 'email address',
        'password' => 'password',
    ],
];
