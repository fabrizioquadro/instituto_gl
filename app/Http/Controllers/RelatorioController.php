<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;

class RelatorioController extends Controller
{
    public function index_comissoes(){
        $users = User::where('tipo','Investidor')->orderBy('nome')->get();
        return view('adm/relatorios/index_comissoes', compact('users'));
    }
}
