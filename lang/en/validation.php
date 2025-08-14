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
        'integer' => 'The :attribute must be at least :min item.',
    ],
    'confirmed' => 'The :attribute confirmation does not match.',
    'exists' => 'The :attribute does not exist.',
    'before_or_equal' => 'The start date must be a date before or equal to today and before or equal to end date.',
    'after_or_equal' => 'The end date must be a date before or equal to today and after or equal to start date.',

    'attributes' => [
        'name' => 'name',
        'cart' => 'Cart',
        'quantity' => 'Quantity',
        'shipping_data' => 'Shipping Data',
        'coupon_code' => 'Coupon Code',
        'email' => 'email address',
        'password' => 'password',
    ],
];
