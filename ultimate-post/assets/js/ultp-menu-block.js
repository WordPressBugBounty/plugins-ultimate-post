(function ($) {
	("use strict");
	/*************************************
         Menu Block
      *************************************/
	// The menu, list menu and mobile view. Blocks an add-on places inside a menu item position
	// themselves.

	/*
          Menu responsive
      */
	const isFront = $("body.postx-admin-page").length == 0;
	let currentStack = "";
	let prevStack = [];
	let toAppend;
	let toAppendBack;
	let rawMenu;
	let mv_rcsstype;
	let mv_rstr;
	let mv_naviIcon;
	let mv_animationDuration;
	let mv_HeadText;
	let mv_dropIconArray = [];
	if (isFront) {
		$(".wp-block-ultimate-post-menu").each(function () {
			const that = $(this);
			if (that.data("hasrootmenu") != "hasRootMenu") {
				that
					.find(
						`.ultp-menu-item-wrapper[data-parentbid=".ultp-block-${that?.data(
							"bid"
						)}"] > .ultp-menu-item-label-container a`
					)
					.each(function () {
						const theA = $(this);
						const currentUrl = window.location.href;
						let isActive = false;
						const targetUrl = theA[0].href;
						if (currentUrl.endsWith("/") && !targetUrl.endsWith("/")) {
							const normalizedTargetUrl = targetUrl + "/";
							if (
								currentUrl.replace("https:", "http:") ==
								normalizedTargetUrl.replace("https:", "http:")
							) {
								isActive = true;
							}
						}
						if (
							currentUrl.replace("https:", "http:") ==
								targetUrl.replace("https:", "http:") ||
							isActive
						) {
							theA
								.closest(
									`.ultp-menu-item-wrapper[data-parentbid=".ultp-block-${that?.data(
										"bid"
									)}"]`
								)
								.addClass("ultp-current-link");
						}
					});
			}
		});
		$(document).on(
			"click",
			'.wp-block-ultimate-post-menu[data-mv="enable"] > .ultp-mv-ham-icon.ultp-active',
			function (e) {
				enableDisableMolibeView(e, "ham");
			}
		);
		$(document).on(
			"click",
			".ultp-mobile-view-container .ultp-mv-back, .ultp-mobile-view-container .ultp-mv-back-label-con",
			function (e) {
				if (!menuNavigationHandled(e, "back")) {
					handleResponsiveMenuHtml(e, "back");
				}
			}
		);
		$(document).on(
			"click",
			".ultp-mobile-view-container .ultp-mv-close",
			function (e) {
				enableDisableMolibeView(e, "close");
			}
		);
		$(document).on("click", ".ultp-mobile-view-container", function (e) {
			if ($(e.target).hasClass("ultp-mobile-view-container")) {
				enableDisableMolibeView(e, "close");
			}
		});
		$(document).on(
			"click",
			".ultp-mobile-view-container .ultp-menu-item-label-container",
			function (e) {
				if (
					$(e.target).is(".ultp-menu-item-label") ||
					$(e.target).parent().is(".ultp-menu-item-label") ||
					($(e.target).is(".ultp-menu-item-label-container") &&
						$(e.target).siblings(".ultp-menu-item-content").find("> *")
							.length == 0)
				) {
					return;
				}
				e.preventDefault();
				if (!menuNavigationHandled(e, "next")) {
					handleResponsiveMenuHtml(e, "next");
				}
			}
		);
	}

	// The mobile view slides from one level to the next. An add-on may run another navigation
	// effect (the block's `data-rstr`): it handles `ultp_menu_navigate` or `ultp_menu_swap` and
	// calls preventDefault().
	function menuNavigationContext(direction) {
		return {
			effect: mv_rstr,
			direction: direction,
			menu: rawMenu,
			duration: mv_animationDuration,
			naviIcon: mv_naviIcon,
		};
	}
	function menuNavigationHandled(e, direction) {
		const navigateEvent = $.Event("ultp_menu_navigate");
		$(document).trigger(navigateEvent, [
			Object.assign(menuNavigationContext(direction), { event: e }),
		]);
		return navigateEvent.isDefaultPrevented();
	}
	function menuSwapHandled(direction, html) {
		const swapEvent = $.Event("ultp_menu_swap");
		$(document).trigger(swapEvent, [
			Object.assign(menuNavigationContext(direction), {
				container: toAppend,
				html: html,
			}),
		]);
		return swapEvent.isDefaultPrevented();
	}

	function enableDisableMolibeView(e, type = "") {
		// ham close
		if (type == "close") {
			$(rawMenu)
				.find("> .ultp-mobile-view-container > .ultp-mobile-view-wrapper")
				.css({
					transform: "translateX(-100%)",
					visibility: "hidden",
					opacity: "0",
				});
			setTimeout(() => {
				if ($(rawMenu).hasClass("ultpMenu__Css")) {
					$(rawMenu).addClass("ultpMenuCss");
					$(rawMenu).removeClass("ultpMenu__Css");
				}
				$(rawMenu).removeClass("ultp-mobile-menu");
				$(rawMenu)
					.find("> .ultp-mobile-view-container")
					.removeClass("ultp-mv-active");
				$(rawMenu)
					.find("> .ultp-mobile-view-container > .ultp-mobile-view-wrapper")
					.css({
						transform: "",
						visibility: "",
						opacity: "",
						"transition-property": "",
						"transition-timing-function": "",
						"transition-duration": "",
					});
				toAppend?.html("");
				currentStack = "";
				prevStack = [];
				toAppend = "";
				toAppendBack = "";
				rawMenu = "";
				mv_rcsstype = "";
				mv_rstr = "";
				mv_animationDuration = 0;
				mv_HeadText = "";
				mv_dropIconArray = [];
			}, mv_animationDuration);
		} else {
			// ham
			const that = $(e.target);
			mv_animationDuration = $(that).hasClass("ultp-mv-ham-icon")
				? $(that).data("animationduration")
				: that.closest(".ultp-mv-ham-icon").data("animationduration");
			mv_animationDuration = mv_animationDuration || 100;
			mv_HeadText = $(that).hasClass("ultp-mv-ham-icon")
				? $(that).data("headtext")
				: that.closest(".ultp-mv-ham-icon").data("headtext");
			mv_naviIcon = that
				.closest(".wp-block-ultimate-post-menu")
				.find(
					"> .ultp-mobile-view-container > .ultp-mv-icons > .ultp-mv-label-icon svg"
				)
				.prop("outerHTML");

			rawMenu = that.closest(".wp-block-ultimate-post-menu");
			const menuObj = $(rawMenu);
			if ($(rawMenu).hasClass("ultpMenuCss")) {
				$(rawMenu).removeClass("ultpMenuCss");
				$(rawMenu).addClass("ultpMenu__Css");
			}
			currentStack = "ultp-block-" + menuObj.data("bid");
			menuObj.addClass("ultp-mobile-menu");
			menuObj.find("> .ultp-mobile-view-container").addClass("ultp-mv-active");
			handleResponsiveMenuHtml("", "hamIcon");

			menuObj
				.find("> .ultp-mobile-view-container > .ultp-mobile-view-wrapper")
				.css({
					"transition-property": "opacity, visibility, transform",
					"transition-timing-function": "ease-in",
					"transition-duration": mv_animationDuration
						? mv_animationDuration / 1000 + "s"
						: ".25s",
				});
		}
	}
	function handleDropIcon(data) {
		const _replace = data?._replace || mv_naviIcon;
		let _string = data?._string;
		if (_string && mv_dropIconArray.length) {
			mv_dropIconArray.forEach((el) => {
				if (el && _replace) {
					_string = _string.replace(el, _replace);
				}
			});
		}
		return _string;
	}
	function handleResponsiveMenuHtml(e, src) {
		// hamIcon next back
		if (src == "hamIcon") {
			mv_rcsstype = rawMenu.data("rcsstype");
			mv_rstr = rawMenu.data("rstr");
			toAppend = $(rawMenu).find(
				"> .ultp-mobile-view-container .ultp-mobile-view-body"
			);
			toAppendBack = $(rawMenu).find(
				"> .ultp-mobile-view-container .ultp-mv-back-label"
			);
			let theHtml = $(rawMenu)
				.find("> .ultp-menu-wrapper > .ultp-menu-content")
				.html();

			$(rawMenu)
				.find(".ultp-menu-item-dropdown")
				.toArray()
				.forEach((element) => {
					if ($(element).html()) {
						mv_dropIconArray.push($(element).html());
					}
				});
			if (theHtml) {
				let tempHtml = $("<div>").html(theHtml);
				tempHtml
					.find(".wp-block-ultimate-post-menu")
					.addClass("ultp-mobile-menu");
				theHtml = tempHtml.html();

				theHtml = handleDropIcon({ type: "hamicon", _string: theHtml });
				toAppend.html(
					mv_rcsstype == "custom"
						? theHtml.replaceAll("ultpMenuCss", "ultpMenu__Css")
						: theHtml
				);
				toAppendBack.text(mv_HeadText);
			}
		} else if (src == "next") {
			const that = $(e.target);
			const parentItem = that.closest(".wp-block-ultimate-post-menu-item");
			const bid = parentItem.data("bid");
			if (!prevStack.includes("ultp-block-" + bid)) {
				let theHtml = "";
				let d_none = "";
				if (parentItem.hasClass("hasListMenuChild")) {
					d_none = parentItem
						.find(
							"> .ultp-menu-item-wrapper > .ultp-menu-item-content > .wp-block-ultimate-post-list-menu"
						)
						.css("display");
					theHtml = parentItem
						.find(
							"> .ultp-menu-item-wrapper > .ultp-menu-item-content > .wp-block-ultimate-post-list-menu > .ultp-list-menu-wrapper > .ultp-list-menu-content"
						)
						.html();
				} else {
					// Whatever block the item opens (an add-on may supply one); hidden means no next level.
					d_none = parentItem
						.find("> .ultp-menu-item-wrapper > .ultp-menu-item-content > *")
						.first()
						.css("display");
					theHtml = parentItem
						.find("> .ultp-menu-item-wrapper > .ultp-menu-item-content")
						.html();
				}
				if (d_none == "none") {
					return;
				}
				if (theHtml) {
					$(rawMenu)
						.find(".ultp-mv-back-label-con")
						.removeClass("ultpmenu-dnone");
					let tempHtml = $("<div>").html(theHtml);
					tempHtml
						.find(".wp-block-ultimate-post-menu")
						.addClass("ultp-mobile-menu");
					theHtml = tempHtml.html();

					prevStack.push(currentStack);
					currentStack = "ultp-block-" + bid;
					toAppendBack.html(
						parentItem
							.find(
								"> .ultp-menu-item-wrapper > .ultp-menu-item-label-container .ultp-menu-item-label-text"
							)
							.html()
					);

					const nextHtml =
						mv_rcsstype == "custom"
							? theHtml.replaceAll("ultpMenuCss", "ultpMenu__Css")
							: theHtml;
					if (!menuSwapHandled("next", nextHtml)) {
						// Slide View
						toAppend.html(nextHtml);
						toAppend.find("> *").css({
							opacity: ".1",
							transform: "translateX(100%)",
							transition: `transform ${mv_animationDuration / 1000 + "s"} ease`,
						});
						toAppend.find("> *").animate(
							{
								opacity: 0.3,
							},
							10,
							function () {
								toAppend
									.find("> *")
									.css({ opacity: "1", transform: "translateX(0px)" });
							}
						);
					}
				}
			}
		} else if (src == "back") {
			if (prevStack.length == 0) {
				return;
			}
			currentStack = prevStack.pop() || "";
			let backHtml = "";
			if (prevStack.length == 0) {
				backHtml = $(rawMenu)
					.find("> .ultp-menu-wrapper > .ultp-menu-content")
					.html();
				toAppendBack.text(mv_HeadText);
				$(rawMenu).find(".ultp-mv-back-label-con").addClass("ultpmenu-dnone");
			} else {
				if (
					$("." + currentStack).hasClass("wp-block-ultimate-post-menu-item")
				) {
					if ($("." + currentStack).hasClass("hasListMenuChild")) {
						backHtml = $(rawMenu)
							.find("." + currentStack)
							.find(
								"> .ultp-menu-item-wrapper > .ultp-menu-item-content > .wp-block-ultimate-post-list-menu > .ultp-list-menu-wrapper > .ultp-list-menu-content"
							)
							.html();
					} else {
						backHtml = $(rawMenu)
							.find("." + currentStack)
							.find("> .ultp-menu-item-wrapper > .ultp-menu-item-content")
							.html();
					}
				}
			}
			if (backHtml) {
				let tempHtml = $("<div>").html(backHtml);
				tempHtml
					.find(".wp-block-ultimate-post-menu")
					.addClass("ultp-mobile-menu");
				backHtml = tempHtml.html();
				backHtml = handleDropIcon({ type: "back", _string: backHtml });
				toAppendBack.html(
					$(rawMenu)
						.find("." + currentStack)
						.find(
							"> .ultp-menu-item-wrapper > .ultp-menu-item-label-container .ultp-menu-item-label-text"
						)
						.html()
				);

				const prevHtml =
					mv_rcsstype == "custom"
						? backHtml.replaceAll("ultpMenuCss", "ultpMenu__Css")
						: backHtml;
				if (!menuSwapHandled("back", prevHtml)) {
					// Slide View
					toAppend.html(prevHtml);
					toAppend.find("> *").css({
						opacity: ".1",
						transform: "translateX(-100%)",
						transition: `transform ${mv_animationDuration / 1000 + "s"} ease`,
					});
					toAppend.find("> *").animate(
						{
							opacity: 0.3,
						},
						10,
						function () {
							toAppend
								.find("> *")
								.css({ opacity: "1", transform: "translateX(0px)" });
						}
					);
				}
			}
		}
		// console.log(prevStack, currentStack);
	}
})(jQuery);
