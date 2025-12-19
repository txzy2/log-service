<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class SendTemplate extends BaseModel
{
    use HasFactory;

    protected $table    = 'send_template';
    protected $fillable = ['to', 'subject', 'template'];
    public $timestamps  = false;

    public function incidentTypes()
    {
        return $this->hasMany(IncidentType::class, 'send_template_id');
    }
}
