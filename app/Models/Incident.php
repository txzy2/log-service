<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Incident extends Model
{
    protected $connection = 'clickhouse';
    protected $table = 'incidents';
}