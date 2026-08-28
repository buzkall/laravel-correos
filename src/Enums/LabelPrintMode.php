<?php

declare(strict_types=1);

namespace SmartDato\CorreosShipping\Enums;

enum LabelPrintMode: int
{
    case A4 = 1;
    case Labeler = 2;
}
