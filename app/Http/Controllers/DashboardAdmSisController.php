<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Procedimento;

class DashboardAdmSisController extends Controller
{
    public function index(){
        $user = session()->get('user');

        $procedimentos = Procedimento::where('situacao', 'Fila de Aplicação')
        ->where('clinica_id', $user->clinica_id)
        ->get();

        $data = date('Y-m-d');
        if($_POST){
            $procedimentos_sec = Procedimento::where('clinica_id', $user->clinica_id)
            ->where('paciente_id', $_POST['paciente_id'])
            ->where('situacao', 'Agendado')
            ->get();
        }
        else{
            $procedimentos_sec = Procedimento::where('clinica_id', $user->clinica_id)
            ->where('data_aplicacao', $data)
            ->where('situacao', 'Agendado')
            ->get();
        }

        return view('adm/dashboard_sistema/index', compact('procedimentos','procedimentos_sec'));
    }
}
