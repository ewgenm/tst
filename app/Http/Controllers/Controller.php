<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

/**
 * Базовый класс для всех контроллеров.
 */
abstract class Controller
{
    use AuthorizesRequests;
}
