<div class="flash-container">
      @if(session('message'))
          <div class="alert {{ session('alert-class') }} text-center" style="margin-bottom:10px;" role="alert">
            {{ session('message') }}
            <a href="#" style="float:right;" class="alert-close" data-dismiss="alert">&times;</a>
          </div>
      @endif

      @if(!empty($error))
          <div class="alert alert-danger text-center" style="margin-bottom:10px;" role="alert">
            {{ $error }}
            <a href="#" style="float:right;" class="alert-close" data-dismiss="alert">&times;</a>
          </div>
      @endif

      @if($errors->any())
          <div class="alert alert-danger text-center" style="margin-bottom:10px;" role="alert">
              <a href="#" style="float:right;" class="alert-close" data-dismiss="alert">&times;</a>
          @foreach ($errors->all() as $error)
                  {{ $error }} <br/>
              @endforeach
          </div>
      @endif

      @if(session('success'))
          <div class="alert alert-success text-center" style="margin-bottom:10px;" role="alert">
            {{ session('success') }}
            <a href="#" style="float:right;" class="alert-close" data-dismiss="alert">&times;</a>
          </div>
      @endif

      @if(session('error'))
          <div class="alert alert-danger text-center" style="margin-bottom:10px;" role="alert">
            {{ session('error') }}
            <a href="#" style="float:right;" class="alert-close" data-dismiss="alert">&times;</a>
          </div>
      @endif
  </div>