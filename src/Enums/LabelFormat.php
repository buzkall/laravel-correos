<?php

declare(strict_types=1);

namespace SmartDato\CorreosShipping\Enums;

enum LabelFormat: int
{
    case XML = 1;
    case PDF = 2;
    case ZPL = 3;
}
