<?php

declare(strict_types=1);

namespace App\VoyageAi;

enum InputType: string
{
    case None = 'None';
    case Query = 'query';
    case Document = 'document';
}
