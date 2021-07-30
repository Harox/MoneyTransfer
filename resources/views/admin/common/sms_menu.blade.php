<div class="box box-primary">

  {{-- normal template --}}
  <div class="box-header with-border">
    <h3 class="box-title underline">SMS Templates</h3>
  </div>
  <div class="box-body no-padding" style="display: block;">
    <ul class="nav nav-pills nav-stacked">

      <li {{ isset($list_menu) &&  $list_menu == 'menu-21' ? 'class=active' : ''}} >
        <a href="{{ URL::to("admin/sms-template/21")}}">Identity/Address Verification</a>
      </li>

      <li {{ isset($list_menu) &&  $list_menu == 'menu-1' ? 'class=active' : ''}} >
        <a href="{{ URL::to("admin/sms-template/1")}}">Transferred Payments</a>
      </li>

      <li {{ isset($list_menu) &&  $list_menu == 'menu-2' ? 'class=active' : ''}} >
        <a href="{{ URL::to("admin/sms-template/2")}}">Received Payments</a>
      </li>


      <li {{ isset($list_menu) &&  $list_menu == 'menu-4' ? 'class=active' : ''}} >
        <a href="{{ URL::to("admin/sms-template/4")}}">Request Payment Creation</a>
      </li>

      <li {{ isset($list_menu) &&  $list_menu == 'menu-5' ? 'class=active' : ''}} >
        <a href="{{ URL::to("admin/sms-template/5")}}">Request Payment Acceptance</a>
      </li>

    </ul>
  </div>
</div>

<div class="box box-primary">
  <div class="box-header with-border">
    <h3 class="box-title underline">SMS Templates of Admin actions</h3>
  </div>
  <div class="box-body no-padding" style="display: block;">
    <ul class="nav nav-pills nav-stacked">

      <li {{ isset($list_menu) &&  $list_menu == 'menu-14' ? 'class=active' : ''}} >
        <a href="{{ URL::to("admin/sms-template/14")}}">Merchant Payment</a>
      </li>

      <li {{ isset($list_menu) &&  $list_menu == 'menu-10' ? 'class=active' : ''}} >
        <a href="{{ URL::to("admin/sms-template/10")}}">Payout</a>
      </li>

      <li {{ isset($list_menu) &&  $list_menu == 'menu-6' ? 'class=active' : ''}} >
        <a href="{{ URL::to("admin/sms-template/6")}}">Transfers</a>
      </li>

      <li {{ isset($list_menu) &&  $list_menu == 'menu-8' ? 'class=active' : ''}} >
        <a href="{{ URL::to("admin/sms-template/8")}}">Request Payments (Success/Refund)</a>
      </li>


      <li {{ isset($list_menu) &&  $list_menu == 'menu-16' ? 'class=active' : ''}} >
        <a href="{{ URL::to("admin/sms-template/16")}}">Request Payments (Cancel/Pending)</a>
      </li>

    </ul>
  </div>
  </div>