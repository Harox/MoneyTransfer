<script type="text/javascript">
	$(document).on('click', '.preference-link', function(e)
	{
		e.preventDefault();
		window.localStorage.setItem("crypto-disabled", true);
		window.location.href = "{{ url('admin/settings/preference') }}";
	});
</script>