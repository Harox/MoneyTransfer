<?php

namespace App\DataTables\Admin;

use App\Http\Helpers\Common;
use App\Models\User;
use Yajra\DataTables\Services\DataTable;
use Illuminate\Support\Facades\Session;

class UsersDataTable extends DataTable
{
    /**
     * Build DataTable class.
     *
     * @param mixed $query Results from query() method.
     * @return \Yajra\DataTables\DataTableAbstract
     */
    public function ajax()
    {
        return datatables()
            ->eloquent($this->query())
            ->editColumn('first_name', function ($user)
            {
                return (Common::has_permission(\Auth::guard('admin')->user()->id, 'edit_user')) ?
                '<a href="' . url('admin/users/edit/' . $user->id) . '">'.$user->first_name.'</a>' : $user->first_name;
            })
            ->editColumn('last_name', function ($user)
            {
                return (Common::has_permission(\Auth::guard('admin')->user()->id, 'edit_user')) ?
                '<a href="' . url('admin/users/edit/' . $user->id) . '">'.$user->last_name.'</a>' : $user->last_name;
            })
            ->editColumn('phone', function ($user)
            {
                return !empty($user->formattedPhone) ? $user->formattedPhone : '-';
            })
            ->addColumn('role', function ($user)
            {
                return $user->role->display_name;
            })
            ->addColumn('last_login_at', function ($user)
            {
                return !empty($user->user_detail->last_login_at) ? \Carbon\Carbon::createFromTimeStamp(strtotime($user->user_detail->last_login_at))->diffForHumans() : '-';
            })
            ->addColumn('last_login_ip', function ($user)
            {
                return !empty($user->user_detail->last_login_ip) ? $user->user_detail->last_login_ip : '-';
            })
            ->addColumn('status', function ($user)
            {
                $status = '';

                if ($user->document_verification->count() > 0)
                {
                    foreach ($user->document_verification as $document)
                    {
                        // return $document->user->identity_verified;
                        // return $document->status;
                        // return $document->user->address_verified;

                        if ($document->user->address_verified && $document->user->identity_verified && $document->status == 'approved')
                        {
                            if ($document->user->status == 'Active') {
                                $status = '<span class="label label-success">Active</span>'.'<br>'.'<span class="label label-primary">Identity Verified</span>'.
                            '<br>'.'<span class="label label-info">Address Verified</span>';
                            } else if ($document->user->status == 'Inactive') {
                                $status = '<span class="label label-danger">Inactive</span>'.'<br>'.'<span class="label label-primary">Identity Verified</span>'.
                            '<br>'.'<span class="label label-info">Address Verified</span>';
                            } else if ($document->user->status == 'Suspended') {
                                $status = '<span class="label label-warning">Suspended</span>'.'<br>'.'<span class="label label-primary">Identity Verified</span>'.
                            '<br>'.'<span class="label label-info">Address Verified</span>';
                            }
                            // $status =
                            // '<br>'.'<span class="label label-primary">Identity Verified</span>'.
                            // '<br>'.'<span class="label label-info">Address Verified</span>';
                        }
                        elseif ($document->user->address_verified && !$document->user->identity_verified && $document->status == 'approved')
                        {
                            // $status ='<br>'.'<span class="label label-info">Address Verified</span>';

                            if ($document->user->status == 'Active') {
                                $status = '<span class="label label-success">Active</span>'.'<br>'.'<span class="label label-info">Address Verified</span>';
                            } else if ($document->user->status == 'Inactive') {
                                $status = '<span class="label label-danger">Inactive</span>'.'<br>'.'<span class="label label-info">Address Verified</span>';
                            } else if ($document->user->status == 'Suspended') {
                                $status = '<span class="label label-warning">Suspended</span>'.'<br>'.'<span class="label label-info">Address Verified</span>';
                            }
                        }
                        elseif (!$document->user->address_verified && $document->user->identity_verified && $document->status == 'approved')
                        {
                            // $status ='<br>'.'<span class="label label-primary">Identity Verified</span>';
                            if ($document->user->status == 'Active') {
                                $status = '<span class="label label-success">Active</span>'.'<br>'.'<span class="label label-primary">Identity Verified</span>';
                            } else if ($document->user->status == 'Inactive') {
                                $status = '<span class="label label-danger">Inactive</span>'.'<br>'.'<span class="label label-primary">Identity Verified</span>';
                            } else if ($document->user->status == 'Suspended') {
                                $status = '<span class="label label-warning">Suspended</span>'.'<br>'.'<span class="label label-primary">Identity Verified</span>';
                            }
                        }
                        elseif (!$document->user->address_verified && !$document->user->identity_verified && $document->status != 'approved')
                        {
                            // $status = '<span class="label label-success">Active</span>';
                            if ($document->user->status == 'Active') {
                                $status = '<span class="label label-success">Active</span>';
                            } else if ($document->user->status == 'Inactive') {
                                $status = '<span class="label label-danger">Inactive</span>';
                            } else if ($document->user->status == 'Suspended') {
                                $status = '<span class="label label-warning">Suspended</span>';
                            }
                        }
                    }
                }
                else
                {
                    if ($user->status == 'Active') {
                        $status = '<span class="label label-success">Active</span>';
                    } else if ($user->status == 'Inactive') {
                        $status = '<span class="label label-danger">Inactive</span>';
                    } else if ($user->status == 'Suspended') {
                        $status = '<span class="label label-warning">Suspended</span>';
                    }
                }
                return $status;
            })
            ->addColumn('action', function ($user)
            {
                $edit = $delete = '';
                $edit = (Common::has_permission(\Auth::guard('admin')->user()->id, 'edit_user')) ? '<a href="' . url('admin/users/edit/' . $user->id) . '" class="btn btn-xs btn-primary"><i class="glyphicon glyphicon-edit"></i></a>&nbsp;' : '';
                $delete = (Common::has_permission(\Auth::guard('admin')->user()->id, 'delete_user')) ? '<a href="' . url('admin/users/delete/' . $user->id) . '" class="btn btn-xs btn-danger delete-warning"><i class="glyphicon glyphicon-trash"></i></a>' : '';
                return $edit . $delete;
            })
            ->rawColumns(['first_name','last_name','status','action'])
            ->make(true);
    }

    public function query()
    {
        $query = User::with(['document_verification:id,user_id,status','role:id,display_name','user_detail:id,user_id,last_login_at,last_login_ip'])->select('users.*');
        return $this->applyScopes($query);
    }

    public function html()
    {
        return $this->builder()
            ->addColumn(['data' => 'id', 'name' => 'users.id', 'title' => 'ID', 'searchable' => false, 'visible' => false])

            ->addColumn(['data' => 'status', 'name' => 'document_verification.status', 'title' => 'Document Verification Status', 'visible' => false])

            ->addColumn(['data' => 'first_name', 'name' => 'users.first_name', 'title' => 'First Name'])

            ->addColumn(['data' => 'last_name', 'name' => 'users.last_name', 'title' => 'Last Name'])

            ->addColumn(['data' => 'phone', 'name' => 'users.phone', 'title' => 'Phone'])

            ->addColumn(['data' => 'email', 'name' => 'users.email', 'title' => 'Email'])

            ->addColumn(['data' => 'role', 'name' => 'role', 'title' => 'Group'])

            ->addColumn(['data' => 'last_login_at', 'name' => 'user_detail.last_login_at', 'title' => 'Last Login'])
            ->addColumn(['data' => 'last_login_ip', 'name' => 'user_detail.last_login_ip', 'title' => 'IP'])

            ->addColumn(['data' => 'status', 'name' => 'users.status', 'title' => 'Status'])

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
