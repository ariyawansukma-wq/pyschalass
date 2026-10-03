<?php

namespace App\Enums;

enum UserRole: string
{
    case Admin   = 'admin';
    case Officer = 'officer';
    case Kader   = 'kader';
}
