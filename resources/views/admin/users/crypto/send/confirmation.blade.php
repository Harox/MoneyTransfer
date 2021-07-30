@php
    $amount = number_format($cryptoTrx['amount'], 8, '.', '');
    $networkFee = $cryptoTrx['network-fee'];
    $user_id = $cryptoTrx['user_id'];
    $user_full_name = $cryptoTrx['user-full-name'];
    $total = number_format($cryptoTrx['amount'] + $cryptoTrx['network-fee'], 8, '.', '');
@endphp

@extends('admin.layouts.master')

@section('title', 'Crypto Send Confirm')

@section('page_content')

<div class="box">
   <div class="panel-body">
        <ul class="nav nav-tabs cus" role="tablist">
            <li class="active">
              <a href='{{url("admin/users/edit/$user_id")}}'>Profile</a>
            </li>

            <li>
              <a href="{{url("admin/users/transactions/$user_id")}}">Transactions</a>
            </li>
            <li>
              <a href="{{url("admin/users/wallets/$user_id")}}">Wallets</a>
            </li>
            <li>
              <a href="{{url("admin/users/tickets/$user_id")}}">Tickets</a>
            </li>
            <li>
              <a href="{{url("admin/users/disputes/$user_id")}}">Disputes</a>
            </li>
       </ul>
      <div class="clearfix"></div>
   </div>
</div>

<div class="row">
    <div class="col-md-2">
        &nbsp;&nbsp;&nbsp;&nbsp;<button style="margin-top: 15px;"  type="button" class="btn button-secondary btn-flat active">Crypto Send</button>
    </div>
    <div class="col-md-6"></div>
    <div class="col-md-4">
        <div class="pull-right">
            <h3>{{ $user_full_name }}</h3>
        </div>
    </div>
</div>

<div class="box">
    <div class="box-body">
        <div class="row">
            <div class="col-md-12">
                <div class="row">
                    <div class="col-md-7">

                        <div class="panel panel-default">
                            <div class="panel-body">
                                <h3 class="text-center"><strong>Details</strong></h3>
                                <div class="row">
                                    <div class="col-md-6 pull-left">Sent Amount</div>
                                    <div class="col-md-6  text-right"><strong>{{ moneyFormat($cryptoTrx['currency-symbol'], $amount) }}</strong></div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6 pull-left">Network Fee</div>
                                    <div class="col-md-6 text-right"><strong>{{ moneyFormat($cryptoTrx['currency-symbol'], $networkFee) }}</strong></div>
                                </div>
                                <hr />
                                <div class="row">
                                    <div class="col-md-6 pull-left"><strong>Total</strong></div>
                                    <div class="col-md-6 text-right"><strong>{{ moneyFormat($cryptoTrx['currency-symbol'], $total) }}</strong></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-7">
                        <div style="margin-left: 0 auto">
                            <div style="float: left;">
                                <a href="#" class="admin-user-crypto-send-confirm-back-link">
                                    <button class="btn button-secondary admin-user-crypto-send-confirm-back-btn"><strong><i class="fa fa-angle-left"></i>&nbsp;&nbsp;Back</strong></button>
                                </a>
                            </div>
                            <div style="float: right;">
                                <form action="{{ url('admin/users/deposit/crypto/send/success') }}" style="display: block;" method="POST" accept-charset="UTF-8" id="admin-user-crypto-send-confirm" novalidate="novalidate">
                                    <input value="{{csrf_token()}}" name="_token" id="token" type="hidden">
                                    <input type="hidden" name="user_id" value="{{ $user_id }}">

                                    <button type="submit" class="btn button-secondary" id="admin-user-crypto-send-confirm-btn">
                                        <i class="fa fa-spinner fa-spin" style="display: none;"></i>
                                        <span id="admin-user-crypto-send-confirm-btn-text">
                                            <strong>Confirm&nbsp; <i class="fa fa-angle-right"></i></strong>
                                        </span>
                                    </button>
                                </form>
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

    function cryptoSendConfirmBack()
    {
        window.localStorage.setItem("previousCrytoSentUrl",document.URL);
        window.location.replace(SITE_URL+`/admin/users/crypto/send/${'{{ $user_id }}'}`);
    }

    //Only go back by back button, if submit button is not clicked
    $(document).on('click', '.admin-user-crypto-send-confirm-back-btn', function (e)
    {
        e.preventDefault();
        cryptoSendConfirmBack();
    });


    $('#admin-user-crypto-send-confirm').validate({
        submitHandler: function(form)
        {
            window.localStorage.removeItem('crypto-sent-amount');

            $("#admin-user-crypto-send-confirm-btn").attr("disabled", true);
            $(".fa-spin").show();
            var pretext=$("#admin-user-crypto-send-confirm-btn-text").text();
            $("#admin-user-crypto-send-confirm-btn-text").text('Confirming...');

            //Make back button disabled and prevent click
            $('.admin-user-crypto-send-confirm-back-btn').attr("disabled", true).click(function (e)
            {
                e.preventDefault();
            });

            //Make back anchor prevent click
            $('.admin-user-crypto-send-confirm-back-link').click(function (e)
            {
                e.preventDefault();
            });

            form.submit();
            setTimeout(function(){
                $("#admin-user-crypto-send-confirm-btn-text").html(pretext + '<i class="fa fa-angle-right"></i>');
                $("#admin-user-crypto-send-confirm-btn").removeAttr("disabled");
                $(".fa-spin").hide();
            },10000);
        }
    });

</script>
@endpush