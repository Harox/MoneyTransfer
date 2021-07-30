@extends('user_dashboard.layouts.app')

@section('css')
    <style>
        @media only screen and (max-width: 240px) {
            .pull-right {
            	float: left;
            }
        }
    </style>
@endsection

@section('content')
    <section class="section-06 history padding-30">
        <div class="container">
            <div class="row">
                <div class="col-md-7 col-xs-12 mb20 marginTopPlus">
                    @include('user_dashboard.layouts.common.alert')
                    <div class="card">
                        <div class="card-header">
                            <h4>@lang('message.dashboard.nav-menu.payout')</h4>
                        </div>
                        <div class="wap-wed mt20 mb20">
                            <p class="mb20">
                                @lang('message.dashboard.payout.new-payout.withdraw-via')&nbsp;&nbsp;<img src="{{asset("public/images/payment_gateway")}}/{{strtolower($transInfo['payout_setting']->paymentMethod->name)}}.jpg"/>
                            </p>

                            @if ( isset($transInfo['payout_setting']->paymentMethod) && $transInfo['payout_setting']->paymentMethod->name == 'Bank')
                                <p class="mb20"> @lang('message.dashboard.payout.payout-setting.modal.bank-account-holder-name')&nbsp;&nbsp;: <b>{{ $transInfo['payout_setting']->account_name }}</b></p>
                                <p class="mb20"> @lang('message.dashboard.payout.payout-setting.modal.account-number')&nbsp;&nbsp;: <b>{{ $transInfo['payout_setting']->account_number }}</b></p>
                                <p class="mb20"> @lang('message.dashboard.payout.payout-setting.modal.swift-code')&nbsp;&nbsp;: <b>{{ $transInfo['payout_setting']->swift_code }}</b></p>
                                <p class="mb20"> @lang('message.dashboard.payout.payout-setting.modal.bank-name')&nbsp;&nbsp;: <b>{{ $transInfo['payout_setting']->bank_name }}</b></p>
                            @endif


                            <div class="h5"><strong>@lang('message.dashboard.confirmation.details')</strong></div>
                            <div class="row mt20">
                                <div class="col-md-12">
                                    <div class="pull-left">@lang('message.dashboard.left-table.withdrawal.withdrawan-amount')</div>
                                    <div class="pull-right"><strong>{{  moneyFormat($transInfo['currSymbol'], formatNumber($transInfo['amount'])) }}</strong></div>
                                </div>
                            </div>
                            <div class="row mt10">
                                <div class="col-md-12">
                                    <div class="pull-left">@lang('message.dashboard.confirmation.fee')</div>
                                    <div class="pull-right"><strong>{{  moneyFormat($transInfo['currSymbol'], formatNumber($transInfo['fee'])) }}</strong></div>
                                </div>
                            </div>
                            <hr />
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="pull-left h6"><strong>@lang('message.dashboard.confirmation.total')</strong></div>
                                    <div class="pull-right text-right"><strong>{{  moneyFormat($transInfo['currSymbol'], formatNumber($transInfo['totalAmount'])) }}</strong></div>
                                </div>
                            </div>
                        </div>

                        <div class="card-footer">
                            <div class="text-center">
                                <a href="#" class="withdrawal-confirm-back-link">
                                    <button class="btn btn-cust float-left withdrawal-confirm-back-btn"><strong><i class="fa fa-angle-left"></i>&nbsp;&nbsp;@lang('message.dashboard.button.back')</strong></button>
                                </a>
                                <a href="{{url('withdrawal/confirm-transaction')}}" class="withdrawal-confirm-submit-link">
                                    <button class="btn btn-cust float-right withdrawal-confirm-submit-btn">
                                        <i class="fa fa-spinner fa-spin" style="display: none;" id="spinner"></i>
                                        <strong>
                                            <span class="withdrawal-confirm-submit-btn-txt">
                                                @lang('message.dashboard.button.confirm') &nbsp; <i class="fa fa-angle-right"></i>
                                            </span>
                                        </strong>
                                    </button>
                                </a>
                            </div>
                        </div>
                    </div>
                 </div>
                <!--/col-->
            </div>
            <!--/row-->
        </div>
    </section>
@endsection

@section('js')

<script src="{{asset('public/user_dashboard/js/jquery.validate.min.js')}}" type="text/javascript"></script>
<script>
    function payoutBack()
    {
        localStorage.setItem("payoutConfirmPreviousUrl",document.URL);
        window.history.back();
    }

    $(document).on('click', '.withdrawal-confirm-submit-btn', function (e)
    {
        $(".fa-spin").show()
        $('.withdrawal-confirm-submit-btn-txt').text("{{__('Confirming...')}}");
        $(this).attr("disabled", true);
        $('.withdrawal-confirm-submit-link').click(function (e) {
            e.preventDefault();
        });

        //Make back button disabled and prevent click
        $('.withdrawal-confirm-back-btn').attr("disabled", true).click(function (e)
        {
            e.preventDefault();
        });

        //Make back anchor prevent click
        $('.withdrawal-confirm-back-link').click(function (e)
        {
            e.preventDefault();
        });
    });

    //Only go back by back button, if submit button is not clicked
    $(document).on('click', '.withdrawal-confirm-back-btn', function (e)
    {
        e.preventDefault();
        payoutBack();
    });
</script>

@endsection
