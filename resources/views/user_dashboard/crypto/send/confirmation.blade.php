@php
	$amount = number_format($cryptoTrx['amount'], 8, '.', '');
	$networkFee = $cryptoTrx['networkFee'];
	$total = number_format($cryptoTrx['amount'] + $cryptoTrx['networkFee'], 8, '.', '');
@endphp

@extends('user_dashboard.layouts.app')

@section('css')
    <style>
        @media only screen and (max-width: 260px) {
            .pull-right {
            	float: left;
            }
        }
        @media only screen and (max-width: 320px) {
              .crypto-send-confirm-text {
                  font-size: 12px !important;
              }
          }
    </style>
@endsection

@section('content')
	<section class="section-06 history padding-30">
		<div class="container">
			<div class="row">
				<div class="col-md-9 col-xs-12 mb20 marginTopPlus">
					<div class="card">

						<div class="card-header">
							<h4>@lang('message.dashboard.right-table.crypto-send') {{ $walletCurrencyCode }}</h4>
						</div>
						<div class="wap-wed mt20 mb20">
							<p>
								<div style="float: left;">
									@lang('message.dashboard.crypto.send.confirm.about-to-send-text-1') {!! $walletCurrencyCode !!} @lang('message.dashboard.crypto.send.confirm.about-to-send-text-2') &nbsp;
								</div>
								<div style="word-break: break-word !important;">
									<strong>{!! $cryptoTrx['receiverAddress'] !!}</strong>
								</div>
							</p>

							<div><strong style="position: relative;top: 10px;">@lang('message.dashboard.confirmation.details')</strong></div>

							<div class="row mt20">
								<div class="col-md-12">
									<div class="pull-left">@lang('message.dashboard.crypto.send.confirm.sent-amount')</div>
									<div class="pull-right"><strong>{{ moneyFormat($cryptoTrx['currencySymbol'], $amount) }}</strong></div>
								</div>
							</div>
							<div class="row mt10">
								<div class="col-md-12">
									<div class="pull-left">@lang('message.dashboard.crypto.send.confirm.network-fee')</div>
									<div class="pull-right text-right"><strong>{{ moneyFormat($cryptoTrx['currencySymbol'], $networkFee) }}</strong></div>
								</div>
							</div>
							<hr />
							<div class="row">
								<div class="col-md-12">
									<div class="pull-left h6"><strong>@lang('message.dashboard.confirmation.total')</strong></div>
									<div class="pull-right text-right"><strong>{{ moneyFormat($cryptoTrx['currencySymbol'], $total) }}</strong></div>
								</div>
							</div>
						</div>
						<div class="card-footer">
							<div class="text-center">
								<a href="#" class="crypto-send-confirm-back-link">
								 	<button class="btn btn-cust float-left crypt-send-confirm-back-button"><strong><i class="fa fa-angle-left"></i>&nbsp;&nbsp;@lang('message.dashboard.button.back')</strong></button>
								</a>
								<a href="{{url('crpto/send/success')}}" class="crypto-send-confirm-link">
									<button class="btn btn-cust float-right crypto-send-confirm">
								    	<i class="fa fa-spinner fa-spin" style="display: none;" id="spinner"></i>
								    	<strong>
								    		<span class="crypto-send-confirm-text">
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

<script type="text/javascript">

	function userCryptoSendConfirmBack()
    {
        window.localStorage.setItem("previousUserCrytoSentUrl",document.URL);
        var urlToUserCryptoSendCreate = '{{ url("/crpto/send/".encrypt(strtolower($walletCurrencyCode))."/".encrypt($walletId)) }}';
        window.location.replace(urlToUserCryptoSendCreate);
    }

    //Only go back by back button, if submit button is not clicked
    $(document).on('click', '.crypt-send-confirm-back-button', function (e)
    {
    	e.preventDefault();
        userCryptoSendConfirmBack();
    });


	$(document).on('click', '.crypto-send-confirm', function (e)
    {
    	window.localStorage.removeItem('user-crypto-sent-amount');
    	window.localStorage.removeItem('user-crypto-receiver-address');

    	$(".fa-spin").show()
    	$('.crypto-send-confirm-text').text("{{__('Confirming...')}}");
    	$(this).attr("disabled", true);
    	$('.crypto-send-confirm-link').click(function (e)
        {
            e.preventDefault();
        });

        //Make back button disabled and prevent click
        $('.crypt-send-confirm-back-button').attr("disabled", true).click(function (e)
        {
            e.preventDefault();
        });

        //Make back anchor prevent click
    	$('.send-money-confirm-back-link').click(function (e)
        {
            e.preventDefault();
        });
    });


</script>

@endsection
