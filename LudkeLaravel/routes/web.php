<?php

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

Route::get('/', function () {
    return view('auth.login');
});

Auth::routes();

Route::get('/home', 'HomeController@index')->name('home');

Auth::routes();

Route::get('/home', 'HomeController@index')->name('home');

// chama a view de produtos
Route::get('/produtos','ProdutoController@indexView')->name('produtos');
// Route::post('produtos','ProdutoController@store')->name('produto.store');


// View Categorias
Route::get('/categorias','CategoriaController@indexView')->name('categorias');

// View Funcionários
Route::get('/funcionarios','FuncionarioController@indexView')->name('funcionarios');

Route::get('/clientes','ClienteController@indexView')->name('clientes');

Route::get('/cargos', 'CargoController@indexView')->name('cargos');

Route::get('/pedidos','PedidoController@index')->name('pedidos');

// ROTAS PARA O PEDIDO
Route::post('/pedidos/getCliente/{cpfCnpj}','PedidoController@getCliente');

// Rotas para testar banco
use App\Produto;
use App\Categoria;
use App\Cliente;
use App\User;
Route::get('/teste',function(){
    $users = User::with('cliente')->get();
    $produtos = Produto::with('categoria')->get();
    $categoria = Categoria::with('produtos')->get();

    return $users[1]->cliente->nomeReduzido;
});