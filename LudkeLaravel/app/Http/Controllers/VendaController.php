<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Validator;
use App\Pedido;
use App\Produto;
use App\Funcionario;
use App\Cliente;
use App\ItensPedido;
use App\User;
use App\Status;
use App\Pagamento;
use App\Cargo;
use App\FormaPagamento;

class VendaController extends Controller
{
    public function index(){
        return view('venda');
    }

    public function indexListarVendas()
    {
        $pedidos = Pedido::with(['status','pagamento'])->
                            where('tipo','v')->
                            where('status_id',2)-> //PESADO
                            orwhere('status_id',3)-> //ENTREGUE
                            orderby('created_at','DESC')->
                            orderby('status_id')->
                            paginate(25);

        return view('listarVendas',['pedidos'=>$pedidos]);
    }

    /**
     * Função que redireciona para tela de pagamento da venda
     */
    public function indexPagamento($id){
        // Pedido
        $pedido = Pedido::with(['cliente'])->find($id);

        // Itens do Pedido
        $itensPedido = ItensPedido::where('pedido_id',$pedido->id)->get();

        $valorTotalDoPagamento = 0;
        $valorDoDesconto = 0;

        foreach ($itensPedido as $item) {
            $valorTotalDoPagamento += floatval($item->valorComDesconto);
            $valorDoDesconto += floatval($item->valorReal) - floatval($item->valorComDesconto);
        }
        // dd($valorTotalDoPagamento,$valorDoDesconto);
        //------------DEBUG--------------------------
        // dd($pedido,$itensPedido, $valorTotalDoPagamento,$valorDoDesconto);
        $entregador_id = Cargo::where('nome','ENTREGADOR')->pluck('id')->first();
        $entregadores = Funcionario::with(['user'])->where('id',$pedido->funcionario_id)->
                                        orwhere('cargo_id',$entregador_id)->get();
        $formasPagamento = FormaPagamento::all();
        return view('pagamentoVenda',
            [
                'pedido'=>$pedido,
                'valorTotalDoPagamento'=>$valorTotalDoPagamento,
                'valorDoDesconto'=>$valorDoDesconto,
                'entregadores'=>$entregadores,
                'formasPagamento'=>$formasPagamento,
            ]);
    }
    /**
     * Função que redireciona para tela de registrar entrega do pedido
     * @param Integer $id
     * @return View registrarEntregaPedido
     */
    public function indexRegistrarEntregaPedido($id){
        // dd($id);
        // Pedido
        $pedido = Pedido::with(['cliente'])->find($id);

        // Itens do Pedido
        $itensPedido = ItensPedido::where('pedido_id',$pedido->id)->get();

        $valorTotalDoPagamento = 0;
        $valorDoDesconto = 0;

        foreach ($itensPedido as $item) {
            $valorTotalDoPagamento += floatval($item->valorReal);
            if(isset($item->valorComDesconto)){
                $valorDoDesconto += floatval($item->valorReal) - floatval($item->valorComDesconto);
            }

        }

        //------------DEBUG--------------------------
        // dd($pedido,$itensPedido, $valorTotalDoPagamento,$valorDoDesconto);
        $entregador_id = Cargo::where('nome','ENTREGADOR')->pluck('id')->first();
       /* $entregadores = Funcionario::with(['user'])->where('id',$pedido->funcionario_id)->
                                        orwhere('cargo_id',$entregador_id)->get();
       */
        $entregadores = Funcionario::all();
        return view('registrarEntregaPedido',
        [
            'pedido'=>$pedido,
            'valorTotalDoPagamento'=>$valorTotalDoPagamento,
            'valorDoDesconto'=>$valorDoDesconto,
            'entregadores'=>$entregadores,
        ]);
    }
    /**
     * Função que registra a entrega do pedido informando o funcionario que entregou
     * @param Integer $id
     * @return View listarVendas
     */
    public function registrarEntregaPedido(Request $request){
        // dd($request->all());
        $pedido = Pedido::find($request['pedido_id']);
        $pedido->entregador_id = $request['entregador_id'];
        $pedido->dataEntrega = $request['dataEntrega'];
        $status_id = Status::where('status','ENTREGUE')->pluck('id')->first();
        $pedido->status_id = $status_id;

        $pedido->save();
        return redirect()->route('listarVendas');
    }
    /**
     * Função para salvar venda realizada
     * @param Request
     * @return pedido_id
     */
    function finalizarVenda(Request $request){
        // dd($request->all());
        $cliente = Cliente::find($request->input('cliente_id'));


        // valor total sem desconto
        $valorTotal = 0;
        $desconto = 0;
        foreach($request->input('listaProdutos') as $item){
            $produto = Produto::find($item[0]['produto_id']);
            if(isset($produto)){
                $valorTotal += $produto->preco * $item[0]['peso'];
            }
        }
        $pedido = new Pedido();

        //Tipo do Pedido
        $pedido->tipo = 'v';

        $status = Status::where('status','PESADO')->first(); // Solicitado
        $pedido->status_id = $status->id;
        // valcula o desconto no valor total
        $pedido->valorTotal = $valorTotal;


        // $pedido->desconto = floatval($request->input('valorDesconto'));
        $pedido->dataEntrega = $request->input('dataEntrega');

        $pedido->cliente_id = $cliente->id;
        $funcionario = Funcionario::find(Auth::user()->id);
        $pedido->funcionario_id = $funcionario->id; //salvando o user_id do funcionario que está logado

        // dd($pedido);
        $pedido->save(); // salva o pedido
        // dd($pedido);

        foreach($request->input('listaProdutos') as $item){

            $itemPedido = new ItensPedido();
            $produto = Produto::find($item[0]['produto_id']);
            if(isset($produto)){
                $itemPedido->pesoSolicitado = $item[0]['peso'];
                $itemPedido->pesoFinal = $item[0]['peso'];
                $itemPedido->valorReal = $produto->preco * $item[0]['peso'];
                $itemPedido->nomeProduto = $produto->nome;
                $itemPedido->produto_id = $produto->id;
                $itemPedido->pedido_id = $pedido->id;

                $itemPedido->save();
            }
        }

        return $pedido->id;
    }

