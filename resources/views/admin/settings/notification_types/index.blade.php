@extends('admin.layouts.master')
@section('title', 'Notification Types')

@section('page_content')
    <!-- Main content -->
    <div class="row">
        <div class="col-md-3 settings_bar_gap">
            @include('admin.common.settings_bar')
        </div>
        <div class="col-md-9">
            <div class="box box-info">
                <div class="nav-tabs-custom">
                    <ul class="nav nav-tabs" id="tabs">
                      <li class="active"><a href="{{ url('admin/settings/notification-types') }}">Notification Types</a></li>
                      <li><a href="{{ url('admin/settings/notification-settings/email') }}">Email Notification Settings</a></li>
                      {{-- <li><a href="{{ url('admin/settings/notification-settings/sms') }}">SMS Notification Settings</a></li> --}}
                    </ul>

                    <div class="tab-content">
                        <div class="tab-pane fade in active" id="tab_1">
                            <div class="box-body" >
                                @if($notificationTypes->count() > 0)
                                    <table class="table table-responsive text-center">
                                        <thead>
                                            <tr>
                                                <th>Type</th>
                                                <th>Status</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($notificationTypes as $notificationType)
                                                <tr>
                                                    <td>{{ $notificationType->name}} </td>
                                                    @if($notificationType->status =='Inactive')
                                                        <td><span class="label label-danger">{{ $notificationType->status }}</span></td>

                                                    @elseif($notificationType->status =='Active')
                                                        <td><span class="label label-success">{{ $notificationType->status }}</span></td>
                                                    @endif
                                                    <td>
                                                        <a href="{{ url('admin/settings/notification-types/edit/'.$notificationType->id) }}" class="btn btn-xs btn-primary"><i class="glyphicon glyphicon-edit"></i></a>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                @else
                                    <h5 style="padding: 15px 20px; ">Notifications not found!</h5>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
