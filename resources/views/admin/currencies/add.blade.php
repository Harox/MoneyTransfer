@extends('admin.layouts.master')
@section('title', 'Add Currency')

@section('head_style')
    <!-- bootstrap-toggle -->
    <link rel="stylesheet" href="{{ asset('public/backend/bootstrap-toggle/css/bootstrap-toggle.min.css') }}">
@endsection

@section('page_content')
    <div class="row">
        <div class="col-md-12">
            <div class="box box-info">
                <div class="box-header with-border text-center">
                  <h3 class="box-title">Add Currency</h3>
                </div>

                <form action="{{ url('admin/settings/add_currency') }}" method="post" class="form-horizontal" enctype="multipart/form-data" id="add_currency_form">
                    {!! csrf_field() !!}

                    <!-- box-body -->
                    <div class="box-body">
                        <!-- Type -->
                        <div class="form-group" id="currency-type-div">
                            <label class="col-sm-3 control-label" for="inputEmail3">Type</label>
                            <div class="col-sm-6">
                                <!-- Type (Fiat & Crypto) -->
                                @if ($getCurrenciesPreference->value == "fiat_and_crypto")
                                    <select class="form-control type" name="type" id="type">
                                      <option value='fiat'>Fiat</option>
                                      <option value='crypto'>Crypto</option>
                                    </select>
                                    <span class="network-validation-error" style="color: red;font-weight: bold"></span>
                                @else
                                    <!-- Type (Fiat) -->
                                    <input type="hidden" name="type" value="fiat" id="type">
                                    <p class="form-control-static">Fiat</p>
                                @endif
                            </div>
                        </div>

                        <!-- Name -->
                        <div class="form-group" id="name-div">
                            <label class="col-sm-3 control-label" for="inputEmail3">Name</label>
                            <div class="col-sm-6">
                              <input type="text" name="name" class="form-control" value="{{ old('name') }}" placeholder="Name" id="name">
                              <span class="text-danger">{{ $errors->first('name') }}</span>
                            </div>
                        </div>

                        <!-- Code -->
                        <div class="form-group" id="code-div">
                            <label class="col-sm-3 control-label" for="inputEmail3">Code</label>
                            <div class="col-sm-6">
                              <input type="text" name="code" class="form-control" value="{{ old('code') }}" placeholder="Code" id="code">
                              <span class="text-danger">{{ $errors->first('code') }}</span>
                            </div>
                        </div>

                        <!-- Symbol -->
                        <div class="form-group" id="symbol-div">
                            <label class="col-sm-3 control-label" for="inputEmail3">Symbol</label>
                            <div class="col-sm-6">
                              <input type="text" name="symbol" class="form-control" value="{{ old('symbol') }}" placeholder="Symbol" id="symbol">
                              <span class="text-danger">{{ $errors->first('symbol') }}</span>
                            </div>
                        </div>

                        <!-- Exchange Rate -->
                        <div class="form-group" id="exchange-rate-div">
                            <label class="col-sm-3 control-label" for="inputEmail3">Exchange Rate</label>
                            <div class="col-sm-6">
                              <input type="text" name="rate" class="form-control" value="{{ old('rate') }}" placeholder="Rate" id="rate" oninput="restrictNumberToEightdecimals(this)">
                              <span class="text-danger">{{ $errors->first('rate') }}</span>
                              <div class="clearfix"></div>
                              <small class="form-text text-muted"><strong>*Allowed upto 8 decimal places.</strong></small>
                            </div>
                        </div>

                        <!-- Logo -->
                        <div class="form-group" id="logo-div">
                            <label for="inputEmail3" class="col-sm-3 control-label">Logo</label>
                            <div class="col-sm-6">
                              <input type="file" name="logo" class="form-control input-file-field" id="currency-logo">
                              <div class="clearfix"></div>
                              <small class="form-text text-muted"><strong>{{ allowedImageDimension(64,64) }}</strong></small>
                              <div class="setting-img">
                                <img src='{{ url('public/user_dashboard/images/favicon.png') }}' width="64" height="64" id="currency-demo-logo-preview">
                              </div>
                            </div>
                        </div>

                        <!-- Exchange From -->
                        <div class="form-group" id="exchange-from-div">
                            <label class="col-sm-3 control-label" for="inputEmail3">Exchange From</label>
                            <div class="col-sm-6">
                              <select class="form-control exchange_from" name="exchange_from" id="exchange_from">
                                  <option value='local'>Local</option>
                                  <option value='api'>API</option>
                              </select>
                            </div>
                        </div>
                        <!-- /.box-body -->

                        <!-- box-footer -->
                        <div class="box-footer">
                            <button type="button" class="btn btn-danger btn-flat" id="cancel-link">Cancel</button>
                            <button type="submit" class="btn btn-primary btn-flat pull-right" id="currency-add-submit-btn">
                                <i class="fa fa-spinner fa-spin" style="display: none;"></i> <span id="currency-add-submit-btn-text">Submit</span>
                            </button>
                        </div>
                        <!-- /.box-footer -->
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('extra_body_scripts')