    /**
     * Função busca os dados referente ao pedido e retorna para tela concluirVenda onde será possível aplicar os descontos
     * em cada item da venda
     * @param $id
     * @return view concluirVenda
     */
    public function concluirVenda($id){
        // dd($id);
        $pedido = Pedido::with(['itensPedidos'])->find($id);
        // dd($pedido);
        if(isset($pedido)){
            for($i = 0; $i< count($pedido->itensPedidos); $i++){
                $produto = Produto::find($pedido->itensPedidos[$i]->produto_id);
                $pedido->itensPedidos[$i]["precoProduto"] = $produto->preco;
            }
            $cliente = Cliente::with('user')->find($pedido->cliente_id);
            $funcionario = Funcionario::with('user')->find($pedido->funcionario_id);

            // $pedido["valorProduto"]= $produto->preco;
            $pedido["nomeCliente"] = $cliente->user->name;
            $pedido["nomeFuncionario"] = $funcionario->user->name;
            // $pedido["dataEntrega"] = new DateTime($pedido->dataEntrega);

            return view('concluirVenda')->with(["pedido"=>$pedido]);
        }
    }

    /**
     * Salva os dados dos descontos referente aos itens e salva informações do pedido
     * @param Request
     * @return view pagamentos
     */
    public function concluirVendaComDescontoNosItens(Request $request){
        // dd($request->all());
        // pedido
        $pedido = Pedido::find($request['pedido_id']);
        // array com a porcentagem dos descontos referente aos itens
        $descontos = $request['desconto'];
        // array com os itens do pedido
        $itensPedido = ItensPedido::where('pedido_id',$request['pedido_id'])->get();
        // array contendo os preços com os descontos calculados
        $itensComDesconto = $this->itensComDesconto($itensPedido, $descontos);

        // ------------------------ DEBUG ------------------------
        // dd($request->all(),$pedido->status->status,$itensPedido,$itensComDesconto);

        /**
         * Percorre $itensPedido, $descontos e $itensComDesconto
         * e salva o descontoPorcentagem e valorComDesconto
         */
        if(count($itensPedido) === count($itensComDesconto)){
            for($i = 0; $i <= count($itensPedido) - 1; $i++ ){
                $itensPedido[$i]->descontoPorcentagem = $descontos[$i];
                $itensPedido[$i]->valorComDesconto = $itensComDesconto[$i];
                $itensPedido[$i]->save();
            }
        }
        $pedido->save();

        // return view('pagamento',['pedido'=>$pedido]);

        return redirect('/vendas/pagamento/'.$pedido->id);

    }
    /**
     * Função que calcula os descontos nos itens e retorna um array com o preço dos itens após
     * o desconto ser aplicado
     * @param Array $itens
     * @param Array $descontos
     * @return Array $itensComDesconto
     */
    function itensComDesconto($itens, $descontos){
        $itensComDesconto = [];
        for($i = 0; $i < count($itens); $i++){
            $itensComDesconto[$i] = floatval($itens[$i]->valorReal) - (floatval($itens[$i]->valorReal) * ($descontos[$i] / 100));
        }
        return $itensComDesconto;
    }

