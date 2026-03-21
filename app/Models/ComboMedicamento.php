<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ComboMedicamento extends Model
{
    use HasFactory;

    protected $fillable = [
        'combo_id',
        'medicamento_id',
        'quantidade',
        'valor_unitario',
    ];

    public function medicamento(){
        return $this->belongsTo(Medicamento::class);
    }
}
