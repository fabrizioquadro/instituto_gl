<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transferencia extends Model
{
    use HasFactory;

    protected $fillable = [
        'clinica_id',
        'clinica_destino_id',
        'motivo',
        'data',
        'valor',
    ];

    public function origem(){
        return $this->belongsTo(Clinica::class,'clinica_id','id');
    }

    public function destino(){
        return $this->belongsTo(Clinica::class,'clinica_destino_id','id');
    }

    public function medicamentos($clinica_id){
        return Estoque::where('transferencia_id', $this->id)
        ->where('clinica_id', $clinica_id)
        ->get();
    }
}
