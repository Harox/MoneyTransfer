<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\{NotificationType,
    NotificationSetting
};
use Illuminate\Http\Request;
use App\Http\Helpers\Common;

class NotificationSettingController extends Controller
{
    protected $helper;

    public function __construct()
    {
        $this->helper = new Common();
    }

    public function index($type = 'email')
    {
        $data['menu'] = 'notification-settings';

        $data['notificationSettings'] = NotificationSetting::getSettings(['notification_settings.recipient_type' => $type]);

        return view('admin.settings.notification_' . $type . '_settings.index', $data);
    }

    public function update(Request $request)
    {
        $notificationTypes = NotificationType::get(['id', 'alias'])->pluck('id', 'alias');
        $data              = $request->all();
        $array             = [];

        $recipientType = 'email';

        if (!empty($data['notification'])) {
            foreach ($data['notification'] as $recipientType => $recipient) {
                foreach ($recipient as $notificationType => $value) {

                    if ($recipientType == 'email') {

                        if (isset($value['status']) && isset($value['recipient'])) {

                            if (filter_var($value['recipient'], FILTER_VALIDATE_EMAIL) == false) {
                                $this->helper->one_time_message('error', ucfirst($notificationType) . ' email address is not valid!');
                                return redirect('admin/settings/notification-settings/' . $recipientType);
                            }

                        } elseif (isset($value['status']) && !isset($value['recipient'])) {

                            $this->helper->one_time_message('error', ucfirst($notificationType) . ' email address field is empty!');
                            return redirect('admin/settings/notification-settings/' . $recipientType);

                        } elseif (isset($value['recipient'])) {

                            if (filter_var($value['recipient'], FILTER_VALIDATE_EMAIL) == false) {
                                $this->helper->one_time_message('error', ucfirst($notificationType) . ' email address is not valid!');
                                return redirect('admin/settings/notification-settings/' . $recipientType);
                            }
                        }
                    } elseif ($recipientType == 'sms') {

                        if (isset($value['status']) && isset($value['recipient'])) {

                            if (strlen($value['recipient']) < 10) {
                                $this->helper->one_time_message('error', ucfirst($notificationType) . ' number should at least 10 digits!');
                                return redirect('admin/settings/notification-settings/' . $recipientType);
                            }

                        }
                        elseif (isset($value['status']) && !isset($value['recipient']))
                        {

                            $this->helper->one_time_message('error', ucfirst($notificationType) . ' number field is empty!');
                            return redirect('admin/settings/notification-settings/' . $recipientType);

                        }
                        elseif (isset($value['recipient']))
                        {

                            if (strlen($value['recipient']) < 10)
                            {
                                $this->helper->one_time_message('error', ucfirst($notificationType) . ' number should at least 10 digits!');
                                return redirect('admin/settings/notification-settings/' . $recipientType);
                            }
                        }
                    }

                    $array[$recipientType][] = [
                        'id'                   => $value['id'],
                        'notification_type_id' => $notificationTypes[$notificationType],
                        'recipient_type'       => $recipientType,
                        'recipient'            => $value['recipient'],
                        'status'               => isset($value['status']) ? 'Yes' : 'No',
                    ];
                }
            }

            foreach ($array as $value)
            {
                foreach ($value as $result)
                {
                    NotificationSetting::where('id', $result['id'])->update($result);
                }
            }

            $this->helper->one_time_message('success', 'Notification settings updated successfully!');
        }

        return redirect('admin/settings/notification-settings/' . $recipientType);
    }
}
