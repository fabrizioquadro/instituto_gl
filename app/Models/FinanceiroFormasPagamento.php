<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FinanceiroFormasPagamento extends Model
{
    use HasFactory;

    protected $fillable = [
        'financeiro_id',
        'forma_pagamento',
        'parcelas',
        'vl_pagamento',
    ];
}
