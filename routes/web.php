<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UsuariosController;
use App\Http\Controllers\RecursosController;
use App\Http\Controllers\ProveedoresController;
use App\Http\Controllers\CreditosController;
use App\Http\Controllers\BancosController;
use App\Http\Controllers\TipoVinculacionesController;
use App\Http\Controllers\GeneralController;
use App\Http\Controllers\CreditosBancosController;
use Illuminate\Support\Facades\Artisan;
/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/test_sap/{linea?}', [CreditosController::class, 'test_sap']);

Route::get('/', [UsuariosController::class, 'login']);
Route::get('/login', [UsuariosController::class, 'login'])->name('login');
Route::get('/logout', [UsuariosController::class, 'logout'])->name('logout');
Route::post('/usuarios/ajaxLogin', [UsuariosController::class, 'ajaxLogin']);

//USUARIOS
Route::get('/form_usuarios/{tipo}', [UsuariosController::class, 'registrar']);

Route::get('/listado_recursos', function () {
    return view('recursos.listado_recurso');
});


Route::get('/form_usuarios', function () {
    return view('usuarios.form_usuarios');
});

Route::get('/listado_usuarios', function () {
    return view('usuarios.listado_usuarios');
});


Route::group(['prefix' => 'recursos'], function () {
    Route::get('/add', [RecursosController::class, 'add']);
});

/* PROVEEDORES */
Route::group(['prefix' => 'proveedores'], function () {
    Route::get('/add', [ProveedoresController::class, 'add']);
});


/* CREDITOS */
//Route::group(['middleware' => 'auth', 'prefix' => 'creditos'], function () {
Route::group(['middleware' => ['verify_csrf_token','auth'], 'prefix' => 'creditos'], function () {
//Route::group(['prefix' => 'creditos'], function () {
    Route::get('/buscar_linea_sap', [CreditosController::class, 'buscarLineaCreditoSap'])->name('buscar_linea_sap');
    Route::get('/listado_creditos', [CreditosController::class, 'index']);
    Route::get('/listado_desembolsos', [CreditosController::class, 'desembolsos']);
    Route::post('/ajaxGuardarCreditoSimuladoBanco', [CreditosController::class, 'ajaxGuardarCreditoSimuladoBanco']);
    Route::get('/ajaxVerificarDesembolso/{id}', [CreditosController::class, 'ajaxVerificarDesembolso']);
    Route::get('/ajaxObtenerInfoLineaSap/{numLinea}', [CreditosController::class, 'ajaxObtenerInfoLineaSap']);
    Route::get('/ajaxObtenerDetalleDesembolsoSap/{numDesembolso}', [CreditosController::class, 'ajaxObtenerDetalleDesembolsoSap']);

    //PETICIÓN AJAX PARA GUARDAR CREDITO # 2
    Route::post('/ajaxGuardarActualizarProyeCuotasLineaSap', [CreditosController::class, 'ajaxGuardarActualizarProyeCuotasLineaSap']);
    Route::post('/ajaxGuardarCreditoSimulado', [CreditosController::class, 'ajaxGuardarCreditoSimulado']);
    Route::put('/ajaxEditarCreditoSimuladoBanco/{id}', [CreditosController::class, 'ajaxEditarCreditoSimuladoBanco']);
    Route::delete('/ajaxBorrarCreditoSimuladoBanco/{id}', [CreditosController::class, 'ajaxBorrarCreditoSimuladoBanco']);
    Route::delete('/ajaxBorrarDesembolsosCreditoBanco/{id}', [CreditosController::class, 'ajaxBorrarDesembolsosCreditoBanco']);


    //PETICIÓN AJAX PARA EDITAR CREDITO # 2
    Route::put('/ajaxEditarCreditoSimulado/{id}', [CreditosController::class, 'ajaxEditarCreditoSimulado']);

    //OBTENER EL NÚMERO DE DESEMBOLSOS DE UN CREDITO
    Route::get('/ajaxObtenerNumDesembolsos/{id}', [CreditosController::class, 'ajaxObtenerNumDesembolsos']);

    //AGREGAR CREDITOS
    Route::get('/add', [CreditosController::class, 'add']);

    //EDITAR CREDITOS
    Route::get('/edit/{id}', [CreditosController::class, 'edit']);

    //BORRAR CREDITOS
    Route::delete('/ajaxBorrarCreditoSimulado/{id}', [CreditosController::class, 'ajaxBorrarCreditoSimulado']);

    //BORRAR DESEMBOLSO
    Route::delete('/ajaxBorrarDesembolsoSimulado/{id}', [CreditosController::class, 'ajaxBorrarDesembolsoSimulado']);


    //LISTADO CREDITOS REALES
    Route::match(
        ['get', 'post'],
        '/listadoCreditosReales',
        [CreditosController::class, 'listadoCreditosReales']
    )
        ->name('creditos_reales');

    //LISTADO CREDITOS SIMULADOS
    Route::match(
        ['get', 'post'],
        '/listadoCreditosSimulados',
        [CreditosController::class, 'listadoCreditosSimulados']
    )
        ->name('creditos_simulados');

    //REGISTRAR DESEMBOLSO
    Route::post('/ajaxGuardarDesembolsoSimulado', [CreditosController::class, 'ajaxGuardarDesembolsoSimulado']);

    //EDITAR DESEMBOLSO
    Route::put('/ajaxEditarDesembolsoSimulado', [CreditosController::class, 'ajaxEditarDesembolsoSimulado']);

    //AJAX OBTENER DESEMBOLSOS
    Route::get('/ajaxObtenerDesembolso/{creditoBancoId?}', [CreditosController::class, 'ajaxObtenerDesembolso']);

    //OBTENER POR EL ID DEL CREDITO
    Route::get('/desembolso/{creditoId}', [CreditosController::class, 'desembolso']);

    Route::get('/desembolsos/{creditoBancoId?}', [CreditosController::class, 'desembolsos']);

    Route::post('/ajaxGuardarActualizarCuotas', [CreditosController::class, 'ajaxGuardarActualizarCuotas']);

    Route::get('/ajaxGenerarExcelDesembolso/{id}', [CreditosController::class, 'ajaxGenerarExcelDesembolso']);
    Route::post('/ajaxGenerarExcelDesembolsoSap', [CreditosController::class, 'ajaxGenerarExcelDesembolsoSap']);

});

