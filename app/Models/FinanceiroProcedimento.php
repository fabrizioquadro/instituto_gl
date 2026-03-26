<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FinanceiroProcedimento extends Model
{
    use HasFactory;

    protected $fillable = [
        'financeiro_id',
        'procedimento_id',
    ];
}
