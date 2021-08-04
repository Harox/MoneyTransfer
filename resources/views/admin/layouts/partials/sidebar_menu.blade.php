@php
$getCurrenciesPreference = (new App\Repositories\CryptoCurrencyRepository())->getCurrenciesPreference();
@endphp

<ul class="sidebar-menu">
    <li <?= $menu == 'dashboard' ? ' class="active"' : 'treeview' ?>>
        <a href="{{ url('admin/home') }}">
            <i class="fa fa-dashboard"></i><span>Dashboard</span>
        </a>
    </li>

    <!--users-->
    @if (Common::has_permission(\Auth::guard('admin')->user()->id, 'view_user') || Common::has_permission(\Auth::guard('admin')->user()->id, 'view_admins'))
        <li <?= $menu == 'users' ? ' class="active treeview"' : 'treeview' ?>>
            <a href="#">
              <!--  <i class="glyphicon glyphicon-user"></i><span>Users</span>
                <span class="pull-right-container"><i class="fa fa-angle-left pull-right"></i></span>
               </a>-->
            <ul class="treeview-menu">
                @if (Common::has_permission(\Auth::guard('admin')->user()->id, 'view_user'))
                    <li <?= isset($sub_menu) && $sub_menu == 'users_list' ? ' class="active"' : '' ?>>
                        <a href="{{ url('admin/users') }}">
                            <i class="fa fa-user-circle-o"></i><span>Users</span>
                        </a>
                    </li>
                @endif
                @if (Common::has_permission(\Auth::guard('admin')->user()->id, 'view_admins'))
                    <li <?= isset($sub_menu) && $sub_menu == 'admin_users_list' ? ' class="active"' : '' ?>>
                        <a href="{{ url('admin/admin_users') }}">
                            <i class="fa fa-user-md"></i><span>Admins</span>
                        </a>
                    </li>
                @endif
            </ul>
        </li>
    @endif

    <!--merchants-->
    @if (Common::has_permission(\Auth::guard('admin')->user()->id, 'view_merchant') || Common::has_permission(\Auth::guard('admin')->user()->id, 'view_merchant_payment'))
        <li <?= $menu == 'merchant' ? ' class="active treeview"' : 'treeview' ?>>
           <!-- <a href="#">
                <i class="glyphicon glyphicon-user"></i><span>Merchants</span>
                <span class="pull-right-container"><i class="fa fa-angle-left pull-right"></i></span>
            </a>-->
            <ul class="treeview-menu">
                @if (Common::has_permission(\Auth::guard('admin')->user()->id, 'view_merchant'))
                    <li <?= isset($sub_menu) && $sub_menu == 'merchant_details' ? ' class="active"' : '' ?>>
                        <a href="{{ url('admin/merchants') }}">
                            <i class="fa fa-user-circle-o"></i><span>Merchants</span>
                        </a>
                    </li>
                @endif

                @if (Common::has_permission(\Auth::guard('admin')->user()->id, 'view_merchant_payment'))
                    <li <?= isset($sub_menu) && $sub_menu == 'merchant_payments' ? ' class="active"' : '' ?>>
                        <a href="{{ url('admin/merchant_payments') }}">
                            <i class="fa fa-money"></i><span>Merchant Payments</span>
                        </a>
                    </li>
                @endif
            </ul>
        </li>
    @endif

    <!-- transactions -->
    @if (Common::has_permission(\Auth::guard('admin')->user()->id, 'view_transaction'))
        <li <?= $menu == 'transactions' ? ' class="active treeview"' : 'treeview' ?>>
            <a href="{{ url('admin/transactions') }}"><i class="fa fa-history"></i><span>History</span></a>
        </li>
    @endif

    

    <!-- deposits -->
    @if (Common::has_permission(\Auth::guard('admin')->user()->id, 'view_deposit'))
        <!--<li <?= isset($menu) && $menu == 'deposits' ? ' class="active"' : '' ?>>
            <a href="{{ url('admin/deposits') }}"><i class="fa fa-arrow-down"></i><span>Deposits</span></a>
        </li>-->
    @endif

    <!-- Payouts -->
    @if (Common::has_permission(\Auth::guard('admin')->user()->id, 'view_withdrawal'))
        <li <?= isset($menu) && $menu == 'withdrawals' ? ' class="active"' : '' ?>>
            {{-- <a href="{{ url('admin/withdrawals') }}"><i class="fa fa-arrow-up"></i><span>Payouts</span></a> --}}
        </li>
    @endif

    @if (Common::has_permission(\Auth::guard('admin')->user()->id, 'view_transfer'))
        <li <?= isset($menu) && $menu == 'transfers' ? ' class="active treeview"' : 'treeview' ?>>
            <a href="#">
                <i class="fa fa-exchange"></i></i><span>Transactions</span>
                <span class="pull-right-container"><i class="fa fa-angle-left pull-right"></i></span>
            </a>
            <ul class="treeview-menu">
                <li <?= isset($sub_menu) && $sub_menu  == 'transfers' ? ' class="active"' : '' ?>>
                    <a href="{{ url('admin/transfers') }}">
                        <i class="fa fa-angle-double-right" aria-hidden="true"></i><span>MOZ  <i class="fa fa-exchange"></i>  ZIM</span>
                    </a>
                </li>
                <li <?= isset($sub_menu) && $sub_menu  == 'transfers' ? ' class="active"' : '' ?>>
                    <a href="{{ url('admin/transfers') }}">
                        <i class="fa fa-angle-double-right" aria-hidden="true"></i><span>ZIM  <i class="fa fa-exchange"></i>  MOZ</span>
                    </a>
                </li>
            </ul>
        </li>
    @endif

   

    <!-- Currencies & Fees -->
    @if (Common::has_permission(\Auth::guard('admin')->user()->id, 'view_currency'))
     <!--   <li <?= isset($menu) && $menu == 'currency' ? ' class="active"' : '' ?>>
            <a href="{{ url('admin/settings/currency') }}"><i class="fa fa-money"></i><span>Currencies</span></a>
        </li>-->
    @endif


    <!-- settings -->
    <li <?= $menu == 'settings' ? ' class="active treeview"' : 'treeview' ?>>
        <a href="{{ url('admin/settings') }}">
            <i class="fa fa-wrench"></i><span>Settings</span>
            <span class="pull-right-container"><i class="fa fa-angle-left pull-right"></i></span>
        </a>
    </li>
</ul>
