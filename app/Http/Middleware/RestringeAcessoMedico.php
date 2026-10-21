<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RestringeAcessoMedico
{
    private $rotas_permitidas = [
        'sistema.medico.dashboard',
        'sistema.medico.conjunto',
        'sistema.medico.procedimento',
        'sistema.procedimentos.imprimir_cadastro',
        'sistema.pacientes.listar_pacientes_ajax',
        'sistema.perfil',
        'sistema.perfil.atualizar_foto',
        'sistema.perfil.update',
        'sistema.perfil.resetar_foto',
        'sistema.alterar_senha',
        'sistema.alterar_senha.update',
    ];

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();

        if($user && $user->tipo == 'Médico'){
            $rota = $request->route() ? $request->route()->getName() : null;

            if(!$rota || !in_array($rota, $this->rotas_permitidas)){
                return redirect()->route('sistema.medico.dashboard');
            }
        }

        return $next($request);
    }
}
