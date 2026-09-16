<?php

namespace App\Permissions;

class PermissionList
{
    public const PROMOTE_TO_COADMIN = 'promote-co-admin';
    public const DEMOTE_TO_USER = 'demote-to-user';
    public const MANAGE_COMMENTS = 'manage-comments';
    public const MANAGE_PRODUCTS = 'manage-products';
    public const VOTE_ON_COMMENT = 'vote-comment';
    public const VIEW_ADMIN_DASHBOARD = 'view-admin-dashboard';
    // Add more permissions here as needed

    /**
     * Optionally, you can return all permissions as an array
     */
    public static function all()
    {
        return [
            self::PROMOTE_TO_COADMIN,
            self::DEMOTE_TO_USER,
            self::MANAGE_COMMENTS,
            self::MANAGE_PRODUCTS,
            self::VOTE_ON_COMMENT,
            self::VIEW_ADMIN_DASHBOARD,
            // Add others here if you like
        ];
    }
}
