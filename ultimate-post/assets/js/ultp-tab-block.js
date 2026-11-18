(function ($) {
	("use strict");
	const isFront = $("body.postx-admin-page").length == 0;

	if (isFront && $(".wp-block-ultimate-post-tabs").length) {
		handleTabBlock();
		$(window).on("resize", function () {
			if ($(".wp-block-ultimate-post-tabs").length) {
				handleTabBlock();
			}
		});
	}
	// Tab Block Function
	function handleTabBlock() {
		const rootTabSelector = (element) =>
			element.closest(".wp-block-ultimate-post-tabs");

		$(".wp-block-ultimate-post-tabs").each(function () {
			let tab = $(this);
			const responsiveType = tab.data("responsive");
			const tabNavMain = tab.find(".ultp-tabs-nav").first();

			const tabContent = tab
				.find(".ultp-tab-content")
				.first()
				.children(".wp-block-ultimate-post-tab-item");

			// Navigation at the top or bottom runs across; any other (left) runs down.
			const isSideNav = function () {
				return !$(this).is(".ultp-nav-top, .ultp-nav-bottom");
			};
			const isTabVertical =
				tab.children().children(".ultp-tab-wrapper").filter(isSideNav)
					.length > 0;

			if (tab.parent(".wp-block-ultimate-post-tab-item").length > 0) {
				tab.closest(".ultp-tab-content").css({ overflow: "hidden" });
			}

			// Responsive
			if (responsiveType == "slider" && tab.width() < 600) {
				tab.find(".ultp-tabs-nav").css({ flexWrap: "nowrap" });
			}

			// Accordion
			if (
				tab.width() < 600 &&
				tab.data("responsive") == "accordion" &&
				!tab.hasClass("ultp-tab-accordion-active")
			) {
				tab.addClass("ultp-tab-accordion-active");
				tab.find(".ultp-tabs-nav-element").each(function (i) {
					if ($(this).data("order")) {
						$(this).css({ order: (i + 1) * 2 - 1 });
					}
				});
				tabContent.each(function (i) {
					$(this).css({ order: (i + 1) * 2 });
				});
			}

			// Active Accordion
			if (tab.hasClass("ultp-tab-accordion-active")) {
				tabNavMain
					.css("display", "contents")
					.parent()
					.css("display", "contents");
				tabContent
					.addClass("ultp-tab-content")
					.parent()
					.css("display", "contents");
			}

			// initial active - Clear all active states first
			if (tabContent.filter(".active").length === 0) {
				const activeTabIndex = tab.data("activetab");
				tabContent.each(function () {
					if ($(this).data("tabindex") == activeTabIndex) {
						$(this).addClass("active");
						tab.find(".ultp-tabs-nav-element").each(function () {
							if ($(this).data("tabindex") == activeTabIndex) {
								$(this).addClass("ultp-tab-active");
							}
						});
					}
				});
			}

			const tabNavContainer = tab.find(".ultp-tabs-nav-wrapper");
			const leftArrow = tab.find(".ultp-tab-left-arrow").first();
			const rightArrow = tab.find(".ultp-tab-right-arrow").first();

			// "On Hover" tabs change on mouseenter, all others on click.
			const tabevent =
				tab.data("tabevent") == "mouseenter" ? "mouseenter" : "click";

			let tabOverflow =
				responsiveType == "slider" ||
				tab.find(".ultp-tab-wrapper").filter(isSideNav).length > 0;

			let navContainerSize = isTabVertical
				? tabNavMain.height()
				: tabNavMain.width();

			let singleElementWidth = navContainerSize / tabNavMain?.children().length;
			let navParenTContainer = isTabVertical
				? tabNavContainer.height()
				: tabNavContainer.width();

			let shiftValue = 0;
			// let SlideshitValue = singleElementWidth;

			// on Click/Hover Element Show
			tab
				.find(".ultp-tabs-nav-element")
				.off(tabevent)
				.on(tabevent, function (event) {
					event.stopPropagation();

					const navContent = $(this);

					navContent
						.addClass("ultp-tab-active")
						.siblings()
						.removeClass("ultp-tab-active");

					rootTabSelector($(this))
						.find(".wp-block-ultimate-post-tab-item")
						.each(function () {
							$(this).removeClass("active");
							if ($(this).data("tabindex") == navContent.data("tabindex")) {
								$(this).addClass("active");
							}
						});

					navContainerSize = isTabVertical
						? tabNavMain.height()
						: tabNavMain.width();

					singleElementWidth = navContainerSize / tabNavMain?.children().length;

					navParenTContainer = isTabVertical
						? tabNavContainer.height()
						: tabNavContainer.width();

					if (navContainerSize > navParenTContainer && tabOverflow) {
						handleTabArrowControl();
					}
				});

			if (navContainerSize > navParenTContainer && tabOverflow) {
				handleTabArrowControl();
			}

			// Left Right Arrow Slide
			function handleTabArrowControl() {
				if (shiftValue == 0) {
					rightArrow.addClass("ultp-arrow-active");
				}
				rightArrow.off("click").on("click", function (event) {
					event.stopPropagation();
					const parentSize = isTabVertical
						? $(this).closest(".ultp-tabs-nav-wrapper").height()
						: $(this).closest(".ultp-tabs-nav-wrapper").width();

					const size = isTabVertical
						? $(this).siblings().find(".ultp-tabs-nav").height()
						: $(this).siblings().find(".ultp-tabs-nav").width();

					const shiftValueLimit = size - parentSize;

					const singleWidth =
						size / $(this).siblings().find(".ultp-tabs-nav")?.children().length;

					if (
						shiftValueLimit > shiftValue &&
						shiftValueLimit - shiftValue > singleWidth
					) {
						shiftValue = shiftValue + singleWidth;
					}

					if (shiftValueLimit - shiftValue < singleWidth + 1) {
						shiftValue = shiftValue + (shiftValueLimit - shiftValue);
						$(this).removeClass("ultp-arrow-active");
					}

					if (shiftValue > 0) {
						$(this)
							.siblings(".ultp-tab-left-arrow")
							.addClass("ultp-arrow-active");
					}

					let transformStyle = isTabVertical
						? `translate(0px, -${shiftValue}px)`
						: `translate(-${shiftValue}px, 0px)`;

					$(this)
						.siblings()
						.find(".ultp-tabs-nav")
						.css({ transform: transformStyle });
				});

				leftArrow.off("click").on("click", function (event) {
					const size = isTabVertical
						? $(this).siblings().find(".ultp-tabs-nav").height()
						: $(this).siblings().find(".ultp-tabs-nav").width();

					const singleWidth =
						size / $(this).siblings().find(".ultp-tabs-nav")?.children().length;

					if (shiftValue > singleWidth) {
						shiftValue = shiftValue - singleWidth;
					} else {
						shiftValue = 0;
					}

					if (shiftValue > 0) {
						$(this)
							.siblings(".ultp-tab-right-arrow")
							.addClass("ultp-arrow-active");
					} else {
						$(this).removeClass("ultp-arrow-active");
					}

					let transformStyle = isTabVertical
						? `translate(0px, -${shiftValue}px)`
						: `translate(-${shiftValue}px, 0px)`;

					$(this)
						.siblings()
						.find(".ultp-tabs-nav")
						.css({ transform: transformStyle });
				});
			}

			// The block is set up (again after each resize). An add-on that changes tabs on its own
			// gets the pieces it needs from this event; the same object stays on the block as
			// `ultpTabBlock` for a script that loads after this one.
			const tabBlock = {
				tab: tab,
				tabContent: tabContent,
				tabNavMain: tabNavMain,
				tabNavContainer: tabNavContainer,
				leftArrow: leftArrow,
				rightArrow: rightArrow,
				isTabVertical: isTabVertical,
				tabOverflow: tabOverflow,
				responsiveType: responsiveType,
			};
			tab.data("ultpTabBlock", tabBlock);
			$(document).trigger("ultp_tab_block_ready", [tabBlock]);
		});
	}
})(jQuery);
