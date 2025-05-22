<?php

use Wotz\SwaggerUi\Http\Middleware\EnsureUserIsAuthorized;

return [
    /*
    |--------------------------------------------------------------------------
    | Swagger UI Path
    |--------------------------------------------------------------------------
    |
    | This is the URI path where Swagger UI will be accessible from. Feel free
    | to change this path to anything you like.
    |
    */

    'path' => 'swagger',

    /*
    |--------------------------------------------------------------------------
    | Swagger UI Versions
    |--------------------------------------------------------------------------
    |
    | Here you may specify the versions of your API that are available in the
    | Swagger UI. The key is the version name and the value is the path to the
    | OpenAPI specification file.
    |
    */

    'versions' => [
        'v1' => resource_path('swagger/openapi.json'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Modify OpenAPI File
    |--------------------------------------------------------------------------
    |
    | If this is set to true, the package will modify the OpenAPI file to set
    | the server url to the current application url and inject oauth urls.
    |
    */

    'modify_file' => false,

    /*
    |--------------------------------------------------------------------------
    | OAuth Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may specify the OAuth configuration for your API. This will be
    | used to prefill the authentication view in Swagger UI.
    |
    */

    'oauth' => [
        'token_path' => 'oauth/token',
        'refresh_path' => 'oauth/token',
        'authorization_path' => 'oauth/authorize',

        'client_id' => env('SWAGGER_UI_OAUTH_CLIENT_ID'),
        'client_secret' => env('SWAGGER_UI_OAUTH_CLIENT_SECRET'),
    ],

    /*
     * The middleware that is applied to the route.
     */
    'middleware' => [
        'web',
        // EnsureUserIsAuthorized::class, // Commented out for development
    ],

    /*
     * Specify the validator URL. Set to false to disable validation.
     */
    'validator_url' => env('SWAGGER_UI_VALIDATOR_URL'),

    /*
     * The title of the page where the swagger file is served.
     */
    'title' => env('APP_NAME') . ' - Swagger',

    /*
     * The default version that is loaded when the route is accessed.
     */
    'default' => 'v1',

    /*
     * Path to a custom stylesheet file if you want to customize the look and feel of swagger-ui.
     * The content of the file will be read and added into a style-tag on the swagger-ui page.
     */
    'stylesheet' => null,

    /*
     * The server URL configuration for the swagger file.
     */
    'server_url' => env('APP_URL', 'http://localhost:8000'),
];
