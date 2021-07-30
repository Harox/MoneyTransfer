@extends('admin.layouts.master')
@section('title', 'Disputes')

@section('head_style')
    <!-- Bootstrap daterangepicker -->
    <link rel="stylesheet" type="text/css" href="{{ asset('public/backend/bootstrap/dist/css/daterangepicker.css')}}">

    <!-- dataTables -->
    <link rel="stylesheet" type="text/css" href="{{ asset('public/backend/DataTables_latest/DataTables-1.10.18/css/jquery.dataTables.min.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ asset('public/backend/DataTables_latest/Responsive-2.2.2/css/responsive.dataTables.min.css') }}">

    <!-- jquery-ui-1.12.1 -->
    <link rel="stylesheet" type="text/css" href="{{ asset('public/backend/jquery-ui-1.12.1/jquery-ui.min.css')}}">
@endsection

@section('page_content')
    <div class="box">
        <div class="box-body">
            <form class="form-horizontal" action="{{ url('admin/disputes') }}" method="GET" id='transaction_form'>

                <input id="startfrom" type="hidden" name="from" value="{{ isset($from) ? $from : '' }}">

                <input id="endto" type="hidden" name="to" value="{{ isset($to) ? $to : '' }}">

                <input id="user_id" type="hidden" name="user_id" value="{{ isset($user) ? $user : '' }}">

                <input id="transaction_id" type="hidden" name="transaction_id" value="{{ isset($transaction) ? $transaction : '' }}">

                <div class="row">
                    <div class="col-md-11">
                        <div class="row">
                            <!-- Date and time range -->
                            <div class="col-md-3" style="margin-right: 5px;">
                                <label>Date Range</label>
                                <button type="button" class="btn btn-default" id="daterange-btn" >
                                    <span id="drp">
                                        <i class="fa fa-calendar"></i>
                                    </span>
                                    <i class="fa fa-caret-down"></i>
                                </button>
                            </div>

                            <div class="col-md-3"  style="margin-left: 5px;">
                                <label for="status">Status</label>
                                <select class="form-control select2" name="status" id="status">
                                    <option value="all" {{ ($status =='all') ? 'selected' : '' }} >All</option>
                                    @foreach($dispute_status as $dispute)
                                      <option value="{{ $dispute->status }}" {{ ($dispute->status == $status) ? 'selected' : '' }}>
                                        {{
                                            (
                                                ($dispute->status == 'Reject') ? "Rejected" :
                                                (
                                                    ($dispute->status == 'Solve') ? "Solved" : $dispute->status
                                                )
                                            )
                                        }}
                                    </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-3" style="margin-left: 5px;">
                                <label>User</label>
                                <input id="user_input" type="text" name="user" placeholder="Enter Name" class="form-control" value="{{ empty($user) ?  $user : $getName->first_name.' '.$getName->last_name }}"
                                    {{  isset($getName) && ($getName->id == $user) ? 'selected' : '' }}>
                                <span id="error-user"></span>
                            </div>
                            <div class="col-md-2">
                                <div class="input-group" style="margin-top: 25px;">
                                   <button type="submit" name="btn" class="btn btn-primary btn-flat" id="btn">Filter</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="box box-default">
        <div class="box-body">
            <div class="row">
                <div class="col-md-12">
                    <div class="top-bar-title padding-bottom">Disputes</div>
                </div>
            </div>
        </div>
    </div>

    @if ($summary)
        <div class="box">
            <div class="box-body">
                <div class="row">
                    @foreach($summary as $data)
                        <div class="col-md-2">
                            <div class="panel panel-primary">
                                <div class="panel-body text-center">
                                    <span style="font-size: 20px;">{{ $data->total_status }}</span><br>
                                      @if($data->status =='Open')
                                        <span class="text-primary" style="font-weight: bold;">Open</span>
                                      @elseif($data->status =='Solve')
                                        <span class="text-success" style="font-weight: bold;">Solved</span>
                                      @elseif($data->status =='Close')
                                        <span class="text-danger" style="font-weight: bold;">Closed</span>
                                      @endif
                                </div>
                             </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @endif

    <div class="box">
        <div class="box-body">
            <div class="row">
                <div class="col-md-12">
                    <div class="table-responsive">
                        {!! $dataTable->table(['class' => 'table table-striped table-hover dt-responsive', 'width' => '100%', 'cellspacing' => '0']) !!}
                    </div>
                </div>
            </div>
        </div>
    </div>
    @include('admin.layouts.partials.message_boxes')
