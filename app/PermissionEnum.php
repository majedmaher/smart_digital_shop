<?php

namespace App;

enum PermissionEnum: string
{
    case MANAGE_USERS = 'manage users';
    case MANAGE_SETTINGS = 'manage settings';
    case MANAGE_CATEGORIES = 'manage categories';
    case MANAGE_SUB_CATEGORIES = 'manage subcategories';
    case MANAGE_PRODUCTS = 'manage products';
    case MANAGE_CODES = 'manage codes';
    case MANAGE_COUPONS = 'manage coupons';
    case MANAGE_SLIDERS = 'manage sliders';
    case MANAGE_ORDERS = 'manage orders';
    case MANAGE_RATINGS = 'manage ratings';
    case REPLY_TO_TICKETS = 'reply tickets';

    public static function all(): array
    {
        return array_column(self::cases(), 'value');
    }
}
