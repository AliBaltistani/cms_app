<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

/**
 * Base Controller Class
 * 
 * Provides common functionality for all application controllers
 * Includes middleware support, request validation, and authorization
 * 
 * @package     Laravel CMS App
 * @subpackage  Controllers
 * @category    Base Controller
 * @author      Go Globe CMS Team
 * @since       1.0.0
 */
abstract class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;
}
