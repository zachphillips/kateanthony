$(document).ready(function(){
	$("img.c").hover(
		function() {
			$(this).stop().animate({"opacity": "0"}, "slow");
		},
		function() {
			$(this).stop().animate({"opacity": "1"}, "slow");
		});

});

$(document).ready(function(){
	
	$("img.logo").hover(
		function() {
			$("p.contactlink").stop().animate({"opacity": "1"}, "slow");
			$("p.hide").stop().css("opacity","0");
			$("img.kate").stop().animate({"opacity": "0"}, "slow");
			$(".e").stop().animate({"opacity": "0"}, "slow");
		},
		function() {
			$(".e").stop().animate({"opacity": "1"}, "slow");
			$("p.contactlink").stop().animate({"opacity": "0"}, "slow");
			$("img.kate").stop().animate({"opacity": "1"}, "slow", function() {
				$("p.hide").stop().css("opacity","1");
			});
		});

});

$(window).load(function(){
	var imageHeight = $("img.c").height();
	$(".autoheight").stop().height(imageHeight);
});

















