<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProcedimentoAnexo extends Model
{
    use HasFactory;

    protected $fillable = [
        'procedimento_id',
        'nm_anexo',
        'anexo',
    ];
}
