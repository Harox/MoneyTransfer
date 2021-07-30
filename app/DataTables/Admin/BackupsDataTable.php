<?php

namespace App\DataTables\Admin;

use App\Http\Helpers\Common;
use App\Models\Backup;
use Yajra\DataTables\Services\DataTable;
use Illuminate\Support\Facades\Session;

class BackupsDataTable extends DataTable
{
    public function ajax() //don't use default dataTable() method
    {
        return datatables()
            ->eloquent($this->query())
            ->addColumn('action', function ($backup)
            {
                $edit = '';

                $edit = (Common::has_permission(\Auth::guard('admin')->user()->id, 'edit_database_backup')) ? '<a href="' . url('admin/backup/download/' . $backup->id) . '" class="btn btn-xs btn-primary"><i class="fa fa-download"></i></a>' : '';
                return $edit;

            })
            ->make(true);
    }

    /**
     * Get the query object to be processed by dataTables.
     */
    public function query()
    {
        $backup = Backup::select('backups.*');
        return $this->applyScopes($backup);
    }

    /**
     * html builder.
     */
    public function html()
    {
        return $this->builder()
            ->addColumn(['data' => 'id', 'name' => 'backups.id', 'title' => 'Id', 'searchable' => false, 'visible' => false])
            ->addColumn(['data' => 'name', 'name' => 'backups.name', 'title' => 'Name'])
            ->addColumn(['data' => 'created_at', 'name' => 'backups.created_at', 'title' => 'Date'])
            ->addColumn(['data' => 'action', 'name' => 'action', 'title' => 'Action', 'orderable' => false, 'searchable' => false])
            ->parameters([
                'order'      => [[0, 'desc']],
                //centering all texts in columns
                "columnDefs" => [
                    [
                        "className" => "dt-center",
                        "targets" => "_all"
                    ]
                ],
                'pageLength' => Session::get('row_per_page'),
                'language'   => Session::get('language'),
            ]);
    }
}
