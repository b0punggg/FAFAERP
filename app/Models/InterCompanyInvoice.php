<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InterCompanyInvoice extends Model
{
    protected $fillable = [
        'seller_company_id',
        'buyer_company_id',
        'invoice_no',
        'invoice_date',
        'amount',
        'status',
    ];
}
