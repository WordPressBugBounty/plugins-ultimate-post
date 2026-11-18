(function ($) {
	"use strict";
	$.fn.UltpSlider = function (options) {
		/*Merge options and default options*/
		let opts = $.extend({}, $.fn.UltpSlider.defaults, options);
		// The vertical and horizontal tickers run here; an add-on runs any other type itself.
		if (opts.type != "horizontal" && opts.type != "vertical") {
			return;
		}
		/*Functions Scope*/
		let thisTicker = $(this),
			intervalID,
			timeoutID,
			isPause = false;
		let pauseVal = true;

		/*Always wrap, used in many place*/
		thisTicker.wrap("<div class='acmeticker-wrap'></div>");
		/*Wrap is always relative*/
		thisTicker.parent().css({
			position: "relative",
		});
		thisTicker
			.children()
			.first()
			.addClass("active"); /* ====  ADD active class to first item ===== */

		/* ==================================================
		horizontal and vertical Control
		===================================================== */
		{
			let sliderAutoPlay = "";
			if (opts.type == "horizontal" || opts.type == "vertical") {
				sliderAutoPlay = setInterval(function () {
					slidePlay();
				}, opts.speed);
			}

			/* =====  Prev Func  ===== */
			$(opts.controls.prev).on("click", function () {
				if (opts.type == "horizontal" || opts.type == "vertical") {
					clearInterval(sliderAutoPlay);
					handleSlider("prev");
					if (pauseVal) {
						sliderAutoPlay = setInterval(function () {
							slidePlay();
						}, opts.speed);
					}
				}
			});

			/* =====  Next Event  ===== */
			$(opts.controls.next).on("click", function () {
				if (opts.type == "horizontal" || opts.type == "vertical") {
					clearInterval(sliderAutoPlay);
					handleSlider("next");
					if (pauseVal) {
						sliderAutoPlay = setInterval(function () {
							slidePlay();
						}, opts.speed);
					}
				}
			});

			/* =====  Pause Event  ===== */
			$(opts.controls.toggle).on("click", function () {
				if (opts.type == "horizontal" || opts.type == "vertical") {
					if (pauseVal) {
						pauseVal = false;
						clearInterval(sliderAutoPlay);
					} else {
						pauseVal = true;
						clearInterval(sliderAutoPlay);
						sliderAutoPlay = setInterval(function () {
							slidePlay();
						}, opts.speed);
					}
				}
			});

			/* ===== Hover Pause Event  ===== */
			if (opts.pauseOnHover) {
				thisTicker.on("mouseenter", function () {
					if (opts.type === "horizontal" || opts.type === "vertical") {
						clearInterval(sliderAutoPlay);
					}
				});

				thisTicker.on("mouseleave", function () {
					if (
						(opts.type === "horizontal" || opts.type === "vertical") &&
						pauseVal
					) {
						sliderAutoPlay = setInterval(function () {
							slidePlay();
						}, opts.speed);
					}
				});
			}
		}

		/* =====================================
			Next Previous Auto Play Control
		===================================== */
		function handleSlider(val) {
			let slideIndex = thisTicker.find(".active").index();
			if (slideIndex < 0) {
				slideIndex = 0;
			}
			let index = 1;
			if (val == "prev") {
				thisTicker.children().eq(slideIndex).removeClass("active");
				thisTicker
					.children()
					.eq(slideIndex - index)
					.addClass("active");
			}
			if (val == "next") {
				thisTicker.children().eq(slideIndex).removeClass("active");
				if (slideIndex == thisTicker.children().length - 1) {
					index = -(thisTicker.children().length - 1);
				}
				thisTicker
					.children()
					.eq(slideIndex + index)
					.addClass("active");
			}
		}

		/* =======  Auto Slide Function   ======= */
		function slidePlay() {
			let index = 1;
			let slideIndex = thisTicker.find(".active").index();
			if (slideIndex < 0) {
				slideIndex = 0;
			}
			thisTicker.children().eq(slideIndex).removeClass("active");
			if (slideIndex == thisTicker.children().length - 1) {
				index = -(thisTicker.children().length - 1);
			}
			thisTicker
				.children()
				.eq(slideIndex + index)
				.addClass("active");
		}
	};

	/* ====================
		Default Value
	===================== */
	$.fn.UltpSlider.defaults = {
		type: "horizontal" /*vertical/horizontal*/,
		autoplay: 2000 /*true/false/number*/ /*For vertical/horizontal 4000*/,
		speed: 50 /*true/false/number*/ /*For vertical/horizontal 600*/,
		direction: "up" /*up/down/left/right*/ /*For vertical up/down*/ /*For horizontal right/left*/,
		pauseOnFocus: true,
		pauseOnHover: true,
		controls: {
			prev: "",
			next: "",
			toggle: "",
		},
	};
})(jQuery);
