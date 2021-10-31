					</div>
				</div>
				<div class="footer">
					<?php echo "Version " . $version . " | "; ?>
					<a href="https://github.com/fireshaper/parcelpony">Github</a> | made by fireshaper
				</div>
			</div>
		</div>
	</div>
</div>

<script type="text/javascript">
$(".media-body").click(function() {

	if ($(this).closest(".media-box").find( ".media-track" ).css("display") == "block") {
		$(this).closest(".media-box").find( ".media-click-text" ).text('Click to Expand');
	} else {
		$(this).closest(".media-box").find( ".media-click-text" ).text('Click to Close');
	}
	
	$(this).closest(".media-box").find( ".media-track" ).slideToggle(100, function() {
		return $(this).closest(".media-box").find( ".media-track" ).is(":visible");
	});

});

$(".media-click-text").click(function() {

    var link = $(this);
	if ($(this).next( ".media-track" ).css("display") == "block") {
		link.text('Click to Expand');
	} else {
		link.text('Click to Close');
	}
	
	$(this).nextAll( ".media-track" ).slideToggle(100, function() {
		return $(this).nextAll( ".media-track" ).is(":visible");
	});

});

function AutoRefresh( t ) {
    setTimeout("location.reload(true);", t);
}
</script>

</body>
</html>
