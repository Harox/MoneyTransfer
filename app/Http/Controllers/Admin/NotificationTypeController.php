<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Redirect;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Helpers\Common;
use App\Models\{NotificationSetting,
    NotificationType
};

class NotificationTypeController extends Controller
{
    protected $helper;

    public function __construct()
    {
        $this->helper = new Common();
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $data['menu']              = 'notification-settings';
        $data['notificationTypes'] = $notificationTypes = NotificationType::select(['id', 'name', 'status'])->get();

        return view('admin.settings.notification_types.index', $data);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  [int]  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $data['menu']             = 'notification-settings';
        $data['notificationType'] = $notificationType = NotificationType::find($id);

        if (empty($notificationType))
        {
            $this->helper->one_time_message('error', 'Notification type not found !');
            return redirect('admin/settings/notification-types');
        }

        return view('admin.settings.notification_types.edit', $data);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request    $request
     * @param  [int]                       $id
     * @return \Illuminate\Http\Response
     */
    public function update($id, Request $request)
    {
        $notificationType = NotificationType::find($id);

        if (empty($notificationType))
        {
            $this->helper->one_time_message('error', 'Notification type not found !');
            return redirect('admin/settings/notification-types');
        }

        $rules = array(
            'notification_type_name'   => 'required|unique:notification_types,name,' . $id,
            'notification_type_status' => 'required',
        );

        $fieldNames = array(
            'notification_type_name'   => 'Notification type name',
            'notification_type_status' => 'Notification type status',

        );

        $validator = Validator::make($request->all(), $rules);
        $validator->setAttributeNames($fieldNames);

        if ($validator->fails())
        {
            return back()->withErrors($validator)->withInput();
        }

        $notificationType->name   = $request->notification_type_name;
        $notificationType->status = $request->notification_type_status;

        if ($notificationType->save())
        {
            $this->helper->one_time_message('success', 'Notification updated successfully !');
            return redirect('admin/settings/notification-types');
        }
        else
        {
            $this->helper->one_time_message('error', 'Notification type not found !');
            return redirect('admin/settings/notification-types');
        }

    }


    /**
     * Check the specified unique name.
     *
     * @param  \Illuminate\Http\Request    $request
     * @param  [int]                       $id
     * @return \Illuminate\Http\Response
     */
    public function uniqueNotificationTypeNameCheck(Request $request)
    {
        $req_name = $request->notification_type_name;
        $req_id   = base64_decode($request->notification_type_id);

        $notificationTypeName = NotificationType::where(['name' => $req_name])->where(function ($query) use ($req_id)
        {
            $query->where('id', '!=', $req_id);
        })->exists();

        if ($notificationTypeName)
        {
            $data['status'] = false;
            $data['fail']   = "Notification type name has already been taken.";
        }
        else
        {
            $data['status'] = true;
            $data['fail']   = "Available.";
        }
        echo json_encode($data);
    }
}
