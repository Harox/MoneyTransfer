@php
    $walletCurrencyCode = strtoupper($walletCurrencyCode);
@endphp

@extends('user_dashboard.layouts.app')

@section('css')
    <style>
        @media only screen and (max-width: 206px) {
            .chart-list ul li.active a {
                padding-bottom: 0;
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

                    <form method="POST" action="{{ url('crpto/send/confirm') }}" id="crypto-send-form" accept-charset='UTF-8'>
                        <input type="hidden" value="{{csrf_token()}}" name="_token" id="token"/>
                        <input type="hidden" name="walletCurrencyCode" value="{{ encrypt($walletCurrencyCode) }}"/>
                        <input type="hidden" name="walletId" value="{{ encrypt($walletId) }}"/>
                        <input type="hidden" name="senderAddress" value="{{ encrypt($senderAddress) }}"/>

                        <div class="card">
                            <div class="card-header">
                                <div class="chart-list float-left">
                                    <ul>
                                        <li class="active"><a href="#">@lang('message.dashboard.right-table.crypto-send') {{ $walletCurrencyCode }}</a></li>
                                    </ul>
                                </div>
                            </div>

                            <div class="wap-wed mt20 mb20">
                                <!-- Address -->
                                <div class="form-group">
                                    <label>@lang('message.dashboard.crypto.send.create.recipient-address-input-label-text')</label>
                                    <input type="text" class="form-control receiverAddress" value="" name="receiverAddress" id="receiverAddress"
                                    placeholder="@lang('message.dashboard.crypto.send.create.recipient-address-input-placeholder-text-1') {{ $walletCurrencyCode }} @lang('message.dashboard.crypto.send.create.recipient-address-input-placeholder-text-2')"
                                    onkeyup="this.value = this.value.replace(/\s/g, '')"/>
                                    <span class="receiver-address-validation-error" style="color: red;font-weight: bold"></span>
                                    <h4 class="form-text text-muted"><b>*@lang('message.dashboard.crypto.send.create.amount-warning-text-4').</b></h4>
                                    <small class="form-text text-muted"><b>*@lang('message.dashboard.crypto.send.create.address-qr-code-foot-text-1') {{ $walletCurrencyCode }} @lang('message.dashboard.crypto.receive.address-qr-code-foot-text-2')</b>, @lang('message.dashboard.crypto.receive.address-qr-code-foot-text-3').</small>
                                </div>

                                <!-- Amount -->
                                <div class="form-group">
                                    <label for="exampleInputPassword1">@lang('message.dashboard.send-request.common.amount')</label>
                                    <input type="text" class="form-control amount" name="amount" placeholder="0.00000000" type="text" id="amount" onkeyup="this.value = this.value.replace(/^\.|[^\d\.]/g, '')"
                                    oninput="restrictNumberToEightdecimals(this)"/>
                                    <span class="amount-validation-error" style="color: red;font-weight: bold"></span>
                                    @if ($walletCurrencyCode == 'DOGE' || $walletCurrencyCode == 'DOGETEST')
                                        <small class="form-text text-muted"><b>*@lang('message.dashboard.crypto.send.create.amount-warning-text-1') 2 {{ $walletCurrencyCode }}.</b></small>
                                        <small class="form-text text-muted"><b>*@lang('message.dashboard.crypto.send.create.amount-warning-text-2') 1 {{ $walletCurrencyCode }} @lang('message.dashboard.crypto.send.create.amount-warning-text-3').</b></small>
                                    @elseif ($walletCurrencyCode == 'BTC' || $walletCurrencyCode == 'BTCTEST')
                                        <small class="form-text text-muted"><b>*@lang('message.dashboard.crypto.send.create.amount-warning-text-1') 0.00002 {{ $walletCurrencyCode }}.</b></small>
                                        <small class="form-text text-muted"><b>*@lang('message.dashboard.crypto.send.create.amount-warning-text-2') 0.0002 {{ $walletCurrencyCode }} @lang('message.dashboard.crypto.send.create.amount-warning-text-3').</b></small>
                                    @elseif ($walletCurrencyCode == 'LTC' || $walletCurrencyCode == 'LTCTEST')
                                        <small class="form-text text-muted"><b>*@lang('message.dashboard.crypto.send.create.amount-warning-text-1') 0.0002 {{ $walletCurrencyCode }}.</b></small>
                                        <small class="form-text text-muted"><b>*@lang('message.dashboard.crypto.send.create.amount-warning-text-2') 0.0001 {{ $walletCurrencyCode }} @lang('message.dashboard.crypto.send.create.amount-warning-text-3').</b></small>
                                    @endif
                                    <small class="form-text text-muted"><b>*@lang('message.dashboard.crypto.send.create.amount-allowed-decimal-text').</b></small>
                                </div>
                            </div>

                            <div class="card-footer">
                                <button type="submit" class="btn btn-cust col-12" id="crypto-send-submit-btn">
                                    <i class="spinner fa fa-spinner fa-spin" style="display: none;"></i> <span id="crypto-send-submit-btn-txt" style="font-weight: bolder;">@lang('message.dashboard.right-table.crypto-send')</span>
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
                <!--/col-->
            </div>
            <!--/row-->
        </div>
    </section>
@endsection

@section('js')

<script src="{{asset('public/user_dashboard/js/jquery.validate.min.js')}}" type="text/javascript"></script>

<script src="{{asset('public/user_dashboard/js/sweetalert/sweetalert-unpkg.min.js')}}" type="text/javascript"></script>

<!-- restrictNumberToEightdecimals -->
@include('common.restrict_number_to_eight_decimal')

<script type="text/javascript">

    //Get wallet currency code
    var walletCurrencyCode = '{{ $walletCurrencyCode }}';
    var senderAddress = '{{ $senderAddress }}';
    var receiverAddress;
    var amount;
    var receiverAddressErrorFlag = false;
    var amountErrorFlag = false;
    var receiverAddressValidationError = $('.receiver-address-validation-error');
    var amountValidationError = $('.amount-validation-error');
    // var hasContentReceiverAddressValidationError = receiverAddressValidationError.text();
    // var hasContentAmountValidationError = amountValidationError.text();

    /**
    * [check submit button should be disabled or not]
    * return {void}
    */
    function checkSubmitBtn()
    {
        if (!receiverAddressErrorFlag && !amountErrorFlag)
        {
            $("#crypto-send-submit-btn").attr("disabled", false);
        }
        else
        {
            $("#crypto-send-submit-btn").attr("disabled", true);
        }
    }

    /**
    * [Check Address Validity]
    * return promise
    */
    function checkAddressValidity(receiverAddress, walletCurrencyCode)
    {
        return new Promise(function(resolve, reject)
        {
            $.ajax(
            {
                method: "GET",
                url: SITE_URL + "/crpto/send/validate-address",
                dataType: "json",
                data:
                {
                    'receiverAddress': receiverAddress,
                    'walletCurrencyCode': walletCurrencyCode,
                }
            })
            .done(function(res)
            {
                if (res.status != 200)
                {
                    receiverAddressValidationError.text(res.message);
                    receiverAddressErrorFlag = true;
                    checkSubmitBtn();
                }
                else
                {
                    receiverAddressValidationError.text('');
                    receiverAddressErrorFlag = false;
                    checkSubmitBtn();
                }
                resolve(res.status);
            })
            .fail(function(err)
            {
                console.log(err);

                err.responseText.hasOwnProperty('message') == true ? alert(JSON.parse(err.responseText).message) : alert(err.responseText);
                reject(err);
                return false;
            });
        });
    }

    /**
    * [Check Minimum Amount]
    * return {void}
    */
    function checkMinimumAmount(message)
    {
        amountValidationError.text(message);
        receiverAddressErrorFlag = true;
        amountErrorFlag = true;
        checkSubmitBtn();
    }

    /**
    * [Check Amount Validity]
    * return {void}
    */
    function checkAmountValidity(amount, senderAddress, receiverAddress, walletCurrencyCode)
    {
        return new Promise(function(resolve, reject)
        {
            // TODO: translation
            if ((walletCurrencyCode == 'DOGE' || walletCurrencyCode == 'DOGETEST') && amount < 2)
            {
                checkMinimumAmount(`{{__('The minimum amount must be')}} 2 ${walletCurrencyCode}`)
            }
            else if ((walletCurrencyCode == 'BTC' || walletCurrencyCode == 'BTCTEST') && amount < 0.00002)
            {
                checkMinimumAmount(`{{__('The minimum amount must be')}} 0.00002 ${walletCurrencyCode}`)
            }
            else if ((walletCurrencyCode == 'LTC' || walletCurrencyCode == 'LTCTEST') && amount < 0.0002)
            {
                checkMinimumAmount(`{{__('The minimum amount must be')}} 0.0002 ${walletCurrencyCode}`)
            }
            else
            {
                $.ajax(
                {
                    method: "GET",
                    url: SITE_URL + "/crpto/send/validate-user-balance",
                    dataType: "json",
                    data:
                    {
                        'amount': amount,
                        'senderAddress': senderAddress,
                        'receiverAddress': receiverAddress,
                        'walletCurrencyCode': walletCurrencyCode,
                    },
                })
                .done(function(res)
                {
                    // console.log(res.status);

                    if (res.status == 400)
                    {
                        amountValidationError.text(res.message);
                        amountErrorFlag = true;
                        checkSubmitBtn();
                    }
                    else
                    {
                        amountValidationError.text('');
                        amountErrorFlag = false;
                        checkSubmitBtn();
                    }
                    resolve();
                })
                .fail(function(err)
                {
                    console.log(err);

                    err.responseText.hasOwnProperty('message') == true ? alert(JSON.parse(err.responseText).message) : alert(err.responseText);
                    reject(err);
                    return false;
                });
            }
        });
    }

    $(window).on('load', function (e)
    {
        var previousUserCrytoSentUrl = window.localStorage.getItem("previousUserCrytoSentUrl");
        var userConfirmationCryptoSentUrl = SITE_URL+'/crpto/send/confirm';
        var userCryptoSendAmount = window.localStorage.getItem('user-crypto-sent-amount');
        var userCryptoReceiverAddress = window.localStorage.getItem('user-crypto-receiver-address');
        if ((userConfirmationCryptoSentUrl == previousUserCrytoSentUrl) && userCryptoSendAmount != null && userCryptoReceiverAddress != null)
        {
            swal("{{__('Please Wait')}}", "{{__('Loading...')}}", {
                closeOnClickOutside: false,
                closeOnEsc: false,
                buttons: false,
            });

            $('.amount').val(userCryptoSendAmount);
            $('.receiverAddress').val(userCryptoReceiverAddress);

            //Get network fees
            checkAmountValidity($('.amount').val().trim(), senderAddress, $(".receiverAddress").val().trim(), walletCurrencyCode)
            .then(() =>
            {
                $("#crypto-send-submit-btn").attr("disabled", false);
                $(".spinner").hide();
                $("#crypto-send-submit-btn-txt").html("{{__('Send')}}");
                window.localStorage.removeItem('user-crypto-sent-amount');
                window.localStorage.removeItem('user-crypto-receiver-address');
                window.localStorage.removeItem('previousUserCrytoSentUrl');
                swal.close();
            })
            .catch(error => {
                console.log(error);
            });
        }
    });

    //Validate Address
    $(document).on('blur', ".receiverAddress", function ()
    {
        //Get address
        receiverAddress = $(this).val().trim();
        amount = $('.amount').val().trim();
        if (receiverAddress.length == 0)
        {
            receiverAddressValidationError.text('');
            receiverAddressErrorFlag = false;
            checkSubmitBtn();
        }
        else
        {
            checkAddressValidity(receiverAddress, walletCurrencyCode)
            .then(res =>
            {
                //If amount is not empty and response is 200
                if (amount.length > 0 && !isNaN(amount) && res == 200)
                {
                    checkAmountValidity(amount, senderAddress, receiverAddress, walletCurrencyCode)
                }
            })
            .catch(error => {
                console.log(error);
            });
        }
    });

    //Validate Amount
    $(document).on('blur', '.amount', function ()
    {
        //Get amount
        amount = $(this).val().trim();
        receiverAddress = $(".receiverAddress").val().trim();
        if (amount.length > 0 && receiverAddress.length > 0 && !isNaN(amount))
        {
            checkAmountValidity(amount, senderAddress, receiverAddress, walletCurrencyCode).then(res =>
            {
                if (receiverAddress != '' && res == 200)
                {
                    checkAddressValidity(receiverAddress, walletCurrencyCode)
                }
            })
            .catch(error => {
                console.log(error);
            });
        }
        else
        {
            amountValidationError.text('');
            amountErrorFlag = false;
            checkSubmitBtn();
        }
    });

    jQuery.extend(jQuery.validator.messages, {
        required: "{{__('This field is required.')}}",
        number: "{{__('Please enter a valid number.')}}",
    })

    $('#crypto-send-form').validate({
        rules: {
            receiverAddress: {
                required: true,
            },
            amount: {
                required: true,
                number: true,
            },
        },
        submitHandler: function (form)
        {
            //Set amount to localstorage for showing on create page on going back from confirm page
            window.localStorage.setItem("user-crypto-sent-amount", $('.amount').val().trim());
            window.localStorage.setItem("user-crypto-receiver-address", $(".receiverAddress").val().trim());

            $(".spinner").show();
            $("#crypto-send-submit-btn").attr("disabled", true);
            $("#crypto-send-submit-btn-txt").text("{{__('Sending')}}...");
            form.submit();
        }
    });

</script>

@endsection