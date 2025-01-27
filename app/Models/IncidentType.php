<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IncidentType extends Model
{
    use HasFactory;
    protected $table = 'incident_type';

    public function sendTemplate()
    {
        return $this->belongsTo(SendTemplate::class, 'send_template_id');
    }

    public function incidents()
    {
        return $this->hasMany(Incident::class, 'incident_type_id');
    }
}