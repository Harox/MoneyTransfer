<!doctype html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="description" content="{{!isset($exception) ? meta(Route::current()->uri(),'description'):$exception->description}}">
        <meta name="keywords" content="{{!isset($exception) ? meta(Route::current()->uri(),'keyword'):$exception->keyword}}">
        <title>{{!isset($exception) ? meta(Route::current()->uri(),'title'):$exception->title}} <?= isset($additionalTitle)?'| '.$additionalTitle :'' ?></title>
        <script src="{{asset('public/user_dashboard/js/jquery.min.js')}}" type="text/javascript"></script>
        @include('user_dashboard.layouts.common.style')
        <link rel="javascript" href="{{asset('public/frontend/js/respond.js')}}">

        <!---favicon-->
        @if (!empty(getfavicon()))
            <link rel="shortcut icon" href="{{asset('public/images/logos/'.getfavicon())}}" />
        @endif

        @include('user_dashboard.layouts.common.style')
    </head>
    <body>
        <div class="container">
            <div class="row">
                <div class="col-md-6 offset-md-3 marginTopPlus">
                    <h3 style="margin-bottom:15px;">{{$transInfo->app->merchant->user->first_name}} {{$transInfo->app->merchant->user->last_name}}'s {{$transInfo->app->merchant->business_name}} </h3>
                    <div class="card">
                        <div class="card-header">
                            <h4>@lang('message.express-payment.pay-with') {{ $transInfo->payment_method }}</h4>
                        </div>

                        <div class="wap-wed mt20 mb20">
                            <p class="mb20"><strong>@lang('message.express-payment.about-to-make')&nbsp;{{ $transInfo->payment_method }}&nbsp;<strong>
                            </strong></strong></p>

                            <div class="h5"><strong>@lang('message.dashboard.left-table.details')</strong></div>
                            <div class="row mt20">
                                <div class="col-md-6">@lang('message.dashboard.left-table.amount')</div>
                                <div class="col-md-6 text-right">
                                    <strong>{{$currSymbol}} {{ formatNumber($transInfo->amount) }}</strong>
                                </div>
                            </div>
                            <br>
                        </div>

                        <div class="card-footer">
                            <div style="float: left;">
                                <form action="{{ url('merchant/payment/cancel') }}" method="get">
                                    <button class="btn btn-cust express-payment-confirm-back-btn">
                                        <strong><i class="fa fa-angle-left"></i>&nbsp;&nbsp;@lang('message.form.cancel')</strong>
                                    </button>
                                </form>
                            </div>

                            <div style="float: right;">
                                <form action="{{url('merchant/payment/confirm')}}" method="get" id="express-payment-confirm-form">
                                    <button type="button" class="btn btn-cust express-payment-confirm-submit-btn">
                                      <i class="spinner fa fa-spinner fa-spin" style="display: none;"></i>
                                      <span class="express-payment-submit-btn-txt" style="font-weight: bolder;">
                                        <strong>@lang('message.dashboard.button.confirm') &nbsp; <i class="fa fa-angle-right"></i></strong>
                                      </span>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </body>
</html>

<script type="text/javascript">
    $(document).on('click', '.express-payment-confirm-submit-btn', function (e)
    {
        e.preventDefault();
        $('.express-payment-confirm-submit-btn, .express-payment-confirm-back-btn').attr("disabled", true).click(function (e)
        {
            e.preventDefault();
        });
        $(".fa-spin").show();
        $('#express-payment-confirm-form').submit();
    });
</script>