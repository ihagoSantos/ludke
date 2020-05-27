<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Dompdf\Dompdf;
use App\ItensPedido;
use App\Pedido;
use App\Cliente;
use App\User;

class RelatorioPedidosController extends Controller
{
    //

    public function RelatorioPedidos($id){
        $view = 'relatorioPedido';
        $pedido = Pedido::find($id);
        $itens = ItensPedido::where('pedido_id', '=', $pedido->id)->get();
        $clientes = Cliente::where('id', '=', $pedido->cliente_id)->get();
        $soma = 0;
        foreach ($itens as $iten){
            $soma += $iten->valorReal;
        }

        #####Soma



        $date = date('d/m/Y');
		$view = \View::make($view, compact('itens', 'clientes','soma',  'date'))->render();
		$pdf = \App::make('dompdf.wrapper');
		$pdf->loadHTML($view)->setPaper('a6', 'landscape');

		$filename = 'relatorioPedido'.$date;


		return $pdf->stream($filename.'.pdf');
    }



    public function RelatorioGeral(Request $request){
        // Set variável filtro
        $filtro = $request->all();
        // filtra os pedidos
        $pedidos = self::filtrarPedido($filtro);
        
        $view = 'relatorioGeralPedido';
        // $pedidos = Pedido::all();
        $total = 0;
        foreach ($pedidos as $pedido){
            $total += $pedido->valorTotal;
        }
        $count = count($pedidos);

        //dd($total);


        $date = date('d/m/Y');
        $view = \View::make($view, compact('pedidos', 'total','count','date'))->render();
        $pdf = \App::make('dompdf.wrapper');
        $pdf->loadHTML($view)->setPaper('a4', 'landscape');

        $filename = 'relatorioGeralPedido'.$date;


        return $pdf->stream($filename.'.pdf');
    }


    // Filtra Pedido
    public function filtrarPedido($filtro){
        
        $pedidos = [];
        if(isset($filtro['status_id'])){
            $pedidos = Pedido::where('status_id',intval($filtro['status_id']))
                ->orderBy('status_id')->orderBy('dataEntrega')->get();
            return $pedidos;
        }
        else if(isset($filtro['cliente'])){
            $user = User::where('name','LIKE','%'.strtoupper($filtro['cliente']).'%')->first();
            if(isset($user)){
                $cliente = Cliente::where('user_id',$user->id)->first();
                $pedidos = Pedido::where('cliente_id',$cliente->id)
                    ->orderBy('status_id')->orderBy('dataEntrega')->get();
                    return $pedidos;
            }else{
                return view('listarPedido',['pedidos'=>[],'filtro'=>$filtro,'achou'=> true,'tipoFiltro'=>"Nome do Cliente"]);
            }
        }
        else if(isset($filtro['nomeReduzido'])){

            $cliente = Cliente::where('nomeReduzido','LIKE','%'.strtoupper($filtro['nomeReduzido']).'%')->first();
            if(isset($cliente)){
                $pedidos = Pedido::where('cliente_id',$cliente->id)
                    ->orderBy('status_id')->orderBy('dataEntrega')->get();
                return $pedidos;
            }
            else{
                return $pedidos;
            }
        }
        else if(isset($filtro['dataEntregaInicial']) && !isset($filtro['dataEntregaFinal'])){
            $pedidos = Pedido::whereDate('dataEntrega','>=',$filtro['dataEntregaInicial'])
                ->orderBy('status_id')->orderBy('dataEntrega')->get();
                return $pedidos;
        }
        else if(!isset($filtro['dataEntregaInicial']) && isset($filtro['dataEntregaFinal'])){
            $pedidos = Pedido::whereDate('dataEntrega','<=',$filtro['dataEntregaFinal'])
                ->orderBy('status_id')->orderBy('dataEntrega')->get();
                return $pedidos;
        }
        else if(isset($filtro['dataEntregaInicial']) && isset($filtro['dataEntregaFinal'])){
            $pedidos = Pedido::whereDate('dataEntrega','>=',$filtro['dataEntregaInicial'])
                ->whereDate('dataEntrega','<=',$filtro['dataEntregaFinal'])
                ->orderBy('status_id')->orderBy('dataEntrega')->get();
                return $pedidos;
        }
        else{
            $pedidos = Pedido::all();
            return $pedidos;
        }

    }
}
