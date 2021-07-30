
<!-- temp-9, temp-15 and temp-20 - not in database, can be used later-->

<!-- start temp ID = 1 and ending temp-22, we should add from temp-23-->

<div class="box box-primary">

  {{-- normal template --}}
  <div class="box-header with-border">
    <h3 class="box-title underline">Email Templates</h3>
  </div>
  <div class="box-body no-padding" style="display: block;">
    <ul class="nav nav-pills nav-stacked">

      <li {{ isset($list_menu) &&  $list_menu == 'menu-17' ? 'class=active' : ''}} >
        <a href="{{ URL::to("admin/template/17")}}">Email Verification</a>
      </li>

      <li {{ isset($list_menu) &&  $list_menu == 'menu-19' ? 'class=active' : ''}} >
        <a href="{{ URL::to("admin/template/19")}}">2-Factor Authentication</a>
      </li>

      <li {{ isset($list_menu) &&  $list_menu == 'menu-21' ? 'class=active' : ''}} >
        <a href="{{ URL::to("admin/template/21")}}">Identity/Address Verification</a>
      </li>

      <li {{ isset($list_menu) &&  $list_menu == 'menu-18' ? 'class=active' : ''}} >
        <a href="{{ URL::to("admin/template/18")}}">Password Reset</a>
      </li>

      <li {{ isset($list_menu) &&  $list_menu == 'menu-1' ? 'class=active' : ''}} ><!--1-->
        <a href="{{ URL::to("admin/template/1")}}">Transferred Payments</a>
      </li>

      <li {{ isset($list_menu) &&  $list_menu == 'menu-2' ? 'class=active' : ''}} ><!--2-->
        <a href="{{ URL::to("admin/template/2")}}">Received Payments</a>
      </li>

      <li {{ isset($list_menu) &&  $list_menu == 'menu-4' ? 'class=active' : ''}} ><!--4-->
        <a href="{{ URL::to("admin/template/4")}}">Request Payment Creation</a>
      </li>

      <li {{ isset($list_menu) &&  $list_menu == 'menu-5' ? 'class=active' : ''}} ><!--5-->
        <a href="{{ URL::to("admin/template/5")}}">Request Payment Acceptance</a>
      </li>
      {{-- <li {{ isset($list_menu) &&  $list_menu == 'menu-9' ? 'class=active' : ''}} >
        <a href="{{ URL::to("admin/template/9")}}">Payout</a>
      </li> --}}

      <li {{ isset($list_menu) &&  $list_menu == 'menu-11' ? 'class=active' : ''}} ><!--11-->
        <a href="{{ URL::to("admin/template/11")}}">Ticket</a>
      </li>

      <li {{ isset($list_menu) &&  $list_menu == 'menu-12' ? 'class=active' : ''}} ><!--12-->
        <a href="{{ URL::to("admin/template/12")}}">Ticket Reply</a>
      </li>

      <li {{ isset($list_menu) &&  $list_menu == 'menu-13' ? 'class=active' : ''}} ><!--13-->
        <a href="{{ URL::to("admin/template/13")}}">Dispute Reply</a>
      </li>

      <li {{ isset($list_menu) &&  $list_menu == 'menu-30' ? 'class=active' : ''}} ><!--13-->
        <a href="{{ URL::to("admin/template/30")}}">Deposit via Admin</a>
      </li>

      <li {{ isset($list_menu) &&  $list_menu == 'menu-31' ? 'class=active' : ''}} ><!--13-->
        <a href="{{ URL::to("admin/template/31")}}">Payout via Admin</a>
      </li>

    </ul>
  </div>
</div>

<div class="box box-primary">
  {{-- Status template --}}
  <div class="box-header with-border">
    <h3 class="box-title underline">Email Templates of Admin actions</h3>
  </div>
  <div class="box-body no-padding" style="display: block;">
    <ul class="nav nav-pills nav-stacked">

      <li {{ isset($list_menu) &&  $list_menu == 'menu-29' ? 'class=active' : ''}} >
        <a href="{{ URL::to("admin/template/29")}}">User Status Change</a>
      </li>

      <li {{ isset($list_menu) &&  $list_menu == 'menu-14' ? 'class=active' : ''}} ><!--14-->
        <a href="{{ URL::to("admin/template/14")}}">Merchant Payment</a>
      </li>

      <li {{ isset($list_menu) &&  $list_menu == 'menu-10' ? 'class=active' : ''}} ><!--10-->
        <a href="{{ URL::to("admin/template/10")}}">Payout</a>
      </li>

      <li {{ isset($list_menu) &&  $list_menu == 'menu-6' ? 'class=active' : ''}} ><!--6-->
        <a href="{{ URL::to("admin/template/6")}}">Transfers</a>
      </li>

      <li {{ isset($list_menu) &&  $list_menu == 'menu-8' ? 'class=active' : ''}} >
        <a href="{{ URL::to("admin/template/8")}}">Request Payments (Success/Refund)</a><!--8-->
      </li>
      <li {{ isset($list_menu) &&  $list_menu == 'menu-16' ? 'class=active' : ''}} > <!--15-->
        <a href="{{ URL::to("admin/template/16")}}">Request Payments (Cancel/Pending)</a>
      </li>

    </ul>
  </div>
</div>

<div class="box box-primary">
  {{-- Status template --}}
  <div class="box-header with-border">
    <h3 class="box-title underline">Admin Notifications</h3>
  </div>
  <div class="box-body no-padding" style="display: block;">
    <ul class="nav nav-pills nav-stacked">

      <li {{ isset($list_menu) &&  $list_menu == 'menu-23' ? 'class=active' : ''}} >
        <a href="{{ URL::to("admin/template/23")}}">Deposit Notification</a>
      </li>

      <li {{ isset($list_menu) &&  $list_menu == 'menu-24' ? 'class=active' : ''}} >
        <a href="{{ URL::to("admin/template/24")}}">Payout Notification</a>
      </li>

      <li {{ isset($list_menu) &&  $list_menu == 'menu-25' ? 'class=active' : ''}} >
        <a href="{{ URL::to("admin/template/25")}}">Exchange Notification</a>
      </li>

      <li {{ isset($list_menu) &&  $list_menu == 'menu-26' ? 'class=active' : ''}} >
        <a href="{{ URL::to("admin/template/26")}}">Transfer Notification</a>
      </li>

      <li {{ isset($list_menu) &&  $list_menu == 'menu-27' ? 'class=active' : ''}} >
        <a href="{{ URL::to("admin/template/27")}}">Request Acceptance Notification</a>
      </li>

      <li {{ isset($list_menu) &&  $list_menu == 'menu-28' ? 'class=active' : ''}} >
        <a href="{{ URL::to("admin/template/28")}}">Payment Notification</a>
      </li>

    </ul>
  </div>
</div>