<script type="text/javascript">

    /**
     * Show values according to currency type
     */
    function processCurrencyType(type)
    {
        // console.log(type)

        if (type === 'fiat')
        {
            $('.network-validation-error').text('');

            //show rate, exchange from, code, name, symbol, logo & status div's
            $("#exchange-rate-div, #exchange-from-div, #code-div, #name-div, #symbol-div, #logo-div, #status-div").show();

            //remove crypto network & create network address div's
            $('#crypto-networks-div, #create-network-address-div, #status-div').remove();

            //submit & cancel - false
            $('#currency-add-submit-btn, #cancel-link').removeAttr('disabled');

            //enable cancel link
            $("#cancel-link").click(() => { window.location.href = '{{ url("admin/settings/currency") }}'; });

            showFiatCurrencyStatus();
        }
        else
        {
            //Remove error classes
            $("#name-error, #code-error, #symbol-error, #rate-error, #status-div").remove();
            $("#name-div, #code-div, #symbol-div, #exchange-rate-div").find('.has-error').removeClass("has-error");

            //Hide rate, exchange from and code div
            $('#exchange-rate-div, #exchange-from-div, #code-div').hide();

            showCryptoCurrencyStatus();

            //get active crypto currency settings
            getActiveCryptoCurrencySettings()
        }

        //enable Seelct2 for status select
        $(".status").select2({});
    }

    /**
     * Show currency name & symbol
     */
    function showCryptoCurrencyNameSymbols(network)
    {
        $('.network-name').text(network);

        // console.log(network)
        if (network === 'BTC')
        {
            $('#name').val('Bitcoin');
            $('#symbol').val('฿');
        }
        else if (network === 'BTCTEST')
        {
            $('#name').val('Bitcoin (TESTNET!)');
            $('#symbol').val('฿');
        }
        else if (network === 'LTC')
        {
            $('#name').val('Litecoin');
            $('#symbol').val('Ł');
        }
        else if (network === 'LTCTEST')
        {
            $('#name').val('Litecoin (TESTNET!)');
            $('#symbol').val('Ł');
        }
        else if (network === 'DOGE')
        {
            $('#name').val('Dogecoin');
            $('#symbol').val('Ð');
        }
        else if (network === 'DOGETEST')
        {
            $('#name').val('Dogecoin (TESTNET!)');
            $('#symbol').val('Ð');
        }
        $('#name, #symbol').attr('readonly', true);
    }

    /**
     * Show fiat currency status
     */
    function showFiatCurrencyStatus()
    {
        //Fiat status-div
        var isDefault = '{{ isset($result->default) ? $result->default : null }}';
        var fiatCurrencyStatus = '{{ isset($result->status) ? $result->status : null }}';

        if (isDefault != '' && fiatCurrencyStatus != '')
        {
            if (isDefault == 1)
            {
                $('#exchange-from-div').after(`<div class="form-group" id="status-div">
                  <label class="col-sm-3 control-label" for="inputEmail3">Status</label>
                  <div class="col-sm-6">
                      <p class="form-control-static"><span class="label label-danger">Staus Change Disallowed </span></p><p><span class="label label-warning">Default Currency</span></p>
                  </div>
                </div>`);
            }
            else
            {
                $('#exchange-from-div').after(`<div class="form-group" id="status-div">
                  <label class="col-sm-3 control-label" for="inputEmail3">Status</label>
                  <div class="col-sm-6">
                      <select class="form-control status" name="status" id="status">
                          <option value='Active'>Active</option>
                          <option value='Inactive'>Inactive</option>
                      </select>
                  </div>
                </div>`);

                //Make Selected by database user status value
                $('#status option').filter(function()
                {
                    return ($(this).val() == fiatCurrencyStatus);
                }).prop('selected', true);
            }
        }
        else
        {
            $('#exchange-from-div').after(`<div class="form-group" id="status-div">
                <label class="col-sm-3 control-label" for="inputEmail3">Status</label>
                <div class="col-sm-6">
                  <select class="form-control status" name="status" id="status">
                      <option value='Active'>Active</option>
                      <option value='Inactive'>Inactive</option>
                  </select>
                </div>
            </div>`);
        }
    }

    /**
     * Show Crypto currency status
     */
    function showCryptoCurrencyStatus()
    {
        var cryptoCurrencyStatus = '{{ isset($cryptoCurrencyStatus) ? $cryptoCurrencyStatus : null }}';
        if (cryptoCurrencyStatus != '')
        {
            //Add status-div (read only) if type is crypto
            if (cryptoCurrencyStatus == 'Active')
            {
                $('#exchange-from-div').after(`<div class="form-group" id="status-div">
                    <label class="col-sm-3 control-label" for="inputEmail3">Status</label>
                    <div class="col-sm-6">
                      <p class="form-control-static"><span class="label label-success">Active</span></p>
                      <div class="clearfix"></div>
                      <small class="form-text text-muted"><strong>*<span class="network-name"></span> status is same as <span class="network-name"></span> setting status.</strong></small>
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
            }
            else if (cryptoCurrencyStatus == 'Inactive')
            {
                $('#exchange-from-div').after(`<div class="form-group" id="status-div">
                    <label class="col-sm-3 control-label" for="inputEmail3">Status</label>
                    <div class="col-sm-6">
                      <p class="form-control-static"><span class="label label-danger">Inactive</span></p>
                      <div class="clearfix"></div>
                      <small class="form-text text-muted"><strong>*<span class="network-name"></span> status is same as <span class="network-name"></span> setting status.</strong></small>
                    </div>
                </div>`);
            }
        }
        else
        {
            $('#exchange-from-div').after(`<div class="form-group" id="status-div">
                <label class="col-sm-3 control-label" for="inputEmail3">Status</label>
                <div class="col-sm-6">
                    <p class="form-control-static"><span class="label label-success">Active</span></p>
                    <div class="clearfix"></div>
                    <small class="form-text text-muted"><strong>*<span class="network-name"></span> status is same as <span class="network-name"></span> setting status.</strong></small>
                </div>
            </div>`);
        }
    }

    /**
     * [On Load]
     */
    $(window).on('load',function()
    {
        $(".type, .exchange_from").select2({});
        var type = $('#type').val();
        processCurrencyType(type);
    });

    /**
     * [On Change - Currency Type]
     */
    $(document).on('change','#type', function()
    {
        var type = $(this).val();

        // make name & symbol value empty and read-only on change of type (ADD)
        $('#name, #symbol').val('').attr('readonly', false);

        processCurrencyType(type);
    });

    /**
    * [On Change - network]
    */
    $(document).on('change','#network', function()
    {
        showCryptoCurrencyNameSymbols($('#network option:selected').text())
    });
</script>