<!-- CRYPTO IS DISABLED FROM PREFERENCE-->
@extends('admin.layouts.master')
@section('title', 'Crypto Disabled')
@section('page_content')
    <!-- Main content -->
    <div class="row">
        <div class="col-md-12">
            @include('admin.common.crypto_preference_disabled_body')
        </div>
    </div>
@endsection

@push('extra_body_scripts')
    @include('admin.common.crypto_preference_disabled_script')
@endpush