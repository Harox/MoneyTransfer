@extends('admin.layouts.master')
@section('title', 'Email Templates')

@section('head_style')
  <!-- wysihtml5 -->
  <link rel="stylesheet" type="text/css" href="{{  asset('public/backend/bootstrap-wysihtml5/bootstrap3-wysihtml5.min.css') }}">
@endsection


@section('page_content')
    <div class="row">
      <div class="col-md-3">
         @include('admin.common.mail_menu')
      </div>
      <!-- /.col -->
      <div class="col-md-9">
        <div class="box box-primary">
          <div class="box-header with-border">
            <h3 class="box-title">
              @if($tempId == 1)
                Compose Transferred Template
              @elseif($tempId == 2)
                Compose Received Template

              @elseif($tempId == 3)
                Compose Bank Transfer Template

              @elseif($tempId == 21)
                Compose Identity/Address Verification Template

              @elseif($tempId == 19)
                Compose 2-Factor Authentication Template

              @elseif($tempId == 4)
                Compose Request Creation Template

              @elseif($tempId == 5)
                Compose Request Acceptance Template

              @elseif($tempId == 6)
                Compose Transfer Status Change Template

              @elseif($tempId == 7)
                Compose Bank Transfer Status Change Template

              @elseif($tempId == 8)
                Compose Request Payment Status Change Template

              @elseif($tempId == 10)
                Compose Payout Status Change Template

              @elseif($tempId == 11)
                Compose Ticket Template

              @elseif($tempId == 12)
                Compose Ticket Reply Template

              @elseif($tempId == 16)
                Compose Request Payment Status Change Template

              @elseif($tempId == 17)
                Compose User Verification Template

              @elseif($tempId == 18)
                Compose Password Reset Template

              @elseif($tempId == 13)
                Compose Dispute Reply Template

              @elseif($tempId == 14)
                Compose Merchant Payment Status Change Template

              {{-- user status change --}}
              @elseif($tempId == 29)
              Compose User Status Change Template

              @elseif($tempId == 23)
                Compose Deposit Notification Template

              @elseif($tempId == 24)
                Compose Payout Notification Template

              @elseif($tempId == 25)
                Compose Exchange Notification Template

              @elseif($tempId == 26)
                Compose Transfer Notification Template

              @elseif($tempId == 27)
                Compose Request Acceptance Notification Template

              @elseif($tempId == 28)
                Compose Payment Notification Template

              @elseif($tempId == 30)
                Compose Deposit via Admin Template

              @elseif($tempId == 31)
                Compose Payout via Admin Template

              @endif
            </h3>
          </div>

          <form action='{{url('admin/template_update/'.$tempId)}}' method="post" id="template">
            {!! csrf_field() !!}

            <!-- /.box-header -->
            <div class="box-body">
              <div class="form-group">
                  <label for="Subject">Subject</label>
                  <input class="form-control" name="en[subject]" type="text" value="{{$temp_Data[0]->subject}}">
                  @if ($errors->has('en[subject]'))
                        <span class="help-block">
                            <strong>{{ $errors->first('en[subject]') }}</strong>
                        </span>
                  @endif
                </div>

              <div class="form-group">
                  <textarea name="en[body]" class="form-control editor" style="height: 300px">
                    {{$temp_Data[0]->body}}
                  </textarea>
                  @if ($errors->has('en[body]'))
                      <span class="help-block">
                          <strong>{{ $errors->first('en[body]') }}</strong>
                      </span>
                  @endif
              </div>

              <div class="box-group" id="accordion">
                <!-- we are adding the .panel class so bootstrap.js collapse plugin detects it -->
                <div class="panel box box-primary">
                  <div class="box-header with-border">
                    <h4 class="box-title">
                      <a data-toggle="collapse" data-parent="#accordion" href="#collapseOne" aria-expanded="false" class="collapsed">
                        Arabic
                      </a>
                    </h4>
                  </div>
                  <div id="collapseOne" class="panel-collapse collapse" aria-expanded="false" style="height: 0px;">
                    <div class="box-body">
                      <div class="form-group">
                        <label for="Subject">Subject</label>
                        <input class="form-control" name="ar[subject]" type="text" value="{{$temp_Data[1]->subject}}">
                        @if ($errors->has('ar[subject]'))
                            <span class="help-block">
                                <strong>{{ $errors->first('ar[subject]') }}</strong>
                            </span>
                        @endif
                      </div>
                      <div class="form-group">
                          <textarea name="ar[body]" class="form-control editor" style="height: 300px">
                            {{$temp_Data[1]->body}}
                          </textarea>
                          @if ($errors->has('ar[body]'))
                            <span class="help-block">
                                <strong>{{ $errors->first('ar[body]') }}</strong>
                            </span>
                          @endif
                      </div>

                    </div>
                  </div>
                </div>

                <div class="panel box box-success">
                  <div class="box-header with-border">
                    <h4 class="box-title">
                      <a data-toggle="collapse" data-parent="#accordion" href="#collapseThree" class="collapsed" aria-expanded="false">
                        French
                      </a>
                    </h4>
                  </div>
                  <div id="collapseThree" class="panel-collapse collapse" aria-expanded="false" style="height: 0px;">
                    <div class="box-body">
                      <div class="form-group">
                        <label for="Subject">Subject</label>
                        <input class="form-control" name="fr[subject]" type="text" value="{{$temp_Data[2]->subject}}">
                      </div>
                      <div class="form-group">
                          <textarea name="fr[body]" class="form-control editor" style="height: 300px">
                            {{$temp_Data[2]->body}}
                          </textarea>
                      </div>
                    </div>
                  </div>
                </div>

                <div class="panel box box-success">
                  <div class="box-header with-border">
                    <h4 class="box-title">
                      <a data-toggle="collapse" data-parent="#accordion" href="#collapseTwo" class="collapsed" aria-expanded="false">
                        Português
                      </a>
                    </h4>
                  </div>
                  <div id="collapseTwo" class="panel-collapse collapse" aria-expanded="false" style="height: 0px;">
                    <div class="box-body">
                      <div class="form-group">
                        <label for="Subject">Subject</label>
                        <input class="form-control" name="pt[subject]" type="text" value="{{$temp_Data[3]->subject}}">
                      </div>
                      <div class="form-group">
                          <textarea name="pt[body]" class="form-control editor" style="height: 300px">
                            {{$temp_Data[3]->body}}
                          </textarea>
                      </div>
                    </div>
                  </div>
                </div>

                <div class="panel box box-success">
                  <div class="box-header with-border">
                    <h4 class="box-title">
                      <a data-toggle="collapse" data-parent="#accordion" href="#collapseFour" class="collapsed" aria-expanded="false">
                        Russian
                      </a>
                    </h4>
                  </div>
                  <div id="collapseFour" class="panel-collapse collapse" aria-expanded="false" style="height: 0px;">
                    <div class="box-body">
                      <div class="form-group">
                        <label for="Subject">Subject</label>
                        <input class="form-control" name="ru[subject]" type="text" value="{{$temp_Data[4]->subject}}">
                      </div>
                      <div class="form-group">
                        <textarea name="ru[body]" class="form-control editor" style="height: 300px">
                          {{$temp_Data[4]->body}}
                        </textarea>
                      </div>
                    </div>
                  </div>
                </div>

                <div class="panel box box-success">
                  <div class="box-header with-border">
                    <h4 class="box-title">
                      <a data-toggle="collapse" data-parent="#accordion" href="#collapseFive" class="collapsed" aria-expanded="false">
                        Spanish
                      </a>
                    </h4>
                  </div>
                  <div id="collapseFive" class="panel-collapse collapse" aria-expanded="false" style="height: 0px;">
                    <div class="box-body">
                      <div class="form-group">
                        <label for="Subject">Subject</label>
                        <input class="form-control" name="es[subject]" type="text" value="{{$temp_Data[5]->subject}}">
                      </div>
                      <div class="form-group">
                          <textarea name="es[body]" class="form-control editor" style="height: 300px">
                            {{$temp_Data[5]->body}}
                          </textarea>
                      </div>
                    </div>
                  </div>
                </div>

                <div class="panel box box-success">
                  <div class="box-header with-border">
                    <h4 class="box-title">
                      <a data-toggle="collapse" data-parent="#accordion" href="#collapseSix" class="collapsed" aria-expanded="false">
                        Turkish
                      </a>
                    </h4>
                  </div>
                  <div id="collapseSix" class="panel-collapse collapse" aria-expanded="false" style="height: 0px;">
                    <div class="box-body">
                      <div class="form-group">
                        <label for="Subject">Subject</label>
                        <input class="form-control" name="tr[subject]" type="text" value="{{$temp_Data[6]->subject}}">
                      </div>
                      <div class="form-group">
                          <textarea name="tr[body]" class="form-control editor" style="height: 300px">
                          {{$temp_Data[6]->body}}
                          </textarea>
                      </div>
                    </div>
                  </div>
                </div>

                <div class="panel box box-success">
                  <div class="box-header with-border">
                    <h4 class="box-title">
                      <a data-toggle="collapse" data-parent="#accordion" href="#collapseSeven" class="collapsed" aria-expanded="false">
                        Chinese
                      </a>
                    </h4>
                  </div>
                  <div id="collapseSeven" class="panel-collapse collapse" aria-expanded="false" style="height: 0px;">
                    <div class="box-body">
                      <div class="form-group">
                        <label for="Subject">Subject</label>
                        <input class="form-control" name="ch[subject]" type="text" value="{{ ($temp_Data[7]->subject) }}">
                      </div>
                      <div class="form-group">
                          <textarea name="ch[body]" class="form-control editor" style="height: 180px">
                          {{ ($temp_Data[7]->body) }}
                          </textarea>
                      </div>
                    </div>
                  </div>
                </div>

              </div>
            </div>
            <!-- /.box-body -->
            <div class="box-footer">
              <div class="pull-right">

                @if(Common::has_permission(\Auth::guard('admin')->user()->id, 'edit_email_template'))
                  <button type="submit" class="btn btn-primary btn-flat" id="email_edit">
                      <i class="fa fa-spinner fa-spin" style="display: none;"></i> <span id="email_edit_text">Update</span>
                  </button>
                @endif

              </div>
            </div>
          </form>
          <!-- /.box-footer -->
        </div>
        <!-- /.nav-tabs-custom -->
      </div>
      <!-- /.col -->
    </div>
    <!-- /.row -->
@endsection

@push('extra_body_scripts')

<!-- jquery.validate -->
<script src="{{ asset('public/dist/js/jquery.validate.min.js') }}" type="text/javascript"></script>

<!-- wysihtml5 -->
<script src="{{ asset('public/backend/bootstrap-wysihtml5/bootstrap3-wysihtml5.all.min.js') }}" type="text/javascript"></script>

<script>
    $(function () {
      $(".editor").wysihtml5();
    });

    $('#template').validate({
        rules: {
            subject: {
                required: true
            },
            content:{
               required: true
            }
        },
        submitHandler: function(form)
        {
            $("#email_edit").attr("disabled", true);
            $(".fa-spin").show();
            $("#email_edit_text").text('Updating...');
            form.submit();
        }
    });
</script>

@endpush