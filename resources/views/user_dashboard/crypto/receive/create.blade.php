@extends('user_dashboard.layouts.app')

@section('css')
    <style>
        @media only screen and (max-width: 206px) {
            .chart-list ul li.active a {
                padding-bottom: 0;
            }
        }
        #wallet-address {
            display: flex;
            justify-content: center;
            text-align: center;
        }
        #wallet-address canvas{
            width: 256px !important;
            height: 256px !important;
        }
        .wrapper {
            position: relative;
            width: 100%;
        }
        #wallet-address-input {
            float: left;
            box-sizing: border-box;
            padding-right: 80px;
            width: 84%;
        }
        .wallet-address-copy-btn {
            position: relative;
            top: 1px;
        }
    </style>
@endsection

@section('content')
    <section class="section-06 history padding-30">
        <div class="container">
            <div class="row">
                <div class="col-md-8 col-xs-12 mb20 marginTopPlus">

                    @include('user_dashboard.layouts.common.alert')

                    <form method="POST" action="{{url('transfer')}}" id="transfer_form" accept-charset='UTF-8'>
                        <input type="hidden" value="{{csrf_token()}}" name="_token" id="token">

                        <div class="card">
                            <div class="card-header">
                                <div class="chart-list float-left">
                                    <ul>
                                        <!-- TODO: translation -->
                                        <li class="active"><a href="#">@lang('message.dashboard.right-table.crypto-receive') {{ strtoupper($walletCurrencyCode) }}</a></li>
                                    </ul>
                                </div>
                            </div>
                            <div class="wap-wed mt20 mb20">
                                <div class="text-center">
                                    <!-- TODO: translation -->
                                    <h3><b>@lang('message.dashboard.crypto.receive.address-qr-code-head-text')</b></h3>
                                    <br>
                                    <div id="wallet-address"></div>
                                    <br>

                                    {{-- <button class="btn btn-cust wallet-address-print-btn" type="button">Print QR Code</button> --}}

                                    <!-- TODO: translation -->
                                    <small class="form-text text-muted"><b>@lang('message.dashboard.crypto.receive.address-qr-code-foot-text-1') {{ strtoupper($walletCurrencyCode) }} @lang('message.dashboard.crypto.receive.address-qr-code-foot-text-2')</b>, @lang('message.dashboard.crypto.receive.address-qr-code-foot-text-3').</small>
                                    <br>
                                </div>
                            </div>

                            <div class="card-footer">
                                <div class="form-group">
                                    <!-- TODO: translation -->
                                    <label for="exampleInputPassword1">@lang('message.dashboard.crypto.receive.address-input-label-text')</label>
                                    <div class="wrapper">
                                        <input type="text" class="form-control wallet-address-input" id="wallet-address-input" value="{{ decrypt($address) }}" readonly>
                                        <!-- TODO: translation -->
                                        <button class="btn btn-cust wallet-address-copy-btn" type="button">@lang('message.dashboard.crypto.receive.address-input-copy-text')</button>
                                    </div>
                                </div>
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

<!-- jquery-qrcode -->
<script src="{{asset('public/user_dashboard/js/jquery-qrcode/jquery.qrcode.min.js')}}" type="text/javascript"></script>
<script src="{{asset('public/user_dashboard/js/jquery-qrcode/qrcode.js')}}" type="text/javascript"></script>

<!-- sweetalert -->
<script src="{{asset('public/user_dashboard/js/sweetalert/sweetalert-unpkg.min.js')}}" type="text/javascript"></script>

<script src="https://unpkg.com/jspdf@latest/dist/jspdf.min.js"></script>

<script>
    $(window).on('load', function (e)
    {
        jQuery('#wallet-address').qrcode({
            text : '{{ decrypt($address) }}'
        });
    });

    $(document).on('click','.wallet-address-copy-btn',function ()
    {
        $('.wallet-address-input').select();
        document.execCommand('copy');

        // TODO: translation
        swal({
            title: "{{__('Copied')}}!",
            text: "{{__('Address Copied')}}!",
            type: "info",
            icon: "success",
            closeOnClickOutside: false,
            closeOnEsc: false,
        });
    })

    // $(document).on('click','.wallet-address-print-btn',function ()
    // {
    //     var canvas = $('#wallet-address canvas')
    //     var img    = canvas[0].toDataURL("image/png");
    //     var doc = new jsPDF();
    //     doc.setFontSize(22);
    //     doc.text("Receiving Address Qr Code", 55, 25);
    //     doc.addImage(img, "PNG", 15, 40, 180, 150, 150);
    //     doc.save('wallet-address-qr-code.pdf')
    // })
</script>

@endsection