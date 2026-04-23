<?php

namespace App\Enums;

enum RbacRole: string
{
    case ADMIN = 'admin';
    case STUDENT = 'student';
}
