(function ($) {
	"use strict";

	// -------------------------------
	// ---- Addon Submenu Items ------
	// -------------------------------
	// Show or hide the Builder, Saved Templates and Custom Font submenu items to match
	// their addon setting. The dashboard (Addons.js) toggles them later by these ids.
	const addonSubmenus =
		(typeof ultp_admin_menu !== "undefined" &&
			ultp_admin_menu.addon_submenus) ||
		{};
	Object.keys(addonSubmenus).forEach(function (slug) {
		const link = document.querySelector(
			'#adminmenu a[href*="page=ultp-settings#' + slug + '"]',
		);
		const item = link ? link.closest("li") : null;

		if (!item) {
			return;
		}

		item.id = "postx-submenu-" + slug;
		item.style.display = addonSubmenus[slug] ? "" : "none";
	});

	// *************************************
	// Add target blank for upgrade button
	// *************************************
	const postxMenuHref = $("#toplevel_page_ultp-settings > a").attr("href");
	if (postxMenuHref?.indexOf("?page=ultp-settings") > 0) {
		$("#toplevel_page_ultp-settings > a").attr("href", postxMenuHref + "#home");
	}

	// *************************************
	// Add URL for PostX
	// *************************************
	$(document).on(
		"click",
		"#plugin-information-content ul > li > a",
		function (e) {
			const URL = $(this).attr("href");
			if (URL.includes("downloads/gutenberg-post-blocks-pro")) {
				e.preventDefault();
				window.open("https://www.wpxpo.com/postx/");
			}
		},
	);
})(jQuery);
