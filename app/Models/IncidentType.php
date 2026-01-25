<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class IncidentType extends BaseModel
{
    use HasFactory;

    public $timestamps = false;
    protected $table = 'incident_type';
    protected $fillable = [
        'type_name',
        'send_template_id',
        'code',
        'lifecycle',
        'alias',
    ];

    public function sendTemplate()
    {
        return $this->belongsTo(SendTemplate::class, 'send_template_id');
    }

    public function incidents()
    {
        return $this->hasMany(Incident::class, 'incident_type_id');
    }
}
