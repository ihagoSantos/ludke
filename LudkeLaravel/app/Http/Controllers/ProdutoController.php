<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Produto;
use App\FotosProduto;
use File;

class ProdutoController extends Controller
{
    // Retorna a view dos produtos
    public function indexView()
    {
        return view('produto');
    }

    //usado pela api para retornar os produtos
    public function index()
    {
        $produtos = Produto::all();
        return $produtos->toJson();
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
//
    }


    // Recebe o request do ajax
    public function store(Request $request)
    {

        // dd($request->all());
        // dd($request->file('imagensProduto'));
        


        // salva produtos no banco
        $prod = new Produto();
        $prod->nome = $request->input('nome');
        $prod->validade = $request->input('validade');
        // $prod->quantidade = $request->input('quantidade');
        $prod->preco = $request->input('preco');
        $prod->descricao = $request->input('descricao');
        $prod->categoria_id = $request->input('categoria_id');
        
      
        $prod->save();

        $fotosProduto = $request->file('imagensProduto');
        if(isset($fotosProduto)){
            foreach($fotosProduto as $f){
                $path = $f->store('public');
                $nomeFoto = str_replace('public/','',$path);
                // $path = $f->store('fotosProduto');
                // dd($path);
                $foto = new FotosProduto();
                $foto->path = $nomeFoto; 
                $foto->produto_id = $prod->id;
                $foto->save();
            }
        }
        
        // $fotosProduto = $request->file('imagensProduto');
        // if(isset($fotosProduto)){
        //     foreach($fotosProduto as $f){
        //         $input['path'] = time().'.'.$f->getClientOriginalExtension();
        //         $f->move(public_path('images'),$input['path']);

        //         $input['produto_id'] = $prod->id;
                
        //         FotosProduto::create($input);
        //     }
        // }
        
        // retorna o objeto para exibir na tabela
        return json_encode($prod);
        
        
    }

    //Exibe um determinado produto
    public function show($id)
    {
        $produto = Produto::find($id);
        $fotosProduto = FotosProduto::where('produto_id',$id)->get();
        // dd($fotosProduto);
        // dd($produto);
        $prod = [
            'id' => $produto->id,
            'nome' => $produto->nome,
            'validade' => $produto->validade,
            'preco' => $produto->preco,
            'descricao' => $produto->descricao,
            'categoria_id' => $produto->categoria_id,
            'created_at' => $produto->created_at,
            'updated_at' => $produto->updated_at,
            'fotosProduto' => $fotosProduto,
        ];
        if(isset($prod)){
            return json_encode($prod);// retorna um objeto json
        }
        else{
            return response('Produto não encontrado',404);
        }

    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $prod = Produto::find($id);
        // dd($request->input('nome'));
        
        if(isset($prod)){
            $prod->nome = $request->input('nome');
            $prod->validade = $request->input('validade');
            // $prod->quantidade = $request->input('quantidade');
            $prod->preco = $request->input('preco');
            $prod->descricao = $request->input('descricao');
            $prod->categoria_id = $request->input('categoria_id');

            $fotosProduto = $request->file('imagensProduto');
            if(isset($fotosProduto)){
                foreach($fotosProduto as $f){
                    $path = $f->store('public');
                    $nomeFoto = str_replace('public/','',$path);
                    // $path = $f->store('fotosProduto');
                    // dd($path);
                    $foto = new FotosProduto();
                    $foto->path = $nomeFoto; 
                    $foto->produto_id = $prod->id;
                    $foto->save();
                }
            }

            if(isset($request->arrayIdsDeletarFotos)){
                foreach($request->arrayIdsDeletarFotos as $id){
                    $foto = FotosProduto::find($id);
                    $foto->delete();
                }
            }
            
            $prod->save();
            // retorna o objeto para exibir na tabela
            return json_encode($prod);
        }
        else{
            return response('Produto não encontrado',404);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $prod = Produto::find($id);
        if(isset($prod)){
            $fotosProduto = FotosProduto::where('produto_id',$prod->id);
            if(isset($fotosProduto)){
                foreach($fotosProduto as $foto){
                    File::delete($foto->path);
                    // $nomeFoto = str_replace('images/','',$foto->path);
                    // Storage::delete($nomeFoto);
                    // Storage::disk('public')->delete($nomeFoto);
                    // $nomeFoto = str_replace('fotosProduto/','',$foto->path);
                    // Storage::disk('public')->delete($foto->path);
                    
                    // Storage::disk('fotosProduto')->delete($nomeFoto);
                }
                $fotosProduto->delete();
            }
            $prod->delete();
            return response('OK',200);
        }
        return response('Produto não encontrado',404);
    }
}
