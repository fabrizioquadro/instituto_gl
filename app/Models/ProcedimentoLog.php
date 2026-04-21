<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProcedimentoLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'procedimento_id',
        'usuario_id',
        'administrador_id',
        'acao',
        'descricao',
        'dados_antigos',
        'dados_novos'
    ];

    protected $casts = [
        'dados_antigos' => 'array',
        'dados_novos' => 'array'
    ];

    public function procedimento()
    {
        return $this->belongsTo(Procedimento::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    public function administrador()
    {
        return $this->belongsTo(Administrador::class);
    }

    public static function registrar($procedimento_id, $acao, $descricao = null, $dados_antigos = null, $dados_novos = null)
    {
        $user = session()->get('user');
        $adm = session()->get('administrador');

        return self::create([
            'procedimento_id' => $procedimento_id,
            'usuario_id' => ($user && $user->id != '0') ? $user->id : null,
            'administrador_id' => $adm ? $adm->id : null,
            'acao' => $acao,
            'descricao' => $descricao,
            'dados_antigos' => $dados_antigos,
            'dados_novos' => $dados_novos
        ]);
    }

    public function autor()
    {
        if ($this->administrador) {
            return $this->administrador->nome . ' (Admin)';
        }
        if ($this->user && $this->user->nome) {
            return $this->user->nome . ' (Usuário)';
        }
        return 'Sistema';
    }
}
