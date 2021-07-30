@extends('user_dashboard.layouts.app')
@section('content')
    <section class="section-06 history padding-30">
        <div class="container">
            <div class="row">
                <div class="col-md-12 col-xs-12  marginTopPlus">
                    <div class="wap-wed mt20 mb20">
                        <div class="text-center">
                            <div class="h3 mt10 text-danger">
                                <div class="alert alert-danger">
                                    <i class="fa fa-flag"></i> <strong>{{ __('Disabled') }}!</strong>
                                    <hr class="message-inner-separator">
                                    <p>@lang('message.dashboard.crypto.preference-disabled').</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection

@section('js')
    <script type="text/javascript">
        $(document).on('click', '.preference-link', function(e)
        {
            e.preventDefault();
            window.localStorage.setItem("crypto-disabled", true);
            window.location.href = "{{ url('admin/settings/preference') }}";
        });
    </script>
@endsection