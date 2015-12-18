$(document).ready(function() {
	$("div [id$=Round]").corner();
});

function swapPhoto(placeholder, url) {
    $("#" + placeholder).attr("src", url);
}