<?php

use Illuminate\Routing\Router;

Admin::registerHelpersRoutes();

Route::group([
    'prefix'        => config('admin.prefix'),
    'namespace'     => Admin::controllerNamespace(),
    'middleware'    => ['web', 'admin'],
], function (Router $router) {
    $router->get('/', function() {
        if (Admin::user()->isRole('administrator')) {
            return redirect('/point');
        }
        return redirect('/delivery-waste');
    })->name('index');
    $router->resource('city', CityController::class);
    $router->resource('waste', WasteController::class);
    $router->resource('point', PointController::class);
    $router->resource('city-user', CityUserController::class);
    $router->resource('delivery-waste', DeliveryWasteController::class);
    $router->resource('price', PriceController::class);
    $router->get('delivery-waste/paid/{id}', 'DeliveryWasteController@paid')->name('paid');
    $router->post('price/group-edit', 'PriceController@groupEdit');
});

