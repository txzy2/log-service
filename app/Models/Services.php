<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Services extends Model
{
    protected $table = 'incident_services';

    protected $fillable = [
        'name',
        'active',
    ];
}
