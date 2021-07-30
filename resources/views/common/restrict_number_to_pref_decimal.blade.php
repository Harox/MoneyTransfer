<script type="text/javascript">
    function restrictNumberToPrefdecimal(e)
    {
        let decimaFormat = '{{ $preference['decimal_format_amount'] }}';
        let num = $.trim(e.value);
        if (num.length > 0 && !isNaN(num))
        {
            switch (decimaFormat)
            {
                case '1':
                    e.value = digitCheck(num, 8, decimaFormat);
                    break;
                case '2':
                    e.value = digitCheck(num, 8, decimaFormat);
                    break;
                case '3':
                    e.value = digitCheck(num, 8, decimaFormat);
                    break;
                case '4':
                    e.value = digitCheck(num, 8, decimaFormat);
                    break;
                case '5':
                    e.value = digitCheck(num, 8, decimaFormat);
                    break;
                case '6':
                    e.value = digitCheck(num, 8, decimaFormat);
                    break;
                case '7':
                    e.value = digitCheck(num, 8, decimaFormat);
                    break;
                case '8':
                    e.value = digitCheck(num, 8, decimaFormat);
                    break;
            }
            return e.value;
        }
    }

    function digitCheck(num, beforeDecimal, afterDecimal)
    {
        return num.replace(/[^\d.]/g, '')            
                  .replace(new RegExp("(^[\\d]{" + beforeDecimal + "})[\\d]", "g"), '$1') 
                  .replace(/(\..*)\./g, '$1')         
                  .replace(new RegExp("(\\.[\\d]{" + afterDecimal + "}).", "g"), '$1'); 
    }
</script>