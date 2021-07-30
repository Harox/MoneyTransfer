@extends('user_dashboard.layouts.app')
@section('content')
    <section class="section-06 history padding-30">
        <div class="container">
            <div class="row">
                <div class="col-md-7 col-xs-12 mb20 marginTopPlus">
                    <div class="card">
                        <div class="card-header">
                           <h4>@lang('message.dashboard.deposit.title')</h4>
                        </div>

                        <form action="{{ url('deposit/bank-payment') }}" style="display: block;" method="POST" accept-charset="UTF-8" id="bank_deposit_form" enctype="multipart/form-data">

                            <input value="{{csrf_token()}}" name="_token" id="token" type="hidden">
                            <input value="{{$transInfo['payment_method']}}" name="method" id="method" type="hidden">
                            <input value="{{$transInfo['totalAmount']}}" name="amount" id="amount" type="hidden">

                            <div class="wap-wed mt20 mb20">
                                <div>
                                    <div class="form-group">
                                        <label for="bank" class="h6"><strong>@lang('message.dashboard.deposit.select-bank')</strong></label>
                                        <select class="form-control bank" name="bank" id="bank">
                                            @foreach($banks as $bank)
                                              <option value="{{ $bank['id'] }}" {{ isset($bank['is_default']) && $bank['is_default'] == 'Yes' ? "selected" : "" }}>{{ $bank['bank_name'] }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>

                                <div class="card">
                                  <div class="card-body">

                                    <div class="container">

                                        @if ($bank['account_name'])
                                        <div class="row">
                                            <div class="col-sm">
                                                <p class="form-control-static">@lang('message.dashboard.left-table.bank-transfer.bank-account-name')</p>
                                            </div>
                                            <div class="col-sm">
                                                <p class="form-control-static" id="account_name">{{  $bank['account_name'] }}</p>
                                            </div>
                                        </div>
                                        @endif

                                        <br>

                                        @if ($bank['account_number'])
                                            <div class="row">
                                                <div class="col-sm">
                                                  <p class="form-control-static">@lang('message.dashboard.left-table.bank-transfer.bank-account-number')</p>
                                                </div>
                                                <div class="col-sm">
                                                  <p class="form-control-static" id="account_number">{{  $bank['account_number'] }}</p>
                                                </div>
                                            </div>
                                        @endif

                                        <br>

                                        @if ($bank['bank_name'])
                                            <div class="row">
                                                <div class="col-sm">
                                                  <p class="form-control-static">@lang('message.dashboard.left-table.bank-transfer.bank-name')</p>
                                                </div>
                                                <div class="col-sm">
                                                  <p class="form-control-static" id="bank_name">{{  $bank['bank_name'] }}</p>
                                                </div>
                                            </div>
                                        @endif

                                    </div>
                                  </div>
                                </div>

                                <br>

                                <div id="attached_file">
                                    <div class="form-group">
                                        <label for="bank" class="h6"><strong>@lang('message.dashboard.payout.payout-setting.modal.attached-file')</strong></label>
                                        <input type="file" name="attached_file" class="form-control input-file-field" data-rel="">
                                    </div>
                                </div>

                                <!--bank logo-->
                                <p class="mb20">@lang('message.dashboard.deposit.deposit-via')&nbsp;&nbsp;
                                    <span id="bank_logo"></span>
                                </p>

                                <div class="h5"><strong>@lang('message.dashboard.confirmation.details')</strong></div>

                                <div class="row mt20">
                                    <div class="col-md-6">@lang('message.dashboard.deposit.deposit-amount')</div>
                                    <div class="col-md-6 text-right"><strong>{{ moneyFormat($transInfo['currSymbol'], formatNumber($transInfo['amount'])) }}</strong></div>
                                </div>

                                <div class="row mt10">
                                    <div class="col-md-6">@lang('message.dashboard.confirmation.fee')</div>
                                    <div class="col-md-6 text-right"><strong>{{ moneyFormat($transInfo['currSymbol'], formatNumber($transInfo['fee'])) }}</strong></div>
                                </div>
                                <hr />

                                <div class="row">
                                    <div class="col-md-6 h6"><strong>@lang('message.dashboard.confirmation.total')</strong></div>
                                    <div class="col-md-6 text-right"><strong>{{ moneyFormat($transInfo['currSymbol'], formatNumber($transInfo['totalAmount'])) }}</strong></div>
                                </div>
                            </div>

                            <div class="card-footer" style="margin-left: 0 auto;">
                                <div style="float: left;">
                                    <a href="#" class="deposit-bank-confirm-back-link">
                                        <button class="btn btn-cust deposit-bank-confirm-back-btn"><strong><i class="fa fa-angle-left"></i>&nbsp;&nbsp;@lang('message.dashboard.button.back')</strong></button>
                                    </a>
                                </div>
                                <div style="float: right;">
                                    <button type="submit" class="btn btn-cust" id="deposit-money">
                                        <i class="spinner fa fa-spinner fa-spin" style="display: none;"></i> <span id="deposit-money-text">@lang('message.dashboard.button.confirm')&nbsp; <i class="fa fa-angle-right"></i></span>
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
                <!--/col-->
            <!--/row-->
        </div>
    </section>
@include('user_dashboard.layouts.common.help')
@endsection


@section('js')

<script src="{{asset('public/user_dashboard/js/jquery.validate.min.js')}}" type="text/javascript"></script>
<script src="{{asset('public/user_dashboard/js/additional-methods.min.js')}}" type="text/javascript"></script>
<script>

    function depositBankBack()
    {
        localStorage.setItem("depositConfirmPreviousUrl",document.URL);
        window.history.back();
    }

    function getBanks()
    {
        var bank = $('#bank').val();
        if (bank)
        {
            $.ajax({
                headers:
                {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                method: "POST",
                url: SITE_URL+"/deposit/bank-payment/get-bank-detail",
                dataType: "json",
                cache: false,
                data: {
                    'bank': bank,
                }
            })
            .done(function(response)
            {
                // log(response);
                if (response.status == true)
                {
                    $('#bank_name').html(response.bank.bank_name);
                    $('#account_name').html(response.bank.account_name);
                    $('#account_number').html(response.bank.account_number);

                    if (response.bank_logo) {
                        $("#bank_logo").html(`<img class="" src="${SITE_URL}/public/uploads/files/bank_logos/${response.bank_logo}" width="120" height="80"/>`);
                    } else {
                        $("#bank_logo").html(`<img class="" src="${SITE_URL}/public/images/payment_gateway/bank.jpg" width="120" height="80"/>`);
                    }
                }
                else
                {
                    $('#bank_name').html('');
                    $('#bank_branch_name').html('');
                    $('#bank_branch_city').html('');
                    $('#bank_branch_address').html('');
                    $('#swift_code').html('');
                    $('#account_name').html('');
                    $('#account_number').html('');
                }
            });
        }
    }

    $(window).on('load',function()
    {
        getBanks();
    });

    $("#bank").change(function()
    {
        getBanks();
    });

    $(document).on('change', '#bank', function()
    {
        getBanks();
    });

    jQuery.extend(jQuery.validator.messages, {
        required: "{{__('This field is required.')}}",
    })

    $('#bank_deposit_form').validate({
        rules: {
            attached_file: {
                required: true,
                extension: "png|jpg|jpeg|gif|bmp|pdf|docx|txt|rtf",
            },
        },
        messages: {
          attached_file: {
            extension: "{{__("Please select (png, jpg, jpeg, gif, bmp, pdf, docx,txt or rtf) file!")}}"
          },
        },
        submitHandler: function(form)
        {
            $("#deposit-money").attr("disabled", true);
            $(".spinner").show();
            var pretext=$("#deposit-money-text").text();
            $("#deposit-money-text").text("{{__('Confirming...')}}");

            //Make back button disabled and prevent click
            $('.deposit-bank-confirm-back-btn').attr("disabled", true).click(function (e)
            {
                e.preventDefault();
            });

            //Make back anchor prevent click
            $('.deposit-bank-confirm-back-link').click(function (e)
            {
                e.preventDefault();
            });

            form.submit();
            setTimeout(function(){
                $("#deposit-money").removeAttr("disabled");
                $(".spinner").hide();
                $("#deposit-money-text").text(pretext);
            },10000);
        }
    });

    //Only go back by back button, if submit button is not clicked
    $(document).on('click', '.deposit-bank-confirm-back-btn', function (e)
    {
        e.preventDefault();
        depositBankBack();
    });

</script>
@endsection