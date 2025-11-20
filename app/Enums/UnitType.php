<?php

namespace App\Enums;

enum UnitType: int
{
    case BERKAS = 1;
    case DATA = 2;
    case DOKUMEN = 3;
    case KALI = 4;
    case KEGIATAN = 5;
    case LAPORAN = 6;
    case VOLUME = 7;
    case MODUL = 8;
    case JAM = 9;
}