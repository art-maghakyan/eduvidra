<?php

namespace App\Enums;

enum RbacPermission: string
{
    case ADMIN_ACCESS = 'admin.access';
    case USERS_MANAGE = 'users.manage';
}
