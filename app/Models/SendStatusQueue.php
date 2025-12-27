<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class SendStatusQueue extends BaseModel
{
    use HasFactory;

    protected $table = 'send_status_queue';
    protected $fillable = ['status', 'job_id', 'error'];
    public $timestamps  = true;
}