    /**
     * Função que calcula o desconto aplicado em cada forma de pagamento
     *
     * @param $valorTotalPagamento
     * @param $descontoPagamento
     * @return $valorPago
     */
    function descontoFormaPagamento($valorTotalPagamento, $descontoPagamento){
        $valorPago = 0.0;
        $valorPago = floatval($valorTotalPagamento) - (floatval($valorTotalPagamento) * ($descontoPagamento / 100));
        return $valorPago;
    }
    /**
     * Função que registra o pagamento efetuado na venda
     * @param Request
     * @return void
     */
    public function pagamento(Request $request){
        $pedido = Pedido::find($request['pedido_id']);

        // -------------DEBUG----------------
        // dd($request->all(),$pedido,Auth::user()->funcionario->id);

        $pedido->entregador_id = $request['entregador_id'];
        $status = Status::where('status','ENTREGUE')->first(); // Solicitado
        // dd($status->id);
        $pedido->status_id = $status->id;

        $pedido->save();

        //Salva cada forma de pagamento
        for($i = 0; $i < count($request['formaPagamento']); $i++){
            $pagamento = new Pagamento();
            $pagamento->dataVencimento = $request['dataVencimento'][$i];
            $pagamento->dataPagamento = $request['dataPagamento'][$i];
            $pagamento->obs = $request['obs'][$i];
            $pagamento->descontoPagamento = floatval($request['descontoPagamento'][$i]);//porcentagem do pagamento
            $pagamento->valorTotalPagamento = floatval($request['valorTotalPagamento'][$i]);//valor sem desconto aplicado
            $pagamento->valorPago = self::descontoFormaPagamento(floatval($request['valorTotalPagamento'][$i]),floatval($request['descontoPagamento'][$i])); // valor com desconto aplicado
            $pagamento->formaPagamento_id = $request['formaPagamento'][$i];

            $pagamento->funcionario_id = Auth::user()->funcionario->id;
            $pagamento->pedido_id = $pedido->id;
            $pagamento->save();
        }

        return redirect()->route('listarVendas');

    }

