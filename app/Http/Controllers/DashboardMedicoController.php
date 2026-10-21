<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Paciente;
use App\Models\Procedimento;
use App\Models\ProcedimentoAnexo;

class DashboardMedicoController extends Controller
{
    private function usuario(){
        $user = auth()->user();
        if(!$user){
            $user = session()->get('user');
        }
        return $user;
    }

    public function index(Request $request){
        $user = $this->usuario();
        $paciente_id = $request->input('paciente_id');
        $termo = trim((string) $request->input('paciente'));
        $paciente_selecionado = null;
        $pacientes = collect();
        $conjuntos = null;

        if($paciente_id){
            $paciente_selecionado = Paciente::where('id', $paciente_id)->first();
            if($paciente_selecionado){
                $pacientes = collect([$paciente_selecionado]);
            }
        }
        elseif(mb_strlen($termo) >= 2){
            $pacientes = Paciente::where('nm_paciente','LIKE','%'.$termo.'%')
            ->orderBy('nm_paciente')
            ->get();
        }

        if($pacientes->count() > 0){
            $pacientes_ids = $pacientes->pluck('id');

            $primeiros = Procedimento::select('codigo', DB::raw('MIN(nr_procedimento) as primeiro_nr'))
            ->whereIn('paciente_id', $pacientes_ids)
            ->groupBy('codigo');

            $conjuntos = Procedimento::select('procedimentos.*')
            ->joinSub($primeiros, 'primeiros', function($join){
                $join->on('procedimentos.codigo','=','primeiros.codigo')
                ->on('procedimentos.nr_procedimento','=','primeiros.primeiro_nr');
            })
            ->whereIn('procedimentos.paciente_id', $pacientes_ids)
            ->orderByDesc('procedimentos.data_cad')
            ->paginate(20)
            ->withQueryString();
        }

        return view('sistema/medico/index', compact('user','termo','paciente_id','paciente_selecionado','pacientes','conjuntos'));
    }

    public function conjunto(Request $request, $codigo){
        $user = $this->usuario();

        $procedimentos = Procedimento::where('codigo', $codigo)
        ->orderBy('nr_procedimento')
        ->get();

        if($procedimentos->count() == 0){
            return redirect()->route('sistema.medico.dashboard')->with('mensagem_erro', 'Conjunto de procedimentos não encontrado.');
        }

        $procedimento = $procedimentos->first();

        return view('sistema/medico/conjunto', compact('user','procedimentos','procedimento'));
    }

    public function procedimento(Request $request, $id){
        $user = $this->usuario();

        $procedimento = Procedimento::where('id', $id)->first();

        if(!$procedimento){
            return redirect()->route('sistema.medico.dashboard')->with('mensagem_erro', 'Procedimento não encontrado.');
        }

        $procedimentos_conjunto = Procedimento::where('codigo', $procedimento->codigo)
        ->orderBy('nr_procedimento')
        ->get();

        $anexos = ProcedimentoAnexo::whereIn('procedimento_id', $procedimentos_conjunto->pluck('id'))->get();
        $observacoes = $procedimento->observacoes_procedimento()->orderBy('created_at','desc')->get();
        $aplicacoes = $procedimento->aplicacaos;

        return view('sistema/medico/procedimento', compact('user','procedimento','procedimentos_conjunto','anexos','observacoes','aplicacoes'));
    }
}
