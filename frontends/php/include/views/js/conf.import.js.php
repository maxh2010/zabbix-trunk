<script type="text/javascript">
	jQuery(function($) {
		$('#import').click(function() {
			if ($('.deleteMissing:checked').length > 0) {
				return confirm('<?= _('Delete all elements that are not present in the XML file?') ?>');
			}
		});
	});
</script>
