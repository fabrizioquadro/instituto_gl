<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProcedimentoObservacao extends Model
{
    use HasFactory;

    protected $fillable = [
        'procedimento_id',
        'user_id',
        'observacao'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
