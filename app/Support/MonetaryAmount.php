<?php

namespace App\Support;

final class MonetaryAmount
{
    public const SCALE = 2;

    public const PRECISION = 15;

    /** Maximum storable value for DECIMAL(15, 2). */
    public const MAX = 9_999_999_999_999.99;
}
