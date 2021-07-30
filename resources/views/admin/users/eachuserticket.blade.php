@extends('admin.layouts.master')

@section('title', 'Tickets')

@section('head_style')
    <!-- dataTables -->
    <link rel="stylesheet" type="text/css" href="{{ asset('public/backend/DataTables_latest/DataTables-1.10.18/css/jquery.dataTables.min.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ asset('public/backend/DataTables_latest/Responsive-2.2.2/css/responsive.dataTables.min.css') }}">
@endsection

@section('page_content')
    <div class="box">
       <div class="panel-body">
            <ul class="nav nav-tabs cus" role="tablist">
                <li>
                  <a href='{{url("admin/users/edit/$users->id")}}'>Profile</a>
                </li>

                <li>
                  <a href="{{url("admin/users/transactions/$users->id")}}">Transactions</a>
                </li>
                <li>
                  <a href="{{url("admin/users/wallets/$users->id")}}">Wallets</a>
                </li>
                <li class="active">
                  <a href="{{url("admin/users/tickets/$users->id")}}">Tickets</a>
                </li>
                <li>
                  <a href="{{url("admin/users/disputes/$users->id")}}">Disputes</a>
                </li>
           </ul>
          <div class="clearfix"></div>
       </div>
    </div>

    @if ($users->status == 'Inactive')
        <h3>{{ $users->first_name.' '.$users->last_name }}&nbsp;<span class="label label-danger">Inactive</span></h3>
    @elseif ($users->status == 'Suspended')
        <h3>{{ $users->first_name.' '.$users->last_name }}&nbsp;<span class="label label-warning">Suspended</span></h3>
    @elseif ($users->status == 'Active')
        <h3>{{ $users->first_name.' '.$users->last_name }}&nbsp;<span class="label label-success">Active</span></h3>
    @endif

    <div class="box">
      <div class="box-body">
        <div class="row">
            <div class="col-md-12">
                <div class="table-responsive">
                    <table class="table table-hover" id="eachuserticket">
                        <thead>
                          <tr>
                            <th>Date</th>
                            <th>Subject</th>
                            <th>Status</th>
                            <th>Priority</th>
                            <th>Last Reply</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if ($tickets)
                            @foreach($tickets as $ticket)
                                <tr>

                                    <td>{{ dateFormat($ticket->created_at) }}</td>

                                    <td><a href="{{ url('admin/tickets/reply/'.$ticket->id) }}">{{ $ticket->subject }}</a></td>

                                    @if ($ticket->ticket_status->name == 'Open')
                                        <td><span class="label label-success">Open</span></td>
                                    @elseif ($ticket->ticket_status->name == 'In Progress')
                                        <td><span class="label label-primary">In Progress</span></td>
                                    @elseif ($ticket->ticket_status->name == 'Hold')
                                        <td><span class="label label-warning">Hold</span></td>
                                    @elseif ($ticket->ticket_status->name == 'Closed')
                                        <td><span class="label label-danger">Closed</span></td>
                                    @endif

                                    <td>{{ $ticket->priority }}</td>

                                    <td>{{ $ticket->last_reply ?  dateFormat($ticket->last_reply)  :  'No Reply Yet' }}</td>

                                    <td>
                                        <a class="btn btn-xs btn-primary" href="{{ url('admin/tickets/edit/'.$ticket->id) }}"><i class="glyphicon glyphicon-edit"></i></a>

                                        <form action="{{ url('admin/tickets/delete/'.$ticket->id) }}" method="POST" style="display:inline">{{-- modal is in message_boxes.blade.php --}}
                                        {{ csrf_field() }}

                                        <button class="btn btn-xs btn-danger" data-message="Are you sure you want to delete this ticket?" data-target="#confirmDelete" data-title="Delete Ticket" data-toggle="modal" title="Delete" type="button"><i class="glyphicon glyphicon-trash"></i></button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        @else
                            No Ticket Found!
                        @endif
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @include('admin.layouts.partials.message_boxes')
@endsection

@push('extra_body_scripts')

<!-- jquery.dataTables js -->
<script src="{{ asset('public/backend/DataTables_latest/DataTables-1.10.18/js/jquery.dataTables.min.js') }}" type="text/javascript"></script>
<script src="{{ asset('public/backend/DataTables_latest/Responsive-2.2.2/js/dataTables.responsive.min.js') }}" type="text/javascript"></script>

<script type="text/javascript">
    $(function () {
      $("#eachuserticket").DataTable({
            "order": [],
            "columnDefs": [
            {
                "className": "dt-center",
                "targets": "_all"
            }
            ],
            "language": '{{Session::get('dflt_lang')}}',
            "pageLength": '{{Session::get('row_per_page')}}'
        });
    });
</script>
@endpush