@endsection

@push('extra_body_scripts')

<!-- Bootstrap daterangepicker -->
<script src="{{ asset('public/backend/bootstrap-daterangepicker/daterangepicker.js') }}" type="text/javascript"></script>

<!-- jquery.dataTables js -->
<script src="{{ asset('public/backend/DataTables_latest/DataTables-1.10.18/js/jquery.dataTables.min.js') }}" type="text/javascript"></script>
<script src="{{ asset('public/backend/DataTables_latest/Responsive-2.2.2/js/dataTables.responsive.min.js') }}" type="text/javascript"></script>

<!-- jquery-ui-1.12.1 -->
<script src="{{ asset('public/backend/jquery-ui-1.12.1/jquery-ui.min.js') }}" type="text/javascript"></script>

{!! $dataTable->scripts() !!}

<script type="text/javascript">
    $(".select2").select2({});

    var sDate;
    var eDate;

    //Date range as a button
    $('#daterange-btn').daterangepicker(
        {
            ranges   : {
              'Today'       : [moment(), moment()],
              'Yesterday'   : [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
              'Last 7 Days' : [moment().subtract(6, 'days'), moment()],
              'Last 30 Days': [moment().subtract(29, 'days'), moment()],
              'This Month'  : [moment().startOf('month'), moment().endOf('month')],
              'Last Month'  : [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')]
             },
          startDate: moment().subtract(29, 'days'),
          endDate  : moment()
        },
        function (start, end)
        {
            var sessionDate      = '{{Session::get('date_format_type')}}';
            var sessionDateFinal = sessionDate.toUpperCase();

            sDate = moment(start, 'MMMM D, YYYY').format(sessionDateFinal);
            $('#startfrom').val(sDate);

            eDate = moment(end, 'MMMM D, YYYY').format(sessionDateFinal);
            $('#endto').val(eDate);

            $('#daterange-btn span').html('&nbsp;' + sDate + ' - ' + eDate + '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;');
        }
    )

    $(document).ready(function()
    {
        $("#daterange-btn").mouseover(function() {
            $(this).css('background-color', 'white');
            $(this).css('border-color', 'grey !important');
        });

        var startDate = "{!! $from !!}";
        var endDate   = "{!! $to !!}";
        // alert(startDate);

        if (startDate == '') {
            $('#daterange-btn span').html('<i class="fa fa-calendar"></i> &nbsp;&nbsp; Pick a date range &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;');
        } else {
            $('#daterange-btn span').html(startDate + ' - ' +endDate + '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;');
        }

        $("#user_input").on('keyup keypress', function(e)
        {
            if (e.type=="keyup" || e.type=="keypress")
            {
                var user_input = $('form').find("input[type='text']").val();
                if(user_input.length === 0)
                {
                    $('#user_id').val('');
                    $('#error-user').html('');
                    $('form').find("button[type='submit']").prop('disabled',false);
                }
            }
        });

        $('#user_input').autocomplete(
        {
            source:function(req,res)
            {
                if (req.term.length > 0)
                {
                    $.ajax({
                        url:'{{url('admin/disputes_user_search')}}',
                        dataType:'json',
                        type:'get',
                        data:{
                            search:req.term
                        },
                        success:function (response)
                        {
                            // console.log(response);
                            // console.log(req.term.length);

                            $('form').find("button[type='submit']").prop('disabled',true);

                            if(response.status == 'success')
                            {
                                res($.map(response.data, function (item)
                                {
                                    return {
                                            id : item.id,
                                            first_name : item.first_name,
                                            last_name : item.last_name,
                                            value: item.first_name + ' ' + item.last_name
                                        }
                                    }
                                ));
                            }
                            else if(response.status == 'fail')
                            {
                                $('#error-user').addClass('text-danger').html('User Does Not Exist!');
                            }
                        }
                    })
                }
                else
                {
                    // console.log(req.term.length);
                    $('#user_id').val('');
                }
            },
            select: function (event, ui)
            {
                var e = ui.item;

                $('#error-user').html('');

                $('#user_id').val(e.id);

                $('form').find("button[type='submit']").prop('disabled',false);
            },
            minLength: 0,
            autoFocus: true
        });
    });
</script>

@endpush