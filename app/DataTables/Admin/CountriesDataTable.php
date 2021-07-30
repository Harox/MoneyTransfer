<?php

namespace App\DataTables\Admin;

use App\Http\Helpers\Common;
use App\Models\Country;
use App\User;
use Yajra\DataTables\Services\DataTable;
use Illuminate\Support\Facades\Session;

class CountriesDataTable extends DataTable
{
    /**
     * Build DataTable class.
     *
     * @return \Yajra\Datatables\Engines\BaseEngine
     */
    public function ajax() //don't use default dataTable() method
    {
        return datatables()
            ->eloquent($this->query())
            ->editColumn('is_default', function($country){
                $isDefault = '';
                if ($country->is_default == 'no') {
                    $isDefault = '<span class="label label-danger">No</span>';
                } else if ($country->is_default == 'yes') {
                    $isDefault = '<span class="label label-success">Yes</span>';
                } 
                return $isDefault;
            })
            ->addColumn('action', function ($country)
            {
                $edit = $delete = '';
                $edit = (Common::has_permission(\Auth::guard('admin')->user()->id, 'edit_country')) ? '<a href="' . url('admin/settings/edit_country/' . $country->id) . '" class="btn btn-xs btn-primary"><i class="glyphicon glyphicon-edit"></i></a>&nbsp;' : '';

                $delete = (Common::has_permission(\Auth::guard('admin')->user()->id, 'delete_country')) ? '<a href="' . url('admin/settings/delete_country/' . $country->id) . '" class="btn btn-xs btn-danger delete-warning"><i class="glyphicon glyphicon-trash"></i></a>' : '';

                return $edit . $delete;
            })
            ->rawColumns(['is_default', 'action'])
            ->make(true);
    }

    /**
     * Get the query object to be processed by dataTables.
     *
     * @return \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Query\Builder|\Illuminate\Support\Collection
     */
    public function query()
    {
        // $query = Country::query()->orderBy('id', 'desc');
        $query = Country::select();
        return $this->applyScopes($query);
    }

    /**
     * Optional method if you want to use html builder.
     *
     * @return \Yajra\Datatables\Html\Builder
     */
    public function html()
    {
        return $this->builder()
            ->addColumn(['data' => 'id', 'name' => 'countries.id', 'title' => 'ID', 'searchable' => false, 'visible' => false])
            ->addColumn(['data' => 'short_name', 'name' => 'countries.short_name', 'title' => 'Short Name'])
            ->addColumn(['data' => 'name', 'name' => 'countries.name', 'title' => 'Long Name'])
            ->addColumn(['data' => 'iso3', 'name' => 'countries.iso3', 'title' => 'Iso3'])
            ->addColumn(['data' => 'number_code', 'name' => 'countries.number_code', 'title' => 'Num Code'])
            ->addColumn(['data' => 'phone_code', 'name' => 'countries.phone_code', 'title' => 'Phone Code'])
            ->addColumn(['data' => 'is_default', 'name' => 'countries.is_default', 'title' => 'Default'])
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

    /**
     * Get columns.
     *
     * @return array
     */
    protected function getColumns()
    {
        return [
            'short_name',
            'name',
            'iso3',
            'number_code',
            'phone_code',
            'is_default',
            'action',
        ];
    }

    /**
     * Get filename for export.
     *
     * @return string
     */
    protected function filename()
    {
        return 'countriesdatatable_' . time();
    }
}
