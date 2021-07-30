@extends('admin.layouts.master')
@section('title', 'Edit Currency')

@section('head_style')
    <!-- sweetalert -->
    <link rel="stylesheet" type="text/css" href="{{ asset('public/backend/sweetalert/sweetalert.css')}}">
    <!-- bootstrap-toggle -->
    <link rel="stylesheet" href="{{ asset('public/backend/bootstrap-toggle/css/bootstrap-toggle.min.css') }}">
@endsection

@section('page_content')

  <!-- Main content -->
  <div class="row">
      <div class="col-md-12">
          <div class="box box-info">
              <div class="box-header with-border text-center">
                <h3 class="box-title">Edit Currency</h3>
              </div>

              <form action="{{ url('admin/settings/edit_currency/'.$result->id) }}" method="POST" class="form-horizontal" enctype="multipart/form-data" id="edit_currency_form">
                  {!! csrf_field() !!}

                  <input type="hidden" value="{{ $result->id }}" name="id" id="id">
                  <input type="hidden" value="{{ $result->default }}" name="default_currency" id="default_currency">
                  <input type="hidden" value="{{ $result->allow_address_creation }}" name="allow_address_creation" id="allow_address_creation">
                  <input type="hidden" value="{{ $result->type }}" name="type" id="type">
                  <input type="hidden" value="{{ $result->status }}" name="status">

                  <!-- box-body -->
                  <div class="box-body">

                      <!-- Type (Fiat/Crypto) -->
                      <div class="form-group" id="currency-type-div">
                          <label class="col-sm-3 control-label" for="inputEmail3">Type</label>
                          <div class="col-sm-6">
                            <select class="form-control type" name="type" id="type" disabled="disabled">
                                <option value='fiat' {{ $result->type == 'fiat' ? 'selected':"" }}>Fiat</option>
                                <option value='crypto' {{ $result->type == 'crypto' ? 'selected':"" }}>Crypto</option>
                            </select>
                            <span class="network-validation-error" style="color: red;font-weight: bold"></span>
                          </div>
                      </div>

                      <!-- Name -->
                      <div class="form-group" id="name-div">
                          <label class="col-sm-3 control-label" for="inputEmail3">Name</label>
                          <div class="col-sm-6">
                            <input type="text" name="name" class="form-control" value="{{ $result->name }}" placeholder="Name" id="name">
                            <span class="text-danger">{{ $errors->first('name') }}</span>
                          </div>
                      </div>

                      <!-- Code -->
                      <div class="form-group" id="code-div">
                          <label class="col-sm-3 control-label" for="inputEmail3">Code</label>
                          <div class="col-sm-6">
                            <input type="text" name="code" class="form-control" value="{{ $result->code }}" placeholder="Code" id="code">
                            <span class="text-danger">{{ $errors->first('code') }}</span>
                          </div>
                      </div>

                      <!-- Symbol -->
                      <div class="form-group" id="symbol-div">
                          <label class="col-sm-3 control-label" for="inputEmail3">Symbol</label>
                          <div class="col-sm-6">
                            <input type="text" name="symbol" class="form-control" value="{{ $result->symbol }}" placeholder="Symbol" id="symbol">
                            <span class="text-danger">{{ $errors->first('symbol') }}</span>
                          </div>
                      </div>

                      <!-- Exchange Rate -->
                      <div class="form-group" id="exchange-rate-div">
                          <label class="col-sm-3 control-label" for="inputEmail3">Exchange Rate</label>
                          <div class="col-sm-6">
                            <input type="text" name="rate" class="form-control" value="{{ (float)$result->rate }}" placeholder="Rate" id="rate" oninput="restrictNumberToEightdecimals(this)">
                            <span class="text-danger">{{ $errors->first('rate') }}</span>
                            <div class="clearfix"></div>
                            <small class="form-text text-muted"><strong>*Allowed upto 8 decimal places.</strong></small>
                          </div>
                      </div>

                      <!-- Logo -->
                      <div class="form-group" id="logo-div">
                          <label for="inputEmail3" class="col-sm-3 control-label">Logo</label>
                          <div class="col-sm-6">
                            <input type="file" name="logo" class="form-control input-file-field" data-rel="{{ isset($result->logo) ? $result->logo : '' }}" id="logo"
                            value="{{ isset($result->logo) ? $result->logo : '' }}">
                            <span class="text-danger">{{ $errors->first('logo') }}</span>
                            <div class="clearfix"></div>
                            <small class="form-text text-muted"><strong>{{ allowedImageDimension(64,64) }}</strong></small>

                            @if(!empty($result->logo))
                              <div class="setting-img">
                                <img src='{{ url('public/uploads/currency_logos/'.$result->logo) }}' width="64" height="64" id="currency-logo-preview">
                                <span class="remove_currency_preview"></span>
                              </div>
                            @else
                              <div class="setting-img">
                                <img src='{{ url('public/user_dashboard/images/favicon.png') }}' width="64" height="64" id="currency-demo-logo-preview">
                              </div>
                            @endif
                          </div>
                      </div>

                      <!-- Exchange From -->
                      <div class="form-group" id="exchange-from-div">
                          <label class="col-sm-3 control-label" for="inputEmail3">Exchange From</label>
                          <div class="col-sm-6">
                          <select class="form-control exchange_from" name="exchange_from" id="exchange_from">
                              <option value='local' {{ isset($result->exchange_from) && $result->exchange_from == 'local' ? 'selected':"" }}>local</option>
                              <option value='api' {{ isset($result->exchange_from) && $result->exchange_from == 'api' ? 'selected':"" }}>api</option>
                          </select>
                          <span class="text-danger">{{ $errors->first('exchange_from') }}</span>
                          </div>
                      </div>

                      <!-- Status -->
                      {{-- <div class="form-group" id="status-div">
                        <label class="col-sm-3 control-label" for="inputEmail3">Status</label>
                        <div class="col-sm-6">

                          @if ($result->default == 1)
                            <p class="form-control-static"><span class="label label-danger">Staus Change Disallowed </span></p><p><span class="label label-warning">Default Currency</span></p>

                          @else
                            <select class="form-control status" name="status" id="status">
                                <option value='Active' {{ $result->status == 'Active' ? 'selected':"" }}>Active</option>
                                <option value='Inactive' {{ $result->status == 'Inactive' ? 'selected':"" }}>Inactive</option>
                            </select>
                            <span class="text-danger">{{ $errors->first('status') }}</span>
                          @endif
                        </div>
                      </div> --}}
                      <!-- /.box-body -->

                      <!-- box-footer -->
                      <div class="box-footer">
                          <button type="button" class="btn btn-danger btn-flat" id="cancel-link">Cancel</button>
                          <button type="submit" class="btn btn-primary btn-flat pull-right" id="currency-edit-submit-btn">
                              <i class="fa fa-spinner fa-spin" style="display: none;"></i> <span id="currency-edit-submit-btn-text">Update</span>
                          </button>
                      </div>
                      <!-- /.box-footer -->
                  </div>
              </form>
          </div>
      </div>
  </div>
  <!-- /.box -->