<!-- jquery.validate -->
<script src="{{ asset('public/dist/js/jquery.validate.min.js') }}" type="text/javascript"></script>

<!-- jquery.validate additional-methods -->
<script src="{{ asset('public/dist/js/jquery-validation-1.17.0/dist/additional-methods.min.js') }}" type="text/javascript"></script>

<!-- bootstrap-toggle -->
<script src="{{ asset('public/backend/bootstrap-toggle/js/bootstrap-toggle.min.js') }}" type="text/javascript"></script>

<!-- read-file-on-change -->
@include('common.read-file-on-change')

<!-- currency-validations -->
@include('common.currency-validations')

<!-- restrictNumberToEightdecimals -->
@include('common.restrict_number_to_eight_decimal')

<script type="text/javascript">
    /**
     * Will show active crypto currencies settings
     * return {void}
     */
    function getActiveCryptoCurrencySettings()
    {
        $.ajax(
        {
            url: SITE_URL+"/admin/settings/currency/get-active-blockio-crypto-currency-settings",
            type: "get",
            dataType: 'json',
        })
        .done(function(res)
        {
            // console.log(res);
            // console.log(res.cryptoCurrenciesSettings.length);

            if (res.cryptoCurrenciesSettings.length > 0)
            {
                //show name, symbol from, logo, status & create-network-address status div's
                $("#name-div, #symbol-div, #logo-div, #status-div, #create-network-address-div").show();

                //enable cancel link
                $("#cancel-link").click(() => { window.location.href = '{{ url("admin/settings/currency") }}'; });

                //make network validation error empty
                $('.network-validation-error').text('');

                //Add network div if type is crypto
                $('#currency-type-div').after( `<div class="form-group" id="crypto-networks-div">
                    <label class="col-sm-3 control-label" for="inputEmail3">Network</label>
                    <div class="col-sm-6">
                      <select class="form-control network" name="network" id="network">
                      </select>
                    </div>
                </div>`);

                //Create Network(BTC,LTC,DOGE) Address (for all users)
                $('#status-div').after( `<div class="form-group" id="create-network-address-div">
                    <label class="col-sm-3 control-label" for="inputEmail3">Create Addresses</label>
                    <div class="col-sm-6">
                        <input type="checkbox" data-toggle="toggle" name="network_address" id="network-address">
                        <div class="clearfix"></div>
                        <small class="form-text text-muted"><strong>*If On, <span class="network-name"></span> wallet addresses will be created for all registered users.</strong></small>
                    </div>
                </div>`);

                //enable bootstrap toogle for network-address
                $('#network-address').bootstrapToggle({
                  size: 'normal',
                });

                //enable Seelct2 for network select
                $(".network").select2({});

                //load network select options
                let output = '';
                $.each(res.cryptoCurrenciesSettings, function(index, value)
                {
                    output += `
                        <option value="${value.network}">${value.network}</option>
                    `;
                });
                $('#network').html(output);

                //Show name, symbol (readonly) based on network
                let network = $('#network option:selected').text();
                $('.network-name').text(network);
                showCryptoCurrencyNameSymbols(network);
            }
            else
            {
                $('.network-validation-error').text('CryptoCurrency setting is either empty or inactive!');

                //hide name, symbol, logo, status & create-network-address-div div's
                $("#name-div, #symbol-div, #logo-div, #status-div, #create-network-address-div").hide();

                //submit & cancel - true & disable cancel link
                $("#currency-add-submit-btn, #cancel-link").attr("disabled", true);
            }
        })
        .fail(function(error)
        {
            console.log(error);
        });
    }

    /**
    * [On Change - Preview Currency Logo]
    */
    $(document).on('change','#currency-logo', function()
    {
        let orginalSource = '{{ url('public/user_dashboard/images/favicon.png') }}';
        readFileOnChange(this, $('#currency-demo-logo-preview'), orginalSource);
    });

    $.validator.setDefaults({
        highlight: function(element) {
            $(element).parent('div').addClass('has-error');
        },
        unhighlight: function(element) {
            $(element).parent('div').removeClass('has-error');
        },
    });

    $('#add_currency_form').validate({
        rules: {
            name: {
                required: true,
            },
            code: {
                required: true,
            },
            symbol: {
                required: true,
            },
            rate: {
                required: true,
                number: true,
                min: 0.0001,
            },
            logo: {
                extension: "png|jpg|jpeg|gif|bmp",
            },
        },
        messages: {
          rate: {
            min: "Please enter values greater than 0.0001!"
          },
          logo: {
            extension: "Please select (png, jpg, jpeg, gif or bmp) file!"
          }
        },
        submitHandler: function(form)
        {
            $("#currency-add-submit-btn").attr("disabled", true);
            $('#cancel-link').attr("disabled", true);
            $(".fa-spin").show();
            $("#currency-add-submit-btn-text").text('Submitting...');
            $('#currency-add-submit-btn').click(function (e) {
                e.preventDefault();
            });
            $('#cancel-link').click(function (e) {
                e.preventDefault();
            });
            form.submit();
        }
    });

</script>

@endpush
