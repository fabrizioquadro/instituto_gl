<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BaixaAberto extends Model
{
    use HasFactory;

    protected $fillable = [
        'clinica_id',
        'estoque_aberto_id',
        'user_id',
        'quantidade',
        'motivo',
    ];

    public function estoque(){
        return $this->belongsTo(EstoqueAberto::class,'estoque_aberto_id','id');
    }

    public function user(){
        return $this->belongsTo(User::class);
    }

    public function clinica(){
        return $this->belongsTo(Clinica::class);
    }
}
