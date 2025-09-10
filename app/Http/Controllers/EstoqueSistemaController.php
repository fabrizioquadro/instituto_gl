<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Estoque;
use App\Models\Medicamento;

class EstoqueSistemaController extends Controller
{
    public function index(){
        $user = auth()->user();
        if(!$user){
            $user = session()->get('user');
        }
        //vamos buscar todos os medicamentos que a clinica possui no estoque
        $medicamentos = Estoque::select('medicamento_id')
        ->where('clinica_id', $user->clinica_id)
        ->distinct()
        ->get();

        $array_estoque = array();

        foreach($medicamentos as $linha){
            $medicamento = Medicamento::where('id', $linha->medicamento_id)->first();
            $lotes = Estoque::get_lotes_medicamento($medicamento->id, $user->clinica_id);

            foreach($lotes as $lote){
                $array = array();
                $array['medicamento_nome'] = $medicamento->nome;
                $array['lote'] = $lote['lote'];
                $array['unidade'] = $medicamento->unidade;
                $array['quantidade'] = $lote['estoque'];
                $array['vl_compra'] = $medicamento->ultimo_valor_pg;
                $array['vl_compra_total'] = round($lote['estoque'] * $medicamento->ultimo_valor_pg, 2);
                $array['vl_venda'] = $medicamento->vl_venda;
                $array['vl_venda_total'] = round($lote['estoque'] * $medicamento->vl_venda, 2);
                $array_estoque[] = $array;
            }
        }
        return view('sistema/estoques/index', compact('array_estoque'));
    }
}
