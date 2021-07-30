<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NotificationSetting extends Model
{
	protected $table = 'notification_settings';

	protected $fillable = ['notification_type_id', 'recipient_type', 'recipient', 'status'];

    public function notification_type()
    {
        return $this->belongsTo(NotificationType::class);
    }

    public static function getSettings($conditions = [])
    {
        $conditions = array_merge($conditions, ['nt.status' => 'Active']);
        $notificationSettings = self::select('notification_settings.*', 'nt.name', 'nt.alias')
        ->leftjoin('notification_types as nt', 'nt.id', '=', 'notification_settings.notification_type_id')
        ->where($conditions)
        ->get();
        return $notificationSettings;
    }
}
