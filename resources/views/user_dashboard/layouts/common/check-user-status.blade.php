<script type="text/javascript">
    /**
     * Check User Status
     */
    function checkUserSuspended(event)
    {
        let userStatus = '{{ auth()->user()->status }}';
        if (userStatus == 'Suspended')
        {
            event.stopPropagation();
            window.location.href="{{ url('check-user-status') }}";
            return false;
        }
    }
</script>