    // Filtra Pedido
    public function filtrarVenda(Request $request, Pedido $pedido){
        // dd($request->all());
        $filtro = $request->all();
        // dd($filtro);
        if(isset($filtro['status_id'])){
            $pedidos = Pedido::where('tipo','v')->where('status_id',intval($filtro['status_id']))
                ->orderBy('status_id')->orderBy('dataEntrega')->paginate(25);
            return view('listarVendas',['pedidos'=>$pedidos,'filtro'=>$filtro,'achou'=> true,'tipoFiltro'=>"Status"]);
        }
        else if(isset($filtro['cliente'])){
            $user = User::where('name','LIKE','%'.strtoupper($filtro['cliente']).'%')->first();
            if(isset($user)){
                $cliente = Cliente::where('user_id',$user->id)->first();
                $pedidos = Pedido::where('cliente_id',$cliente->id)->where('tipo','v')
                    ->orderBy('status_id')->orderBy('dataEntrega')->paginate(25);
                return view('listarVendas',['pedidos'=>$pedidos,'filtro'=>$filtro,'achou'=> true,'tipoFiltro'=>"Nome do Cliente"]);
            }else{
                return view('listarVendas',['pedidos'=>[],'filtro'=>$filtro,'achou'=> true,'tipoFiltro'=>"Nome do Cliente"]);
            }
        }
        else if(isset($filtro['nomeReduzido'])){

            $cliente = Cliente::where('nomeReduzido','LIKE','%'.strtoupper($filtro['nomeReduzido']).'%')->first();
            if(isset($cliente)){
                $pedidos = Pedido::where('cliente_id',$cliente->id)->where('tipo','v')
                    ->orderBy('status_id')->orderBy('dataEntrega')->paginate(25);
                return view('listarVendas',['pedidos'=>$pedidos,'filtro'=>$filtro,'achou'=> true,'tipoFiltro'=>"Nome Reduzido"]);
            }
            else{
                return view('listarVendas',['pedidos'=>[],'filtro'=>$filtro,'achou'=> true,'tipoFiltro'=>"Nome Reduzido"]);
            }
        }
        else if(isset($filtro['dataEntregaInicial']) && !isset($filtro['dataEntregaFinal'])){
            $pedidos = Pedido::where('tipo','v')->whereDate('dataEntrega','>=',$filtro['dataEntregaInicial'])
                ->orderBy('status_id')->orderBy('dataEntrega')->paginate(25);
            return view('listarVendas',['pedidos'=>$pedidos,'filtro'=>$filtro,'achou'=> true,'tipoFiltro'=>"Data Entrega Maior ou Igual à: ".date('d/m/Y',strtotime($filtro['dataEntregaInicial']))]);
        }
        else if(!isset($filtro['dataEntregaInicial']) && isset($filtro['dataEntregaFinal'])){
            $pedidos = Pedido::where('tipo','v')->whereDate('dataEntrega','<=',$filtro['dataEntregaFinal'])
                ->orderBy('status_id')->orderBy('dataEntrega')->paginate(25);
            return view('listarVendas',['pedidos'=>$pedidos,'filtro'=>$filtro,'achou'=> true,'tipoFiltro'=>"Data Entrega Menor ou Igual à: ".date('d/m/Y',strtotime($filtro['dataEntregaFinal']))]);
        }
        else if(isset($filtro['dataEntregaInicial']) && isset($filtro['dataEntregaFinal'])){
            $pedidos = Pedido::where('tipo','v')->whereDate('dataEntrega','>=',$filtro['dataEntregaInicial'])
                ->whereDate('dataEntrega','<=',$filtro['dataEntregaFinal'])
                ->orderBy('status_id')->orderBy('dataEntrega')->paginate(25);
                return view('listarVendas',['pedidos'=>$pedidos,'filtro'=>$filtro,'achou'=> true,'tipoFiltro'=>"Intervalo Data Entrega: ".date('d/m/Y',strtotime($filtro['dataEntregaInicial']))." e ".date('d/m/Y',strtotime($filtro['dataEntregaFinal']))]);
        }
        else{
            return redirect()->route("listarVendas");
        }

        // $pedidos = $pedido->filtro($filtro,25);
        // return view('listarVendas',['pedidos'=>$pedidos,'filtro'=>$filtro,'achou'=> true]);
        // dd($pedidos);

    }

}