/* USUARIOS */
Route::group(['middleware' => ['verify_csrf_token','auth'], 'prefix' => 'usuarios'], function () {
    Route::get('/listadoRoles', [UsuariosController::class, 'listadoRoles'])->name('roles');
    Route::match(['get', 'post'], '/index', [UsuariosController::class, 'index']);
    Route::get('/add', [UsuariosController::class, 'add']);
    Route::get('/edit/{id}', [UsuariosController::class, 'edit'])->name('usuarios_edit');
    Route::get('/perfilUsuario/{id}', [UsuariosController::class, 'perfilUsuario'])->name('perfil_usuario');
    Route::post('/ajaxGuardarUsuario', [UsuariosController::class, 'ajaxGuardarUsuario']);
    Route::post('/ajaxGuardarRol', [UsuariosController::class, 'ajaxGuardarRol']);
    Route::patch('/ajaxEditarNombreRol/{id}', [UsuariosController::class, 'ajaxEditarNombreRol']);
    Route::delete('/ajaxBorrarRol/{id}', [UsuariosController::class, 'ajaxBorrarRol']);
    Route::get('/ajaxCargarRoles', [UsuariosController::class, 'ajaxCargarRoles']);
    Route::get('/ajaxObtenerRolPermisos/{id}', [UsuariosController::class, 'ajaxObtenerRolPermisos']);
    Route::post('/ajaxActualizarRolPermisos', [UsuariosController::class, 'ajaxActualizarRolPermisos']);
    Route::delete('/ajaxBorrarUsuario/{id}', [UsuariosController::class, 'ajaxBorrarUsuario']);
    Route::match(['put', 'patch'], '/ajaxEditarUsuario/{id}', [UsuariosController::class, 'ajaxEditarUsuario']);
    Route::match(['put', 'patch'], '/ajaxEditarPerfil/{id}', [UsuariosController::class, 'ajaxEditarPerfil']);
});


/* BANCOS */
Route::group(['middleware' =>  ['verify_csrf_token','auth'], 'prefix' => 'bancos'], function () {
    // Route::get('/index', [BancosController::class, 'index'])->name('bancos_index');
    Route::match(['get', 'post'], '/index', [BancosController::class, 'index'])->name('bancos_index');
    Route::post('/ajaxGuardarBanco', [BancosController::class, 'ajaxGuardarBanco']);
    Route::match(['put', 'patch'], '/ajaxEditarBanco/{id}', [BancosController::class, 'ajaxEditarBanco']);
    Route::match(['delete'], '/ajaxBorrarBanco/{id}', [BancosController::class, 'ajaxBorrarBanco']);
});

/* TIPO VINCULACIONES */
Route::group(['middleware' =>  ['verify_csrf_token','auth'], 'prefix' => 'tipo_vinculaciones'], function () {
    Route::match(['get', 'post'], '/index', [TipoVinculacionesController::class, 'index'])->name('tipo_vinculaciones_index');
    Route::post('/ajaxGuardarRegistro', [TipoVinculacionesController::class, 'ajaxGuardarRegistro']);
    Route::match(['put', 'patch'], '/ajaxEditarRegistro/{id}', [TipoVinculacionesController::class, 'ajaxEditarRegistro']);
    Route::match(['delete'], '/ajaxBorrarRegistro/{id}', [TipoVinculacionesController::class, 'ajaxBorrarRegistro']);
});

/* GENERAL */
Route::group(['middleware' =>  ['verify_csrf_token','auth'], 'prefix' => 'general'], function () {
    Route::get('/ajaxCargarRegistros/{tabla}', [GeneralController::class, 'ajaxCargarRegistros']);
    Route::get('/ajaxCargarRoles', [GeneralController::class, 'ajaxCargarRoles']);
});


Route::get('/clear_cache', function () {
    Artisan::call('cache:clear');
    Artisan::call('route:clear');
    Artisan::call('view:clear');
    Artisan::call('config:clear');
    return "Cache is cleared";
});

/* EMPRESTITOS */
Route::group(['prefix' => 'creditosbancos', 'middleware' =>  ['verify_csrf_token','auth']], function () {
    Route::get('/ajaxCargarCreditosBancosPorCreditoId/{creditoId}', [CreditosBancosController::class, 'ajaxCargarCreditosBancosPorCreditoId']);
});
