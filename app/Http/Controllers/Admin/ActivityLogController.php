<?php

namespace App\Http\Controllers\Admin;

use App\DataTables\Admin\ActivityLogsDataTable;
use App\Http\Controllers\Controller;
use App\Http\Helpers\Common;
use Illuminate\Http\Request;
use App\Models\{ActivityLog, 
    User
};

class ActivityLogController extends Controller
{
    public function activities_list(ActivityLogsDataTable $dataTable)
    {
        $data['menu']     = 'activity_logs';
        return $dataTable->render('admin.activity_logs.list', $data);
    }
}
