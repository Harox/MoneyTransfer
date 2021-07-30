@extends('admin.layouts.master')
@section('title', 'General Settings')

@section('head_style')
   <!-- sweetalert -->
  <link rel="stylesheet" type="text/css" href="{{ asset('public/backend/sweetalert/sweetalert.css')}}">

  <!-- bootstrap-select -->
  <link rel="stylesheet" type="text/css" href="{{ asset('public/backend/bootstrap-select-1.13.12/css/bootstrap-select.min.css')}}">

@endsection

@section('page_content')

<!-- Main content -->
<div class="row">
    <div class="col-md-3 settings_bar_gap">
        @include('admin.common.settings_bar')
    </div>
    <div class="col-md-9">
        <div class="box box-info">
            <div class="box-header with-border text-center">
              <h3 class="box-title">General Settings Form</h3>
            </div>

            <form action="{{ url('admin/settings') }}" method="post" class="form-horizontal" enctype="multipart/form-data" id="general_settings_form">
                {!! csrf_field() !!}

                <!-- box-body -->
        				<div class="box-body">

        					{{-- Name --}}
        					<div class="form-group">
        					  <label class="col-sm-4 control-label" for="inputEmail3">Name</label>
        					  <div class="col-sm-6">
        					    <input type="text" name="name" class="form-control" value="{{ @$result['name'] }}" placeholder="Name">
        					  	<span class="text-danger">{{ $errors->first('name') }}</span>
        					  </div>
        					</div>

                  <!-- Logo -->
        					<div class="form-group">
                    <label class="col-sm-4 control-label" for="Logo">Logo</label>
                    <div class="col-sm-6">
                      <input type="file" name="photos[logo]" id="logo" class="form-control input-file-field" data-rel="{{ isset($result['logo']) ? $result['logo'] : '' }}" value="{{ old('photos[logo]') }}" placeholder="photos[logo]">

                      <span class="text-danger">{{ $errors->first('photos[logo]') }}</span>

                      <div class="clearfix"></div>
                      <small class="form-text text-muted"><strong>{{ allowedImageDimension(288,90) }}</strong></small>

                      @if (isset($result['logo']))
                          <img src='{{ url('public/images/logos/'. $result['logo']) }}' width="288" height="90" id="logo-preview">
                          <span class="remove_img_preview_site_logo"></span>
                      @else
                        <img src='{{ url('public/uploads/userPic/default-logo.jpg') }}' width="288" height="90" id="logo-demo-preview">
                      @endif
                    </div>
                  </div>


        					<!-- Favicon -->
        					<div class="form-group">
        					  <label class="col-sm-4 control-label" for="Favicon">Favicon</label>
        					  <div class="col-sm-6">
        					    <input type="file" name="photos[favicon]" id="favicon" class="form-control input-file-field" data-favicon="{{ isset($result['favicon']) ? $result['favicon'] : '' }}" value="{{ old('photos[favicon]') }}" placeholder="photos[favicon]">
        					  	<span class="text-danger">{{ $errors->first('photos[favicon]') }}</span>

                      <div class="clearfix"></div>
                      <small class="form-text text-muted"><strong>{{ allowedImageDimension(40,40) }}</strong></small>

                      @if (isset($result['favicon']))
                        <div class="setting-img">
                          <img src='{{ url('public/images/logos/'. $result['favicon']) }}' width="40" height="40" id="favicon-preview">
                        	<span class="remove_fav_preview"></span>
                        </div>
                      @else

                        <div class="setting-img">
                          <img src='{{ url('public/uploads/userPic/default-image.png') }}' width="40" height="40" id="favicon-demo-preview">
                        </div>
                      @endif
        					  </div>
        					</div>

                  <!-- Head Code -->
                  <div class="form-group">
                    <label for="inputEmail3" class="col-sm-4 control-label">Google Analytics Tracking Code</label>
                    <div class="col-sm-6">
                      <textarea name="head_code" placeholder="Google Analytics Tracking Code" rows="3" class="form-control">{{ @$result['head_code'] }}</textarea>
                      <span class="text-danger">{{ $errors->first('head_code') }}</span>
                    </div>
                  </div>

                  <!-- Google reCAPTCHA -->
                  <div class="form-group">
                    <label class="col-sm-4 control-label" for="exampleFormControlInput1">Google reCAPTCHA</label>
                    <div class="col-sm-6">
                      <select class="form-control has_captcha" name="has_captcha" id="has_captcha">
                          <option value='Enabled' {{ $result['has_captcha'] == 'Enabled' ? 'selected':""}}>Enabled</option>
                          <option value='Disabled' {{ $result['has_captcha'] == 'Disabled' ? 'selected':""}}>Disabled</option>
                      </select>
                    </div>
                  </div>

                  <!-- Login Via -->
                  <div class="form-group">
                    <label class="col-sm-4 control-label" for="exampleFormControlInput1">Login Via</label>
                    <div class="col-sm-6">
                      <select class="form-control login_via" name="login_via" id="login_via">
                          <option value='email_only' {{ $result['login_via'] == 'email_only' ? 'selected':""}}>email only</option>
                          <option value='phone_only' {{ $result['login_via'] == 'phone_only' ? 'selected':""}}>phone only</option>
                          <option value='email_or_phone' {{ $result['login_via'] == 'email_or_phone' ? 'selected':""}}>email or phone</option>
                      </select>
                      <span id="sms-error"></span>
                    </div>
                  </div>

                  <!-- Default Currency -->
                  <div class="form-group">
                    <label for="inputEmail3" class="col-sm-4 control-label">Default Currency</label>
                    <div class="col-sm-6">
                      <select class="form-control default_currency" name="default_currency">
                          @foreach ($currency as $key => $value)
                            <option value='{{ $key }}' {{ $result['default_currency'] == $key ? 'selected':""}}> {{ $value }}</option>
                          @endforeach
                      </select>
                    </div>
                  </div>

                  @if ($getCurrenciesPreference->value == "fiat_and_crypto")
                    <!-- Default Crypto Currency/Currencies -->
                    <div class="form-group">
                      <label for="inputEmail3" class="col-sm-4 control-label">Allowed Crypto Currencies</label>
                      <div class="col-sm-6">
                        <select class="form-control" name="default_crypto_currencies[]" id="default_crypto_currencies" multiple>
                            @foreach ($activeCryptoCurrencies as $key => $value)
                              <option value='{{ $key }}'>{{ $value }}</option>
                            @endforeach
                        </select>
                      </div>
                    </div>
                  @endif

                  <!-- Default Language -->
        					<div class="form-group">
        					  <label for="inputEmail3" class="col-sm-4 control-label">Default Language</label>
        					  <div class="col-sm-6">
        					    <select class="form-control default_language" name="default_language">
        					        @foreach ($language as $key => $value)
        					          <option value='{{ $key }}' {{ $result['default_language'] == $key ? 'selected':""}}> {{ $value }}</option>
        					        @endforeach
        					    </select>
        					  </div>
        					</div>
        				</div>
        				<!-- /.box-body -->

        				<!-- box-footer -->
          				@if(Common::has_permission(\Auth::guard('admin')->user()->id, 'edit_general_setting'))
            					<div class="box-footer">
                        <button type="submit" class="btn btn-primary btn-flat pull-right" id="general-settings-submit">
                            <i class="fa fa-spinner fa-spin" style="display: none;"></i> <span id="general-settings-submit-text">Submit</span>
                        </button>
                      </div>
          				@endif
  	            <!-- /.box-footer -->
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

  <!-- sweetalert -->
  <script src="{{ asset('public/backend/sweetalert/sweetalert.min.js')}}" type="text/javascript"></script>

  <!-- bootstrap-select -->
  <script src="{{ asset('public/backend/bootstrap-select-1.13.12/js/bootstrap-select.min.js') }}" type="text/javascript"></script>

  <!-- read-file-on-change -->
  @include('common.read-file-on-change')

  <script type="text/javascript">

      function updateSideBarCompanySmallLogo(file)
      {
          if (file.name.match(/.(png|jpg|jpeg|gif|bmp)$/i))
          {
            $.ajax(
            {
                headers: {
                  'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                type:'POST',
                url: SITE_URL+"/admin/settings/update-sidebar-company-logo",
                data: new FormData($('#general_settings_form')[0]),
                cache:false,
                contentType: false,
                processData: false,
            })
            .done(function(res)
            {
                $('.company-logo').attr('src', SITE_URL+'/public/images/logos/'+ res.filename);
            })
            .fail(function(error)
            {
                console.log(error.responseText);
            });
          }
          else
          {
            $('.company-logo').attr('src', SITE_URL+'/public/uploads/userPic/default-logo.jpg');
          }
      }

      $(window).on('load', function()
      {
          $(".has_captcha, .login_via, .default_currency, .default_language").select2({});
          $('#default_crypto_currencies').selectpicker();

          let selectedCryptoCurrencies = '{{ $result['default_crypto_currencies'] }}';
          // console.log(selectedCryptoCurrencies);
          if (selectedCryptoCurrencies != 'none')
          {
              $.each(selectedCryptoCurrencies.split(","), function(i,e)
              {
                  $("#default_crypto_currencies option[value='" + e + "']").prop("selected", true);
                  $('#default_crypto_currencies').selectpicker('refresh');
              });
          }
      });

      // preview company logo on change
      $(document).on('change','#logo', function()
      {
          let orginalSource = '{{ url('public/uploads/userPic/default-logo.jpg') }}';
          let logo = $('#logo').attr('data-rel');
          if (logo != '') {
            readFileOnChange(this, $('#logo-preview'), orginalSource);
            $('.remove_img_preview_site_logo').remove();
            updateSideBarCompanySmallLogo(this.files[0]);
          }
          else
          {
            readFileOnChange(this, $('#logo-demo-preview'), orginalSource);
            updateSideBarCompanySmallLogo(this.files[0]);
          }
      });

      // preview company favicon on change
      $(document).on('change','#favicon', function()
      {
          let orginalSource = '{{ url('public/uploads/userPic/default-image.png') }}'
          let favicon = $('#favicon').attr('data-favicon');
          if (favicon != '') {
            readFileOnChange(this, $('#favicon-preview'), orginalSource);
            $('.remove_fav_preview').remove();
          }
          else
          {
            readFileOnChange(this, $('#favicon-demo-preview'), orginalSource);
          }
      });

      //Delete logo preview
      $(document).on('click','.remove_img_preview_site_logo', function()
      {
          var logo = $('#logo').attr('data-rel');
          if(logo)
          {
            $.ajax(
            {
              headers:
              {
                  'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
              },
              type : "POST",
              url : SITE_URL+"/admin/settings/delete-logo",
              data: {
                'logo' : logo,
              },
              dataType : 'json',
              success: function(reply)
              {
                if (reply.success == 1)
                {
                  swal({title: "", text: reply.message, type: "success"},
                    function(){
                      window.location.reload();
                    }
                  );
                }
                else{
                    alert(reply.message);
                    window.location.reload();
                }
              }
            });
          }
      });

      //Delete favicon preview
      $(document).on('click','.remove_fav_preview', function()
      {
          var favicon = $('#favicon').attr('data-favicon');
          if(favicon)
          {
            $.ajax(
            {
              headers:
              {
                  'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
              },
              type : "POST",
              url : SITE_URL+"/admin/settings/delete-favicon",
              data: {
                'favicon' : favicon,
              },
              dataType : 'json',
              success: function(reply)
              {
                if (reply.success == 1){
                  // window.location.reload();
                  swal({title: "", text: reply.message, type: "success"},
                    function(){
                      window.location.reload();
                    }
                  );
                }else{
                    alert(reply.message);
                    window.location.reload();
                }
              }
            });
          }
      });

      $(document).on('change','#login_via', function()
      {
          if ($(this).val() == 'email_or_phone' || $(this).val() == 'phone_only')
          {
            $.ajax({
              headers:
              {
                  'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
              },
              method: "POST",
              url: SITE_URL+"/admin/settings/check-sms-settings",
              dataType: "json",
              contentType: false,
              processData: false,
              cache: false,
            })
            .done(function(response)
            {
                // console.log(response);
                if (response.status == false)
                {
                    $('#sms-error').addClass('error').html(response.message).css("font-weight", "bold");
                    $('form').find("button[type='submit']").prop('disabled',true);
                }
                else if (response.status == true)
                {
                    $('#sms-error').html('');

                    $('form').find("button[type='submit']").prop('disabled',false);
                }
            });
          }
          else
          {
            $('#sms-error').html('');
            $('form').find("button[type='submit']").prop('disabled',false);
          }
      });

      $.validator.setDefaults({
          highlight: function(element) {
              $(element).parent('div').addClass('has-error');
          },
          unhighlight: function(element) {
              $(element).parent('div').removeClass('has-error');
          },
          errorPlacement: function (error, element) {
              error.insertAfter(element);
          }
      });

      $('#general_settings_form').validate({
          rules: {
              name: {
                  required: true,
              },
              "photos[logo]": {
                  extension: "png|jpg|jpeg|gif|bmp",
              },
              "photos[favicon]": {
                  extension: "png|jpg|jpeg|gif|bmp",
              },
          },
          messages: {
            "photos[logo]": {
              extension: "Please select (png, jpg, jpeg, gif or bmp) file!"
            },
            "photos[favicon]": {
              extension: "Please select (png, jpg, jpeg, gif or bmp) file!"
            }
          },
          submitHandler: function(form)
          {
              $("#general-settings-submit").attr("disabled", true).click(function (e) {
                  e.preventDefault();
              });
              $(".fa-spin").show();
              $("#general-settings-submit-text").text('Submitting...');
              form.submit();
          }
      });

  </script>

@endpush