@endsection

@push('extra_body_scripts')

<!-- jquery.validate -->
<script src="{{ asset('public/dist/js/jquery.validate.min.js') }}" type="text/javascript"></script>

<!-- jquery.validate additional-methods -->
<script src="{{ asset('public/dist/js/jquery-validation-1.17.0/dist/additional-methods.min.js') }}" type="text/javascript"></script>

<!-- sweetalert -->
<script src="{{ asset('public/backend/sweetalert/sweetalert.min.js')}}" type="text/javascript"></script>

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

        //enable bootstrap toogle for network-address
        $('#network-address').bootstrapToggle({
          size: 'normal',
        });

        //if allow_address_creation is checked
        let checkAllowedAddressCreation = $('#allow_address_creation').val();
        if(checkAllowedAddressCreation == 'Yes') $('#network-address').prop('checked', true).change();

        //enable Seelct2 for network select
        $(".network").select2({disabled:'readonly'});

        //load network select options
        let output = '';
        output += `<option value="${$('#code').val()}">${$('#code').val()}</option>`;
        $('#network').html(output);

        //Show name, symbol (readonly) based on network
        let network = $('#network option:selected').text();
        $('.network-name').text(network);
        showCryptoCurrencyNameSymbols(network);
    }

    /**
    * [On Change - Preview Currency Logo]
    */
    $(document).on('change','#logo', function()
    {
        let orginalSource = '{{ url('public/user_dashboard/images/favicon.png') }}';
        let logo = $('#logo').attr('data-rel');
        if (logo != '') {
          readFileOnChange(this, $('#currency-logo-preview'), orginalSource);
          $('.remove_currency_preview').remove();
        }
        readFileOnChange(this, $('#currency-demo-logo-preview'), orginalSource);
    });

    $(document).on('click','.remove_currency_preview',function()
    {
        var image = $('#logo').attr('data-rel');
        var currency_id = $('#id').val();

        if(image)
        {
          $.ajax(
          {
            headers:
            {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            type : "POST",
            url : SITE_URL+"/admin/settings/currency/delete-currency-logo",
            // async : false,
            data: {
              'logo' : image,
              'currency_id' : currency_id,
            },
            dataType : 'json',
            success: function(reply)
            {
              if (reply.success == 1){
                swal({title: "Deleted!", text: reply.message, type: "success"},
                  function(){
                     location.reload();
                  }
                );
              }else{
                  alert(reply.message);
                  location.reload();
              }
            }
          });
        }
    });

    $.validator.setDefaults({
        highlight: function(element) {
           $(element).parent('div').addClass('has-error');
        },
        unhighlight: function(element) {
            $(element).parent('div').removeClass('has-error');
        },
    });

    $('#edit_currency_form').validate({
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
          },
        },
        submitHandler: function(form)
        {
            $("#currency-edit-submit-btn").attr("disabled", true);
            $('#cancel-link').attr("disabled", true);
            $(".fa-spin").show();
            $("#currency-edit-submit-btn-text").text('Updating...');
            $('#currency-edit-submit-btn').click(function (e) {
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