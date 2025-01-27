<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SendTemplate extends Model
{
    use HasFactory;

    protected $table = 'send_template';
    public function incidentTypes()
    {
        return $this->hasMany(IncidentType::class, 'send_template_id');
    }
}