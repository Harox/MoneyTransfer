<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    protected $table   = 'activity_logs';

    protected $fillable = [
        'user_id',
        'type',
        'ip_address',
        'browser_agent',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function admin()
    {
        return $this->belongsTo(Admin::class, 'user_id');
    }
}
