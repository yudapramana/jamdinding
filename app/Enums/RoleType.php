<?php

namespace App\Enums;

enum RoleType: int
{
    case SUPERADMIN = 1;
    case ADMIN_EVENT = 2;
    case PENDAFTARAN = 3;
    case VERIFIKATOR = 4;
    case DEWAN_HAKIM = 5;
    case PANITERA = 6;
    case USER = 7;
}