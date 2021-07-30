@extends('admin.layouts.master')
@section('title', 'SMS Settings')

@section('head_style')
    <!-- bootstrap-toggle -->
    <link rel="stylesheet" href="{{ asset('public/backend/bootstrap-toggle/css/bootstrap-toggle.min.css') }}">
@endsection

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
                        <li class="active"><a href="{{ url('admin/settings/sms/twilio') }}">Twilio</a></li>
                        <li><a href="{{ url('admin/settings/sms/nexmo')}}">Nexmo</a></li>
                    </ul>

                    <div class="tab-content">
                        <div class="tab-pane fade in active" id="tab_1">
                            <div class="card">
                                <div class="card-header">
                                    <h4></h4>
                                </div>
                                <div class="container-fluid">
                                    <div class="tab-pane" id="tab_2">

                                        <form action="{{ url('admin/settings/sms/twilio') }}" method="POST" class="form-horizontal" id="twilio_sms_setting_form">
                                            {!! csrf_field() !!}

                                            <input type="hidden" name="type" value="{{ base64_encode($twilio->type) }}">

                                            <div class="box-body">

                                                {{-- Name --}}
                                                <div class="form-group" style="display: none;">
                                                    <div class="col-md-10 col-md-offset-1">
                                                        <label class="col-md-3 control-label">Name</label>
                                                        <div class="col-md-8">
                                                            <input type="text" name="name" class="form-control" value="{{ $twilio->type == 'twilio' ? 'Twilio' : '' }}" placeholder="Enter Twilio Sms Gateway Name" id="" readonly>
                                                            @if ($errors->has('name'))
                                                                <span style="color:red;font-weight:bold;">{{ $errors->first('name') }}</span>
                                                            @endif
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="clearfix"></div>

                                                {{-- Key --}}
                                                <div class="form-group">
                                                    <div class="col-md-10 col-md-offset-1">
                                                        <label class="col-md-3 control-label">Account SID</label>
                                                        <div class="col-md-8">
                                                            <input type="text" name="twilio[account_sid]" class="form-control" value="{{ isset($credentials->account_sid) ? $credentials->account_sid : '' }}" placeholder="Enter Twilio Account SID" id="">
                                                            @if ($errors->has('twilio.account_sid'))
                                                                <span style="color:red;font-weight:bold;">{{ $errors->first('twilio.account_sid') }}</span>
                                                            @endif
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="clearfix"></div>

                                                {{-- Secret --}}
                                                <div class="form-group">
                                                    <div class="col-md-10 col-md-offset-1">
                                                        <label class="col-md-3 control-label">Auth Token</label>
                                                        <div class="col-md-8">
                                                            <input type="text" name="twilio[auth_token]" class="form-control" value="{{ isset($credentials->auth_token) ? $credentials->auth_token : '' }}" placeholder="Enter Twilio Auth Token" id="">
                                                            @if ($errors->has('twilio.auth_token'))
                                                                <span style="color:red;font-weight:bold;">{{ $errors->first('twilio.auth_token') }}</span>
                                                            @endif
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="clearfix"></div>

                                                {{-- Secret --}}
                                                <div class="form-group">
                                                    <div class="col-md-10 col-md-offset-1">
                                                        <label class="col-md-3 control-label">Default Phone Number</label>
                                                        <div class="col-md-8">
                                                            <input type="text" name="twilio[default_twilio_phone_number]" class="form-control"
                                                            value="{{ isset($credentials->default_twilio_phone_number) ? $credentials->default_twilio_phone_number : '' }}" placeholder="Enter Twilio Default Phone Number" id="">
                                                            @if ($errors->has('twilio.default_twilio_phone_number'))
                                                                <span style="color:red;font-weight:bold;">{{ $errors->first('twilio.default_twilio_phone_number') }}</span>
                                                            @endif
                                                            <div class="clearfix"></div>
                                                            <h6 class="form-text text-muted"><strong>*Must enter phone number without(+) symbol.</strong></h6>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="clearfix"></div>

                                                {{-- Status --}}
                                                <div class="form-group">
                                                    <div class="col-md-10 col-md-offset-1">
                                                        <label class="col-md-3 control-label">Status</label>
                                                        <div class="col-md-8">
                                                            <select name="status" class="select2 select2-hidden-accessible" id="">
                                                                <option {{ $twilio->status == 'Active' ? 'selected' : '' }} value="Active">Active</option>
                                                                <option {{ $twilio->status == 'Inactive' ? 'selected' : '' }} value="Inactive">Inactive</option>
                                                            </select>
                                                            @if ($errors->has('status'))
                                                                <span style="color:red;font-weight:bold;">{{ $errors->first('status') }}</span>
                                                            @endif
                                                            <div class="clearfix"></div>
                                                            <h6 class="form-text text-muted"><strong>*Incoming SMS messages might be delayed by {{ ucfirst($twilio->type) }}.</strong></h6>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="clearfix"></div>
                                            </div>

                                            <div class="row">
                                                <div class="col-md-12">
                                                    <div style="margin-top:10px">
                                                        <a id="cancel_anchor" href="{{ url('admin/settings/sms/twilio') }}" class="btn btn-danger btn-flat">Cancel</a>
                                                        <button type="submit" class="btn btn-primary pull-right btn-flat" id="sms-settings-twilio-submit-btn">
                                                            <i class="fa fa-spinner fa-spin" style="display: none;"></i> <span id="sms-settings-twilio-submit-btn-text">Update</span>
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('extra_body_scripts')

    <!-- jquery.validate -->
    <script src="{{ asset('public/dist/js/jquery.validate.min.js') }}" type="text/javascript"></script>

    <script type="text/javascript">

        $(function () {
            $(".select2").select2({
            });
        });

        $.validator.setDefaults({
            highlight: function(element) {
                $(element).parent('div').addClass('has-error');
            },
            unhighlight: function(element) {
                $(element).parent('div').removeClass('has-error');
            },
            errorPlacement: function (error, element) {
                error.insertAfter(element);
            }
        });


        $('#twilio_sms_setting_form').validate({
            rules: {
                "twilio[account_sid]": {
                    required: true,
                },
                "twilio[auth_token]": {
                    required: true,
                },
                "twilio[default_twilio_phone_number]": {
                    required: true,
                    digits: true,
                },
            },
            messages: {
                "twilio[account_sid]": {
                    required: "Twilio Account SID is required!",
                },
                "twilio[auth_token]": {
                    required: "Twilio Auth Token is required!",
                },
                "twilio[default_twilio_phone_number]": {
                    required: "Twilio Default Phone Number is required",
                },
            },
            submitHandler: function(form)
            {
                $("#sms-settings-twilio-submit-btn").attr("disabled", true);
                $(".fa-spin").show();
                $("#sms-settings-twilio-submit-btn-text").text('Updating...');
                $('#cancel_anchor').attr("disabled",true);
                $('#sms-settings-twilio-submit-btn').click(false);
                form.submit();
            }
        });

    </script>
@endpush
