@extends('admin.layouts.master')

@section('title', 'Edit Nofication Type')

@section('page_content')

<div class="row">
    <div class="col-md-3 settings_bar_gap">
        @include('admin.common.settings_bar')
    </div>
    <div class="col-md-9">
        <!-- Horizontal Form -->
        <div class="box box-info">
            <div class="box-header with-border text-center">
                <h3 class="box-title">Edit Notification Type</h3>
            </div>

            <!-- form start -->
            <form method="POST" action="{{ url('admin/settings/notification-types/update/'.$notificationType->id) }}" class="form-horizontal" id="edit_notification_form">
                {{ csrf_field() }}

                <input type="hidden" name="notification_type_id" value="{{ base64_encode($notificationType->id) }}" id="notification_type_id">

                <div class="box-body">
                    {{-- Notification Type Name --}}
                    <div class="form-group">
                        <label class="col-sm-3 control-label" for="notification_type_name">Name</label>
                        <div class="col-sm-6">
                            <input type="text" name="notification_type_name" class="form-control" value="{{ $notificationType->name }}" id="notification_type_name" autocomplete="off">
                            <span id="type_error"></span>
                            @if($errors->has('notification_type_name'))
                                <span class="help-block">
                                    <strong class="text-danger">{{ $errors->first('notification_type_name') }}</strong>
                                </span>
                            @endif
                        </div>
                    </div>

                    {{-- Notification Type Status --}}
                    <div class="form-group">
                        <label class="col-sm-3 control-label" for="notification_type_status">Status</label>
                        <div class="col-sm-6">
                            <select class="select2" name="notification_type_status" id="notification_type_status">
                                <option value='Active' {{ isset($notificationType->status) && $notificationType->status == 'Active' ? 'selected':"" }}>Active</option>
                                <option value='Inactive' {{ isset($notificationType->status) && $notificationType->status == 'Inactive' ? 'selected':"" }}>Inactive</option>
                            </select>
                            @if($errors->has('notification_type_status'))
                                <span class="help-block">
                                    <strong class="text-danger">{{ $errors->first('notification_type_status') }}</strong>
                                </span>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="box-footer">
                    <a class="btn btn-danger" href="{{ url('admin/settings/notification-types') }}">Cancel</a>
                    <button type="submit" class="btn btn-primary pull-right" id="updateNotification">&nbsp; Update &nbsp;</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('extra_body_scripts')
    <!-- jquery.validate -->
    <script src="{{ asset('public/dist/js/jquery.validate.min.js') }}" type="text/javascript"></script>

    <script type="text/javascript">

        $(window).on('load', function()
        {
            $(".select2").select2({});
        });

        function checkDuplicateNotificationTypeName()
        {
            //event.preventDefault();
            var notification_type_name = $('#notification_type_name').val();
            var notification_type_id   = $('#notification_type_id').val();
            $.ajax({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                method: "POST",
                url: SITE_URL+"/admin/settings/notification-type-name/check",
                dataType: "json",
                data: {
                    'notification_type_name': notification_type_name,
                    'notification_type_id'  : notification_type_id,
                    '_token':"{{csrf_token()}}"
                }
            })
            .done(function(response) {
                if (response.status == false) {
                    $('#type_error').text(response.fail).css({"font-weight":"bold", "color":"red"});
                    $('form').find("button[type='submit']").prop('disabled',true);
                    $('#updateNotification').prop('disabled',true);
                } else {
                    $('#type_error').text('');
                    $('#updateNotification').prop('disabled',false);
                }
            });
        }

        //Notification Type Name check
        $(document).on('input','#notification_type_name', function()
        {
            checkDuplicateNotificationTypeName();
        });

        $.validator.setDefaults({
            highlight: function(element) {
                $(element).parent('div').addClass('has-error');
            },
            unhighlight: function(element) {
                $(element).parent('div').removeClass('has-error');
            },
        });

        $('#edit_notification_form').validate({
            rules: {
                notification_type_name: {
                    required: true,
                },
                notification_type_status: {
                    required: true,
                },
            },
            messages: {
                notification_type_name: {
                    required: "Please enter notification type name.",
                },
            },
        });

    </script>
@endpush