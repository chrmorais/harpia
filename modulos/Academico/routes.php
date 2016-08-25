<?php

Route::group(['prefix' => 'academico', 'middleware' => ['auth']], function () {
    Route::controllers([
        'index' => '\Modulos\Academico\Http\Controllers\indexController',
        'polos' => '\Modulos\Academico\Http\Controllers\PolosController',
        'departamentos' => '\Modulos\Academico\Http\Controllers\DepartamentosController',
        'periodosletivos' => '\Modulos\Academico\Http\Controllers\PeriodosLetivosController',
        'cursos' => '\Modulos\Academico\Http\Controllers\CursosController',
        'centros' => '\Modulos\Academico\Http\Controllers\CentrosController',
    ]);
});
