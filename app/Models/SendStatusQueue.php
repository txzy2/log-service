<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class SendStatusQueue extends BaseModel
{
    use HasFactory;

    public $timestamps = true;
    protected $table = 'send_status_queue';
    protected $fillable = ['status', 'job_id', 'error'];
}
