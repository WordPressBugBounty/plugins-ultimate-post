(function ($) {
	"use strict";

	const isFront = $("body.postx-admin-page").length == 0;
	// *************************************
	// Nonce Generation for AJAX
	// *************************************
	const ULTP_NONCE_KEY = "ultp_public_nonce";

	// sessionStorage throws outright when the browser blocks site data (private
	// windows, cookie-blocking extensions). Guard every access so that degrades to
	// "fetch a nonce per request" instead of breaking the block entirely.
	function readStoredNonce() {
		try {
			return sessionStorage.getItem(ULTP_NONCE_KEY);
		} catch (e) {
			return null;
		}
	}

	function storeNonce(value) {
		try {
			sessionStorage.setItem(ULTP_NONCE_KEY, value);
		} catch (e) {
			// Not cacheable in this browser; every call refetches instead.
		}
	}

	function clearStoredNonce() {
		try {
			sessionStorage.removeItem(ULTP_NONCE_KEY);
		} catch (e) {
			// Nothing was stored, so nothing to clear.
		}
	}

	async function getUltpNonce(forceRefresh = false) {
		if (!forceRefresh) {
			const nonce = readStoredNonce();
			if (nonce) return nonce;
		}

		try {
			const response = await $.ajax({
				url: ultp_data_frontend.ajax,
				type: "POST",
				data: { action: "ultp_get_nonce" },
			});

			if (response?.success && response?.data?.nonce) {
				storeNonce(response.data.nonce);
				return response.data.nonce;
			}

			console.log("Failed to fetching nonce");
			return null;
		} catch (xhr) {
			console.log(
				"Error occured.please try again" + xhr.statusText + xhr.responseText,
			);
			console.log("Nonce Not Generating Properly");
			return null;
		}
	}

	// A front-end nonce goes stale in normal use: WordPress nonces last 12-24h and
	// a tab can stay open longer, a full-page cache can serve HTML carrying a nonce
	// minted for an earlier visitor, and a plugin update can change the action name
	// under a tab that is already open. All three look identical from here, and
	// before this existed all three broke the block with no way back short of a
	// reload. PHP marks exactly this case with ultp_invalid_nonce.
	function isExpiredNonce(xhr) {
		return (
			xhr?.status === 403 &&
			xhr?.responseJSON?.data?.code === "ultp_invalid_nonce"
		);
	}

	/**
	 * $.ajax for the nonce-guarded front-end endpoints.
	 *
	 * Takes the same options as $.ajax plus `nonce`, the value to try first, and
	 * injects it as `wpnonce`. On an expired-nonce 403 it discards the cached
	 * nonce, fetches a fresh one and replays the request once. The retry is
	 * invisible to the caller: `success` runs only for the attempt that worked,
	 * and `complete`/`error` are suppressed on an attempt that is being retried,
	 * so loading states are not torn down half way.
	 */
	function ultpAjax(options) {
		const {
			nonce: initialNonce,
			data = {},
			success,
			error,
			complete,
			...rest
		} = options;

		let retried = false;

		function fail(xhr, ...args) {
			if (typeof error === "function") error.call(this, xhr, ...args);
			if (typeof complete === "function") complete.call(this, xhr, ...args);
		}

		function send(nonce) {
			// Set per attempt, not per call: it must not leak into the retry.
			let retrying = false;

			$.ajax({
				...rest,
				data: { ...data, wpnonce: nonce },
				success,
				error(xhr, ...args) {
					if (!retried && isExpiredNonce(xhr)) {
						retried = true;
						retrying = true;
						clearStoredNonce();
						getUltpNonce(true).then((fresh) => {
							if (fresh) {
								send(fresh);
							} else {
								fail.call(this, xhr, ...args);
							}
						});
						return;
					}
					if (typeof error === "function") error.call(this, xhr, ...args);
				},
				complete(xhr, ...args) {
					if (retrying) return;
					if (typeof complete === "function") complete.call(this, xhr, ...args);
				},
			});
		}

		// Prefer a nonce fetched live over whatever the caller passed. A caller's
		// nonce comes either from page HTML, which a full-page cache can serve
		// stale, or from a value captured once at init and reused for the life of
		// the page (the infinite scroll script does this). Reading storage first costs
		// nothing and stops those callers replaying a known-dead nonce forever.
		const storedNonce = readStoredNonce();
		if (storedNonce || initialNonce) {
			send(storedNonce || initialNonce);
			return;
		}

		getUltpNonce().then((nonce) => {
			if (nonce) {
				send(nonce);
			} else {
				fail({ status: 0, statusText: "no nonce", responseText: "" });
			}
		});
	}

	// *************************************
	// Social Share window
	// *************************************
	$(".ultp-post-share-item a").each(function () {
		$(this).on("click", function () {
			// For Share window opening
			let share_url = $(this).attr("url");
			let width = 800;
			let height = 500;
			let leftPosition, topPosition;
			//Allow for borders.
			leftPosition = window.screen.width / 2 - (width / 2 + 10);
			//Allow for title and status bars.
			topPosition = window.screen.height / 2 - (height / 2 + 50);
			let windowFeatures =
				"height=" +
				height +
				",width=" +
				width +
				",resizable=yes,left=" +
				leftPosition +
				",top=" +
				topPosition +
				",screenX=" +
				leftPosition +
				",screenY=" +
				topPosition;
			window.open(share_url, "sharer", windowFeatures);
			// For Share count add
			let id = $(this)
				.parents(".ultp-post-share-item-inner-block")
				.attr("postId");
			let count = $(this)
				.parents(".ultp-post-share-item-inner-block")
				.attr("count");
			ultpAjax({
				url: ultp_data_frontend.ajax,
				type: "POST",
				nonce: ultp_data_frontend.security,
				data: {
					action: "ultp_share_count",
					shareCount: count,
					postId: id,
				},
				error: function (xhr) {
					console.log(
						"Error occured.please try again" +
							xhr.statusText +
							xhr.responseText,
					);
				},
			});

			return false;
		});
	});
	// remove sticky behavior when footer is visible
	$(window).on("scroll", function () {
		if (
			$(window).scrollTop() + window.innerHeight >=
			$("footer")?.offset()?.top
		) {
			$(
				".wp-block-ultimate-post-post_share .ultp-block-wrapper .ultp-disable-sticky-footer",
			).addClass("remove-sticky");
		} else {
			$(
				".wp-block-ultimate-post-post_share .ultp-block-wrapper .ultp-disable-sticky-footer",
			).removeClass("remove-sticky");
		}
	});

	// *************************************
	// News Ticker
	// *************************************
	$(".ultp-news-ticker").each(function () {
		$(this).UltpSlider({
			type: $(this).data("type"),
			direction: $(this).data("direction"),
			speed: $(this).data("speed"),
			pauseOnHover: $(this).data("hover") == 1 ? true : false,
			controls: {
				prev: $(this)
					.closest(".ultp-newsTicker-wrap")
					.find(".ultp-news-ticker-prev"),
				next: $(this)
					.closest(".ultp-newsTicker-wrap")
					.find(".ultp-news-ticker-next"),
				toggle: $(this)
					.closest(".ultp-newsTicker-wrap")
					.find(".ultp-news-ticker-pause"),
			},
		});
	});

	// *************************************
	// Table of Contents
	// *************************************
	$(".ultp-toc-backtotop").on("click", function (e) {
		e.preventDefault();
		$("html, body").animate({ scrollTop: 0 }, "slow");
	});

	$(window).on("scroll", function () {
		scrollTopButton();
	});

	function scrollTopButton() {
		if ($(document).scrollTop() > 1000) {
			$(".ultp-toc-backtotop").addClass("tocshow");
		} else {
			$(".ultp-toc-backtotop").removeClass("tocshow");
		}
	}
	scrollTopButton();

	$(".ultp-collapsible-open").on("click", function (e) {
		$(this)
			.closest(".ultp-collapsible-toggle")
			.removeClass("ultp-toggle-collapsed");
		$(this).parents(".ultp-block-toc").find(".ultp-block-toc-body").show();
	});

	$(".ultp-collapsible-hide").on("click", function (e) {
		$(this)
			.closest(".ultp-collapsible-toggle")
			.addClass("ultp-toggle-collapsed");
		$(this).parents(".ultp-block-toc").find(".ultp-block-toc-body").hide();
	});

	$(".ultp-toc-lists li a").on("click", function () {
		$([document.documentElement, document.body]).animate(
			{
				scrollTop: $($(this).attr("href")).offset().top - 50,
			},
			500,
		);
	});

	// *************************************
	// Flex Menu
	// *************************************
	$(document).ready(function () {
		if ($(".ultp-flex-menu").length > 0) {
			const menuText = $("ul.ultp-flex-menu").data("name");
			$("ul.ultp-flex-menu").flexMenu({
				linkText: menuText,
				linkTextAll: menuText,
				linkTitle: menuText,
			});
		}
	});
	$(document).on("click", function (e) {
		if ($(e.target).closest(".flexMenu-viewMore").length === 0) {
			$(".flexMenu-viewMore").removeClass("active");
			$(".flexMenu-viewMore")
				.children("ul.flexMenu-popup")
				.css("display", "none");
		}
	});
	$(document).on(
		"click",
		".ultp-filter-navigation .flexMenu-popup .filter-item",
		function (e) {
			$(".flexMenu-viewMore").removeClass("active");
			$(".flexMenu-viewMore")
				.children("ul.flexMenu-popup")
				.css("display", "none");
		},
	);

	// *************************************
	// Pagination Block
	// *************************************
	$(".ultp-post-grid-parent").each(function () {
		const grid = $(this).find(".ultp-post-grid-block");
		const pagiWrapper = $(this).find(".ultp-pagination-block");
		const pagi = grid.find(".pagination-block-html > div");

		if (pagiWrapper.length < 1 || pagi.length < 1) {
			return;
		}

		pagiWrapper
			.attr("class")
			.split(" ")
			.forEach((cl) => {
				pagi.addClass(cl);
			});

		pagiWrapper.html(pagi);
	});

	// *************************************
	// Previous Next
	// *************************************

	$(document).off(
		"click",
		".ultp-pagination-ajax-action li, .ultp-loadmore-action, .ultp-prev-action, .ultp-next-action",
		function (e) {},
	);
	$(document).on("click", ".ultp-prev-action, .ultp-next-action", function (e) {
		e.preventDefault();
		let parents = $(this).closest(".ultp-next-prev-wrap"),
			wrap = parents
				.closest(".ultp-block-wrapper")
				.find(".ultp-block-items-wrap"),
			paged = parseInt(parents.data("pagenum")),
			pages = parseInt(parents.data("pages")),
			blockWrapper = parents.closest(".ultp-block-wrapper");

		const postBlock = parents.parents(".ultp-post-grid-parent");

		// Pagination block integration
		if (wrap.length < 1) {
			const pagiFor = parents.data("for");
			if (pagiFor) {
				wrap = $("." + pagiFor + " .ultp-block-items-wrap");
			}
		}

		if (parents.is(".ultp-disable-editor-click")) {
			return;
		}
		if ($(this).hasClass("ultp-prev-action")) {
			if ($(this).hasClass("ultp-disable")) {
				return;
			} else {
				paged--;
				parents.data("pagenum", paged);
				parents
					.find(".ultp-prev-action, .ultp-next-action")
					.removeClass("ultp-disable");
				if (paged == 1) {
					$(this).addClass("ultp-disable");
				}
			}
		}
		if ($(this).hasClass("ultp-next-action")) {
			if ($(this).hasClass("ultp-disable")) {
				return;
			} else {
				paged++;
				parents.data("pagenum", paged);
				parents
					.find(".ultp-prev-action, .ultp-next-action")
					.removeClass("ultp-disable");
				if (paged == pages) {
					$(this).addClass("ultp-disable");
				}
			}
		}

		let post_ID =
			parents.parents(".ultp-shortcode").length != 0 &&
			parents.data("selfpostid") == "no"
				? parents.parents(".ultp-shortcode").data("postid")
				: parents.data("postid");

		if ($(this).closest(".ultp-builder-content").length > 0) {
			post_ID = $(this).closest(".ultp-builder-content").data("postid");
		}
		let widgetBlockId = "";
		let widgetBlock = $(this).parents(".widget_block:first");
		if (widgetBlock.length > 0) {
			let widget_items = widgetBlock.attr("id").split("-");
			widgetBlockId = widget_items[widget_items.length - 1];
		}

		// Adv Filter Integration
		const filterValue = parents.data("filter-value") || "";
		const advFilterData = {};

		if (Array.isArray(filterValue) && filterValue.length > 0) {
			advFilterData.filterShow = true;
			advFilterData.checkFilter = true;
			advFilterData.isAdv = true;
			advFilterData.author = parents.data("filter-author") || "";
			advFilterData.order = parents.data("filter-order") || "";
			advFilterData.orderby = parents.data("filter-orderby") || "";
			advFilterData.adv_sort = parents.data("filter-adv-sort") || "";
		}

		ultpAjax({
			url: ultp_data_frontend.ajax,
			type: "POST",
			nonce: ultp_data_frontend.security,
			data: postsRequestData(
				{
					action: "ultp_next_prev",
					paged: paged,
					blockId: parents.data("blockid"),
					postId: post_ID,
					exclude: parents.data("expost"),
					blockName: parents.data("blockname"),
					builder: parents.data("builder"),
					filterValue: filterValue,
					filterType: parents.data("filter-type") || "",
					widgetBlockId: widgetBlockId,

					...advFilterData,
				},
				wrap,
			),
			beforeSend: function () {
				if (blockWrapper.length < 1 && postBlock.length > 0) {
					postBlock.find(".ultp-block-wrapper").addClass("ultp-loading-active");
				} else {
					blockWrapper.addClass("ultp-loading-active");
				}
			},
			success: function (data) {
				if (data) {
					wrap.html(data);
					postsLoaded(wrap, "replace");
					if ($(window).scrollTop() > wrap.offset().top) {
						$([document.documentElement, document.body]).animate(
							{
								scrollTop: wrap.offset().top - 80,
							},
							100,
						);
					}
				}
			},
			complete: function () {
				if (blockWrapper.length < 1 && postBlock.length > 0) {
					postBlock
						.find(".ultp-block-wrapper")
						.removeClass("ultp-loading-active");
				} else {
					blockWrapper.removeClass("ultp-loading-active");
				}
				contentUpdated();
			},
			error: function (xhr) {
				console.log(
					"Error occured.please try again" + xhr.statusText + xhr.responseText,
				);
				parents
					.closest(".ultp-block-wrapper")
					.removeClass("ultp-loading-active");
			},
		});
	});

	// *************************************
	// Loadmore Append
	// *************************************

	$(document).on("click", ".ultp-loadmore-action", function (e) {
		if ($(this).is(".ultp-disable-editor-click")) {
			return;
		}
		e.preventDefault();
		let that = $(this);
		let parents = that.closest(".ultp-block-wrapper");
		let isPagiBlock = false;

		// Pagination block integration
		if (parents.length < 1) {
			const pagiFor = that.data("for");
			if (pagiFor) {
				parents = $("." + pagiFor + " .ultp-block-wrapper");
				isPagiBlock = parents.length > 0;
			}
		}

		let paged = parseInt(that.data("pagenum"));
		let pages = parseInt(that.data("pages"));

		if (that.hasClass("ultp-disable")) {
			return;
		} else {
			paged++;
			that.data("pagenum", paged);
			if (paged == pages) {
				$(this).addClass("ultp-disable");
			} else {
				$(this).removeClass("ultp-disable");
			}
		}

		let post_ID =
			that.parents(".ultp-shortcode").length != 0 &&
			that.data("selfpostid") == "no"
				? that.parents(".ultp-shortcode").data("postid")
				: that.data("postid");

		if (that.closest(".ultp-builder-content").length > 0) {
			post_ID = that.closest(".ultp-builder-content").data("postid");
		}
		let widgetBlockId = "";
		let widgetBlock = $(this).parents(".widget_block:first");
		if (widgetBlock.length > 0) {
			let widget_items = widgetBlock.attr("id").split("-");
			widgetBlockId = widget_items[widget_items.length - 1];
		}

		// Adv Filter Integration
		const filterValue = that.data("filter-value") || "";
		const advFilterData = {};

		if (Array.isArray(filterValue) && filterValue.length > 0) {
			advFilterData.filterShow = true;
			advFilterData.checkFilter = true;
			advFilterData.isAdv = true;
			advFilterData.author = that.data("filter-author") || "";
			advFilterData.order = that.data("filter-order") || "";
			advFilterData.orderby = that.data("filter-orderby") || "";
			advFilterData.adv_sort = that.data("filter-adv-sort") || "";
		}

		ultpAjax({
			url: ultp_data_frontend.ajax,
			type: "POST",
			nonce: ultp_data_frontend.security,
			data: postsRequestData(
				{
					action: "ultp_next_prev",
					paged: paged,
					blockId: that.data("blockid"),
					postId: post_ID,
					blockName: that.data("blockname"),
					builder: that.data("builder"),
					exclude: that.data("expost"),
					filterValue: filterValue,
					filterType: that.data("filter-type") || "",
					widgetBlockId: widgetBlockId,

					...advFilterData,
				},
				parents,
			),
			beforeSend: function () {
				parents.addClass("ultp-loading-active");

				if (isPagiBlock) {
					that.find(".ultp-spin").css("display", "flex");
				}
			},

			success: function (data) {
				if (data) {
					// Fix for grids that have fixed max-height (Adv Pagination Block integration)
					parents.find(".ultp-block-row").css("max-height", "unset");

					const insertPoint = parents.find(".ultp-loadmore-insert-before");

					if (insertPoint.length) {
						// Adv Pagination block loadmore fix for post modules
						if (that.data("blockname").includes("post-module")) {
							$(
								'<div style="clear:left;width:100%;padding-block:15px;"></div>',
							).insertBefore(insertPoint);
						}

						$(data).insertBefore(insertPoint);
					} else {
						const cardDiv = parents.find(".ultp-block-items-wrap");

						if (that.data("blockname").includes("post-module")) {
							cardDiv.append(
								$(
									'<div style="clear:left;width:100%;padding-block:15px;"></div>',
								),
							);
						}

						cardDiv.append(data);
					}

					postsLoaded(parents, "append");
				}
			},

			complete: function () {
				parents.removeClass("ultp-loading-active");

				if (isPagiBlock) {
					that.find(".ultp-spin").css("display", "none");
				}
			},

			error: function (xhr) {
				console.log(
					"Error occured.please try again" + xhr.statusText + xhr.responseText,
				);
				parents.removeClass("ultp-loading-active");

				if (isPagiBlock) {
					that.find(".ultp-spin").css("display", "none");
				}
			},
		});
	});

	// *************************************
	// Filter
	// *************************************
	$(document).on("click", ".ultp-filter-wrap li a", async function (e) {
		e.preventDefault();
		const ultpNonce = await getUltpNonce();
		if ($(this).closest("li").hasClass("filter-item")) {
			let that = $(this),
				parents = that.closest(".ultp-filter-wrap"),
				wrap = that.closest(".ultp-block-wrapper");
			const postBlock = that.parents(".ultp-post-grid-parent");

			parents.find("a").removeClass("filter-active");
			that.addClass("filter-active");
			if (parents.is(".ultp-disable-editor-click")) {
				return;
			}
			let post_ID =
				parents.parents(".ultp-shortcode").length != 0 &&
				parents.data("selfpostid") == "no"
					? parents.parents(".ultp-shortcode").data("postid")
					: parents.data("postid");

			if (that.closest(".ultp-builder-content").length > 0) {
				post_ID = that.closest(".ultp-builder-content").data("postid");
			}
			let widgetBlockId = "";
			let widgetBlock = $(this).parents(".widget_block:first");
			if (widgetBlock.length > 0) {
				let widget_items = widgetBlock.attr("id").split("-");
				widgetBlockId = widget_items[widget_items.length - 1];
			}

			if (parents.data("blockid")) {
				ultpAjax({
					url: ultp_data_frontend.ajax,
					type: "POST",
					nonce: ultpNonce,
					data: postsRequestData(
						{
							action: "ultp_filter",
							taxtype: parents.data("taxtype"),
							taxonomy: that.data("taxonomy"),
							blockId: parents.data("blockid"),
							postId: post_ID,
							blockName: parents.data("blockname"),
							widgetBlockId: widgetBlockId,
						},
						wrap,
					),
					beforeSend: function () {
						wrap.addClass("ultp-loading-active");
					},
					success: function (response) {
						wrap
							.find(".ultp-block-items-wrap")
							.html(response?.data?.filteredData?.blocks);
						if (
							response?.data?.filteredData?.paginationType == "loadMore" &&
							response?.data?.filteredData?.paginationShow
						) {
							// wrap.find('.ultp-block-items-wrap').append('<span class="ultp-loadmore-insert-before"></span>');

							wrap
								.find(".ultp-loadmore")
								.replaceWith(response?.data?.filteredData?.pagination);
						} else if (
							response?.data?.filteredData?.paginationType == "navigation"
						) {
							wrap
								.find(".ultp-next-prev-wrap")
								.replaceWith(response?.data?.filteredData?.pagination);
						} else if (
							response?.data?.filteredData?.paginationType == "pagination"
						) {
							wrap
								.find(".ultp-pagination-wrap")
								.replaceWith(response?.data?.filteredData?.pagination);
						}

						// Adv Pagination Block Integration
						if (
							response?.data?.filteredData?.pagination &&
							postBlock.length > 0
						) {
							postBlock.data("pagi")?.map((pagiClass) => {
								let pagi = [];

								if (
									response?.data?.filteredData?.paginationType === "loadMore"
								) {
									pagi = $(".ultp-loadmore." + pagiClass);
									if (pagi.length > 0) {
										// wrap.find('.ultp-block-items-wrap').append('<span class="ultp-loadmore-insert-before"></span>');
									}
								} else {
									pagi = $("." + pagiClass + "[data-for]");
								}

								if (pagi.length > 0) {
									const newPagi = $(response.data.filteredData.pagination);
									pagi
										.attr("class")
										.split(" ")
										.forEach((cl) => newPagi.addClass(cl));
									pagi.replaceWith(newPagi);
								}
							});
						}
					},
					complete: function () {
						wrap.removeClass("ultp-loading-active");
						postsLoaded(wrap, "replace");
						contentUpdated();
					},
					error: function (xhr) {
						console.log(
							"Error occured.please try again" +
								xhr.statusText +
								xhr.responseText,
						);
						wrap.removeClass("ultp-loading-active");
					},
				});
			}
		}
	});

	// *************************************
	// Pagination Number
	// *************************************
	function showHide(parents, pageNum, pages) {
		if (pageNum == pages) {
			parents.find(".ultp-next-page-numbers").hide();
		} else {
			parents.find(".ultp-next-page-numbers").show();
		}

		if (pageNum > 1) {
			parents.find(".ultp-prev-page-numbers").show();
		} else {
			parents.find(".ultp-prev-page-numbers").hide();
		}

		if (pageNum > 3) {
			parents.find(".ultp-first-dot").show();
		} else {
			parents.find(".ultp-first-dot").hide();
		}

		if (pageNum > 2) {
			parents.find(".ultp-first-pages").show();
		} else {
			parents.find(".ultp-first-pages").hide();
		}

		if (pages > pageNum + 2) {
			parents.find(".ultp-last-dot").show();
		} else {
			parents.find(".ultp-last-dot").hide();
		}

		if (pages > pageNum + 1) {
			parents.find(".ultp-last-pages").show();
		} else {
			parents.find(".ultp-last-pages").hide();
		}
	}

	function serial(parents, pageNum, pages) {
		let datas =
			pageNum <= 2
				? [1, 2, 3]
				: pages == pageNum
					? [pages - 2, pages - 1, pages]
					: [pageNum - 1, pageNum, pageNum + 1];
		let i = 0;
		parents.find(".ultp-center-item").each(function () {
			if (pageNum == datas[i]) {
				$(this).addClass("pagination-active");
			}
			$(this).find("a").blur();
			$(this).attr("data-current", datas[i]).find("a").text(datas[i]);
			i++;
		});
		parents.find(".ultp-prev-page-numbers a").blur();
		parents.find(".ultp-next-page-numbers a").blur();
	}

	// session value set function
	function setSession(key, value) {
		if (value != undefined) {
			sessionStorage.setItem(key, value);
		}
	}

	function getCurrPage(blockId) {
		const params = new URLSearchParams(window.location.search);
		const page = params.get(blockId + "_page");
		return page ? +page : 1;
	}

	function setCurrPage(blockId, currPage, prevPage) {
		const params = new URLSearchParams(window.location.search);
		params.set(`${blockId}_page`, currPage);
		const url = window.location.pathname + "?" + params.toString();

		window.history.replaceState(
			{
				page: {
					[blockId]: prevPage,
				},
			},
			document.title,
			url,
		);
	}

	// *************************************
	// Common Pagination AJAX Dispatcher
	// *************************************
	function paginationAjaxCall({
		wrap, // .ultp-block-wrapper Selector
		builder, // Get Builder Data
		post_ID,
		exclude,
		pageNum,
		blockid,
		ultpNonce, // Get Nonce
		blockName,
		filterType,
		widgetBlockId,
		filterValue,
		advFilterData = {},
		onSuccess,
		onBeforeSend,
	}) {
		if (!pageNum) return;

		// console.log({
		// 	exclude,
		// 	action: "ultp_pagination",
		// 	paged: pageNum,
		// 	blockId: blockid,
		// 	postId: post_ID,
		// 	blockName,
		// 	builder,
		// 	widgetBlockId,
		// 	filterType: filterType || "",
		// 	wpnonce: ultpNonce,
		// 	filterValue,
		// 	...advFilterData,
		// });

		ultpAjax({
			url: ultp_data_frontend.ajax,
			type: "POST",
			nonce: ultpNonce,
			data: postsRequestData(
				{
					exclude,
					action: "ultp_pagination",
					paged: pageNum,
					blockId: blockid,
					postId: post_ID,
					blockName,
					builder,
					widgetBlockId,
					filterType: filterType || "",
					filterValue,
					...advFilterData,
				},
				wrap,
			),
			beforeSend() {
				if (typeof onBeforeSend === "function") {
					onBeforeSend();
				} else {
					wrap.addClass("ultp-loading-active");
				}
			},
			success(data) {
				if (typeof onSuccess === "function") onSuccess(data);
			},
			complete() {
				wrap.removeClass("ultp-loading-active");
				contentUpdated();
			},
			error(xhr) {
				console.log(
					"Error occured. please try again " +
						xhr.statusText +
						xhr.responseText,
				);
				wrap.removeClass("ultp-loading-active");
			},
		});
	}

	$(document).on(
		"click",
		".ultp-pagination-ajax-action li",
		async function (e) {
			e.preventDefault();
			const ultpNonce = await getUltpNonce();

			let that = $(this),
				parents = that.closest(".ultp-pagination-ajax-action"),
				wrap = that.closest(".ultp-block-wrapper");

			if (parents.is(".ultp-disable-editor-click")) {
				return;
			}

			let pageNum = 1;
			let pages = parents.attr("data-pages");

			const exclude = parents.data("expost");
			const builder = parents.data("builder");
			const blockName = parents.data("blockname");
			const blockid = parents.attr("data-blockid");
			const filterType = parents.data("filter-type") || "";
			const filterValue = parents.data("filter-value") || "";

			// Pagination block integration
			if (wrap.length < 1) {
				const pagiFor = parents.data("for");
				if (pagiFor) {
					wrap = $("." + pagiFor + " .ultp-block-wrapper");
				}
			}

			if (that.attr("data-current")) {
				pageNum = Number(that.attr("data-current"));
				parents
					.attr("data-paged", pageNum)
					.find("li")
					.removeClass("pagination-active");
				serial(parents, pageNum, pages);
				showHide(parents, pageNum, pages);
			} else {
				if (that.hasClass("ultp-prev-page-numbers")) {
					pageNum = Number(parents.attr("data-paged")) - 1;
					parents
						.attr("data-paged", pageNum)
						.find("li")
						.removeClass("pagination-active");
					//parents.find('li[data-current="'+pageNum+'"]').addClass('pagination-active')
					serial(parents, pageNum, pages);
					showHide(parents, pageNum, pages);
				} else if (that.hasClass("ultp-next-page-numbers")) {
					pageNum = Number(parents.attr("data-paged")) + 1;
					parents
						.attr("data-paged", pageNum)
						.find("li")
						.removeClass("pagination-active");
					//parents.find('li[data-current="'+pageNum+'"]').addClass('pagination-active')
					serial(parents, pageNum, pages);
					showHide(parents, pageNum, pages);
				}
			}

			let widgetBlockId = "";
			let widgetBlock = that.parents(".widget_block:first");
			if (widgetBlock.length > 0) {
				let widget_items = widgetBlock.attr("id").split("-");
				widgetBlockId = widget_items[widget_items.length - 1];
			}

			let post_ID =
				parents.parents(".ultp-shortcode").length != 0 &&
				parents.data("selfpostid") == "no"
					? parents.parents(".ultp-shortcode").data("postid")
					: parents.data("postid");

			if (that.closest(".ultp-builder-content").length > 0) {
				post_ID = that.closest(".ultp-builder-content").data("postid");
			}

			// Build adv filter data from pagination element
			const advFilterData = {};
			if (Array.isArray(filterValue) && filterValue.length > 0) {
				advFilterData.filterShow = true;
				advFilterData.checkFilter = true;
				advFilterData.isAdv = true;
				advFilterData.author = parents.data("filter-author") || "";
				advFilterData.order = parents.data("filter-order") || "";
				advFilterData.orderby = parents.data("filter-orderby") || "";
				advFilterData.adv_sort = parents.data("filter-adv-sort") || "";
			}

			if (blockid) {
				setCurrPage(blockid, pageNum, getCurrPage(blockid));
			}

			// console.log(advFilterData, "advFilterData");
			paginationAjaxCall({
				wrap,
				pageNum,
				blockid,
				ultpNonce,
				blockName,
				post_ID,
				filterType,
				widgetBlockId,
				exclude,
				builder,
				filterValue,
				advFilterData,
				onSuccess(data) {
					wrap.find(".ultp-block-items-wrap").html(data);
					postsLoaded(wrap, "replace");
					if ($(window).scrollTop() > wrap.offset().top) {
						$([document.documentElement, document.body]).animate(
							{ scrollTop: wrap.offset().top - 80 },
							100,
						);
					}
				},
			});
		},
	);

	// *************************************
	// SlideShow
	// *************************************

	// Slideshow Display For Elementor via Shortcode
	$(window).on("elementor/frontend/init", () => {
		setTimeout(() => {
			if ($(".elementor-editor-active").length > 0) {
				slideshowDisplay();
			}
		}, 2000);
	});

	// Bricks Builder Backend Slider Support
	// Bricks Builder Backend Slider Support
	if (
		$(".bricks-builder-iframe").length > 0 &&
		$(window.parent.document).find(".bricks-panel-controls").length > 0
	) {
		setTimeout(() => {
			slideshowDisplay();
		}, 2500);
	}

	function slideshowDisplay() {
		$(
			".wp-block-ultimate-post-post-slider-1, .wp-block-ultimate-post-post-slider-2",
		).each(function () {
			const sectionId = "#" + $(this).attr("id");
			let selector = $(sectionId).find(".ultp-block-items-wrap");
			if ($(this).parent(".ultp-shortcode")) {
				selector = $(this).find(".ultp-block-items-wrap");
			}
			let settings = {
				arrows: true,
				dots: selector.data("dots") ? true : false,
				infinite: true,
				speed: 500,
				slidesToShow: selector.data("slidelg") || 1,
				slidesToScroll: 1,
				autoplay: selector.data("autoplay") ? true : false,
				autoplaySpeed: selector.data("slidespeed") || 3000,
				cssEase: "linear",
				prevArrow: selector.parent().find(".ultp-slick-prev").html(),
				nextArrow: selector.parent().find(".ultp-slick-next").html(),
			};

			if (!selector.data("layout")) {
				// Slider 1
				if (selector.data("slidelg") < 2) {
					settings.fade = selector.data("fade") ? true : false;
				} else {
					settings.responsive = [
						{
							breakpoint: 1024,
							settings: {
								slidesToShow: selector.data("slidesm") || 1,
								slidesToScroll: 1,
							},
						},
						{
							breakpoint: 600,
							settings: {
								slidesToShow: selector.data("slidexs") || 1,
								slidesToScroll: 1,
							},
						},
					];
				}
			} else {
				// Slider 2: the centred carousel. A registered Slider 2 hook may answer with the settings
				// of another carousel (see slider2Settings()); null keeps this one.
				const otherSettings = slider2Settings(settings, selector);
				if (otherSettings) {
					settings = otherSettings;
				} else {
					((settings.slidesToShow = selector.data("slidelg") || 1),
						(settings.centerMode = true));
					settings.centerPadding = `${selector.data("paddlg")}px` || 100;
					settings.responsive = [
						{
							breakpoint: 991,
							settings: {
								slidesToShow: selector.data("slidesm") || 1,
								slidesToScroll: 1,
								centerPadding: `${selector.data("paddsm")}px` || 50,
							},
						},
						{
							breakpoint: 767,
							settings: {
								slidesToShow: selector.data("slidexs") || 1,
								slidesToScroll: 1,
								centerPadding: `${selector.data("paddxs")}px` || 50,
							},
						},
					];
				}
			}
			selector.not(".slick-initialized").slick(settings);
		});
	}

	// Slider 2 hook. `window.ultpFrontendApi.slider2Settings( settings, $wrap )` is called for each
	// Slider 2 before it starts. It returns the slick settings of another carousel (built from
	// `settings`), or null to keep this carousel. Nothing is set by default.
	function slider2Settings(settings, selector) {
		const api = window.ultpFrontendApi;
		if (!api || typeof api.slider2Settings !== "function") {
			return null;
		}
		return api.slider2Settings(settings, selector) || null;
	}

	// Started once the page is ready, so scripts loaded after this one (footer scripts) can register
	// their Slider 2 hook before the sliders start.
	$(document).ready(slideshowDisplay);

	// *************************************
	// Accessibility for Loadmore Added
	// *************************************
	$('span[role="button"].ultp-loadmore-action').on("keydown", function (e) {
		const keyD = e.key !== undefined ? e.key : e.keyCode;
		if (
			keyD === "Enter" ||
			keyD === 13 ||
			["Spacebar", " "].indexOf(keyD) >= 0 ||
			keyD === 32
		) {
			e.preventDefault();
			this.click();
		}
	});
	// *************************************
	// Advanced Filter
	// *************************************
	function getTax(name) {
		switch (name) {
			case "categories":
				return "category";
			case "tag":
			case "tags":
				return "post_tag";
			case "authors":
				return "author";
			case "order_by":
				return "orderby";
			default:
				return name;
		}
	}

	function getFormattedSelectedFilter(type, name) {
		const fType = type
			.replace("_", " ")
			.replace(/\b\w/g, (l) => l.toUpperCase());
		return `${fType}: ${name}`;
	}

	function applyFilter(parent, blockId, blockName, postId, grids, builder) {
		const selectedFilters = [];
		const filterData = {};
		const pagis = parent.data("pagi");

		parent.find(".ultp-filter-select").each(function () {
			const type = $(this).attr("data-type");
			const selected = $(this).attr("data-selected");
			const cTax = $(this)
				.find('.ultp-filter-select__dropdown-inner[data-id="' + selected + '"]')
				.data("tax");
			if (getTax(type) === "author") {
				if (selected !== "_all") {
					filterData.author = [{ value: selected }];
				}
				return;
			}
			if (getTax(type) === "order") {
				filterData.order = selected;
				return;
			}
			if (getTax(type) === "orderby") {
				filterData.orderby = selected;
				return;
			}
			if (getTax(type) === "adv_sort") {
				filterData.adv_sort = selected;
				return;
			}
			// A choice of any type other than category or tag is sent with the taxonomy its option names (`data-tax`), if any.
			if (!["category", "post_tag"].includes(getTax(type))) {
				if (cTax) {
					selectedFilters.push({
						value: cTax + "###" + selected,
					});
				}
				return;
			}
			selectedFilters.push({
				value: getTax(type) + "###" + selected,
			});
		});

		parent.find('.ultp-filter-button[data-is-active="true"]').each(function () {
			const type = $(this).attr("data-type");
			const selected = $(this).attr("data-selected");
			const cTax = $(this).attr("data-tax");

			if (getTax(type) === "author") {
				if (selected !== "_all") {
					filterData.author = [{ value: selected }];
				}
				return;
			}

			if (getTax(type) === "order") {
				filterData.order = selected;
				return;
			}

			if (getTax(type) === "orderby") {
				filterData.orderby = selected;
				return;
			}

			if (getTax(type) === "adv_sort") {
				filterData.adv_sort = selected;
				return;
			}

			// A choice of any type other than category or tag is sent with the taxonomy its option names (`data-tax`), if any.
			if (!["category", "post_tag"].includes(getTax(type))) {
				if (cTax) {
					selectedFilters.push({
						value: cTax + "###" + selected,
					});
				}
				return;
			}
			selectedFilters.push({
				value: getTax(type) + "###" + selected,
			});
		});

		filterData.taxonomy = selectedFilters;

		// The search term is added by an add-on from the `ultp_ajax_request` event (action `ultp_adv_filter`).
		let widgetBlockId = "";
		let widgetBlock = parent.parents(".widget_block:first");
		if (widgetBlock.length > 0) {
			let widget_items = widgetBlock.attr("id").split("-");
			widgetBlockId = widget_items[widget_items.length - 1];
		}

		$(grids).each(function () {
			const grid = $(this);

			ultpAjax({
				url: ultp_data_frontend.ajax,
				type: "POST",
				nonce: ultp_data_frontend.security,
				data: postsRequestData(
					{
						action: "ultp_adv_filter",

						...filterData,
						builder_id: builder,
						blockId: blockId,
						blockName: blockName,
						postId: postId,
						widgetBlockId: widgetBlockId,
					},
					parent,
				),
				beforeSend: function () {
					grid.addClass("ultp-loading-active");
				},
				success: function (response) {
					// console.log(
					// 	JSON.parse(response.data.filteredData.data_attrs),
					// 	"response",
					// );
					grid
						.closest(".wp-block-ultimate-post-post-grid-parent")
						?.find(".ultp-not-found-message")
						?.remove();
					if (
						response?.data?.filteredData?.blocks === "" &&
						response?.data?.filteredData?.notFound
					) {
						grid
							.closest(".wp-block-ultimate-post-post-grid-parent")
							.append(
								'<div class="ultp-not-found-message" role="alert">' +
									response?.data?.filteredData?.notFound +
									"</div>",
							);
					}
					grid
						.find(".ultp-block-items-wrap")
						.html(response?.data?.filteredData?.blocks);
					if (
						response?.data?.filteredData?.paginationType == "loadMore" &&
						response?.data?.filteredData?.paginationShow
					) {
						// grid.find(".ultp-block-items-wrap")
						// .append('<span class="ultp-loadmore-insert-before"></span>');
						grid
							.find(".ultp-loadmore")
							.replaceWith(response?.data?.filteredData?.pagination);
					} else if (
						response?.data?.filteredData?.paginationType == "navigation"
					) {
						grid
							.find(".ultp-next-prev-wrap")
							.replaceWith(response?.data?.filteredData?.pagination);
					} else if (
						response?.data?.filteredData?.paginationType == "pagination"
					) {
						grid
							.find(".ultp-pagination-wrap")
							.replaceWith(response?.data?.filteredData?.pagination);
					}

					// Lets other scripts react to the block being filtered. Nobody listens by default.
					$(document).trigger("ultp_block_filtered", [
						grid,
						response.data.filteredData,
					]);

					if (response?.data?.filteredData?.pagination) {
						pagis?.map((pagiClass) => {
							let pagi = [];

							if (response?.data?.filteredData?.paginationType === "loadMore") {
								pagi = $(".ultp-loadmore." + pagiClass);
								if (pagi.length > 0) {
									// grid.find(".ultp-block-items-wrap")
									// .append('<span class="ultp-loadmore-insert-before"></span>');
								}
							} else {
								pagi = $("." + pagiClass + "[data-for]");
							}

							if (pagi.length > 0) {
								const newPagi = $(response.data.filteredData.pagination);
								pagi
									.attr("class")
									.split(" ")
									.forEach((cl) => newPagi.addClass(cl));
								pagi.replaceWith(newPagi);
							}
						});
					}
				},
				complete: function () {
					grid.removeClass("ultp-loading-active");
					postsLoaded(parent, "replace");
				},
				error: function (xhr) {
					console.log(
						"Error occured.please try again" +
							xhr.statusText +
							xhr.responseText,
					);
					grid.removeClass("ultp-loading-active");
				},
			});
		});
	}

	$(".ultp-filter-block").each(function () {
		const that = $(this);
		const parent = $(this).parents(".ultp-post-grid-parent");
		const grids = parent.find(".ultp-block-wrapper");
		const gridData = JSON.parse(parent.attr("data-grids"));
		const selectedFilterTemp = $(this).find(".ultp-filter-clear-template");
		const clearBtn = $(this).find(".ultp-filter-clear-button");
		const firstElClass = "ultp-block-" + clearBtn.data("blockid") + "-first";
		let postId = "";
		let builder = "";

		function resetFilters() {
			// Resetting select fields
			that.find(".ultp-filter-select").each(function () {
				const defVal = $(this).find(".ultp-filter-select-options li").first();
				$(this).attr("data-selected", defVal.attr("data-id"));
				$(this).find(".ultp-filter-select-field-selected").text(defVal.text());
			});

			// Resetting clear button
			const selectedFilter = that.find(".ultp-filter-clear-selected-filter");
			if (selectedFilter.hasClass(firstElClass)) {
				clearBtn.addClass(firstElClass);
			}
			selectedFilter.remove();

			// Add-on search boxes empty themselves on this event, before the fetch below.
			that.trigger("ultp_filter_reset");

			// Resetting filter buttons
			that.find('.ultp-filter-button[data-is-active="true"]').each(function () {
				$(this).removeClass("ultp-filter-button-active");
				$(this).attr("data-is-active", "false");
			});
		}

		function fetchPostsWithFilter() {
			gridData.forEach((data) => {
				applyFilter(
					parent,
					data["blockId"],
					data["name"],
					postId,
					grids,
					builder,
				);
			});
		}

		that.find(".ultp-filter-select").each(function () {
			const dropdown = $(this).find(".ultp-filter-select-options");
			const selected = $(this).find(".ultp-filter-select-field-selected");
			const icon = $(this).find(".ultp-filter-select-field-icon");
			const filter = $(this);
			const type = $(this).attr("data-type");
			const input = $(this).find(".ultp-filter-select-search");
			postId = $(this).attr("data-current-postid");
			builder = $(this).attr("data-builder");
			function showDropdown(show) {
				if (show) {
					$(".ultp-filter-select .ultp-filter-select-options").css(
						"display",
						"none",
					);
					$(".ultp-filter-select .ultp-filter-select-field-icon").removeClass(
						"ultp-dropdown-icon-rotate",
					);
					$(".ultp-filter-select").attr("aria-expanded", false);

					dropdown.css("display", "block");
					icon.addClass("ultp-dropdown-icon-rotate");
				} else {
					dropdown.css("display", "none");
					icon.removeClass("ultp-dropdown-icon-rotate");
				}

				filter.attr("aria-expanded", show);
			}

			const defValue = dropdown.find("li").first();

			function resetFilter() {
				selected.text(defValue.text());
				filter.attr("data-selected", defValue.attr("data-id"));
			}

			// Toggle on click
			$(this).on("click", function (e) {
				e.stopPropagation();
				showDropdown(dropdown.css("display") === "none");
			});

			$(dropdown)
				.find("li")
				.each(function () {
					const value = $(this).attr("data-id");
					const filterId = $(this).attr("data-blockId");
					const text = $(this).text();
					$(this).on("click", function () {
						selected.text(text);
						filter.attr("data-selected", value);

						// Selected filters
						if (value === "_all") {
							that.find(`.ultp-filter-clear[data-type="${type}"]`).remove();
						} else if (clearBtn.length > 0) {
							let isNew = false;

							let copy = that.find(`.ultp-filter-clear[data-type="${type}"]`);

							if (copy.length < 1) {
								isNew = true;
								copy = selectedFilterTemp.clone();
							}

							copy.removeClass("ultp-filter-clear-template");
							copy.addClass("ultp-filter-clear-selected-filter");
							copy
								.find(".ultp-selected-filter-text")
								.text(
									getFormattedSelectedFilter(
										filter.attr("data-filter-label"),
										text,
									),
								);
							// Alignment
							if (clearBtn.hasClass(firstElClass)) {
								clearBtn.removeClass(firstElClass);
								copy.addClass(firstElClass);
							}
							copy.find(".ultp-selected-filter-icon").on("click", function () {
								resetFilter();
								if (copy.hasClass(firstElClass)) {
									if (
										copy.next().hasClass("ultp-filter-clear-selected-filter")
									) {
										copy.next().addClass(firstElClass);
									} else {
										clearBtn.addClass(firstElClass);
									}
								}
								copy.remove();
								fetchPostsWithFilter();
							});
							copy.attr("data-id", value);
							copy.attr("data-type", type);
							copy.attr("data-for", filterId);
							copy.css({
								display: "block",
							});
							isNew && copy.insertBefore(clearBtn);
						}
						fetchPostsWithFilter();
						showDropdown(true);
					});
				});

			input.on("click", function (e) {
				e.preventDefault();
				e.stopPropagation();
			});

			input.on("input", function (e) {
				const value = String(e.target.value).toLowerCase();

				if (value.length > 0) {
					dropdown.find("li").each(function () {
						const currValue = $(this).text();
						$(this).css(
							"display",
							currValue.toLowerCase().includes(value) ? "list-item" : "none",
						);
					});
				} else {
					dropdown.find("li").each(function () {
						$(this).css("display", "list-item");
					});
				}
			});

			$(document).on("click", function (e) {
				if (!filter.is(e.target) && !filter.has(e.target).length) {
					showDropdown(false);
				}
			});
		});

		that.find(".ultp-filter-button").each(function () {
			const currFBtn = this;
			const fBtnType = $(this).data("type");
			postId = $(this).attr("data-current-postid");
			$(this).on("click", function () {
				const isActive = $(currFBtn).attr("data-is-active") === "true";
				const value = $(this).data("selected");

				// Clicking all button will disable other
				if (value === "_all") {
					const otherBtns = that.find(
						'.ultp-filter-button[data-selected]:not([data-selected="_all"])',
					);
					if (otherBtns.length > 0) {
						otherBtns.attr("data-is-active", "false");
						otherBtns.removeClass("ultp-filter-button-active");
					}
				}

				// This buttons are exclusive
				else if (
					["adv_sort", "order", "order_by"].includes(fBtnType) &&
					!isActive
				) {
					const otherBtns = that.find(
						`.ultp-filter-button[data-type="${fBtnType}"]`,
					);
					if (otherBtns.length > 0) {
						otherBtns.attr("data-is-active", "false");
						otherBtns.removeClass("ultp-filter-button-active");
					}
				}

				// Click a button should disable all button
				else {
					const allBtn = that.find('.ultp-filter-button[data-selected="_all"]');
					if (allBtn.length > 0) {
						allBtn.attr("data-is-active", "false");
						allBtn.removeClass("ultp-filter-button-active");
					}
				}

				if (isActive) {
					$(currFBtn).attr("data-is-active", "false");
					$(currFBtn).removeClass("ultp-filter-button-active");
				} else {
					$(currFBtn).attr("data-is-active", "true");
					$(currFBtn).addClass("ultp-filter-button-active");
					if (
						["author"].includes(fBtnType) ||
						!($(this).parent().attr("data-multiselect") == "true")
					) {
						$(currFBtn)
							.siblings()
							.attr("data-is-active", "false")
							.removeClass("ultp-filter-button-active");
					}
				}

				fetchPostsWithFilter();
			});
		});

		clearBtn.on("click", function () {
			resetFilters();
			fetchPostsWithFilter();
		});

		// An add-on's search box asks for a fetch by triggering `ultp_filter_request` on an element inside
		// this grid's parent. The add-on debounces its input; this only fetches. The last filter block of
		// the parent answers, as it did for the search input.
		parent.off("ultp_filter_request").on("ultp_filter_request", function () {
			fetchPostsWithFilter();
		});
	});

	/**************************************
              Dark Light Block
      **************************************/

	// dark light logo vars
	const customSrc = ultp_data_frontend?.dark_logo;
	const site_logo = $(".ultp-dark-logo.wp-block-site-logo")
		.find("img")
		.attr("src");
	const site_srcset =
		$(".ultp-dark-logo.wp-block-site-logo").find("img").attr("srcset") || "";

	/************** handle dark light color swap on click      **************/
	$(document).on(
		"click",
		".ultp-dark-light-block-wrapper-content.ultp-frontend .ultp-dl-con",
		function (e) {
			e.preventDefault();
			const parents = $(this).closest(".ultp-dark-light-block-wrapper-content");
			const lightMode = $(this).hasClass("ultp-light-con");

			const currentParents = $(this).closest(".ultp-dl-after-before-con");
			const opponent = parents.find(
				`.ultp-${lightMode ? "dark" : "light"}-con`,
			);
			const opponentParents = opponent.closest(".ultp-dl-after-before-con");

			const iconlay = currentParents.data("iconlay");
			const iconsize = currentParents.data("iconsize");
			const iconrev = currentParents.data("iconrev");

			let delay = 0;
			// A layout that animates the switch (PostX Pro) registers a function that does it and
			// returns the delay in milliseconds.
			const animate = window.ultpDarkLightAnimations
				? window.ultpDarkLightAnimations[iconlay]
				: null;
			if (typeof animate === "function") {
				delay = animate($(this), { lightMode, iconsize, iconrev }) || 0;
			}
			setCookie("ultplocalDLMode", lightMode ? "ultpdark" : "ultplight", 60);
			setTimeout(() => {
				currentParents.addClass("inactive");
				opponentParents.removeClass("inactive");

				// handle dark light image block
				if (lightMode) {
					$(".wp-block-ultimate-post-image .ultp-light-image-block").addClass(
						"inactive",
					);
					$(".wp-block-ultimate-post-image .ultp-dark-image-block").removeClass(
						"inactive",
					);
				} else if (!lightMode) {
					$(".wp-block-ultimate-post-image .ultp-dark-image-block").addClass(
						"inactive",
					);
					$(
						".wp-block-ultimate-post-image .ultp-light-image-block",
					).removeClass("inactive");
				}

				// handle dark light logo
				$(".ultp-dark-logo.wp-block-site-logo")
					.find("img")
					.attr("src", lightMode ? customSrc : site_logo)
					.attr("srcset", lightMode ? customSrc : site_srcset);
				$(".ultp-dark-logo.wp-block-site-logo img").css({ content: "initial" });

				// handle more than one block
				$(
					`.ultp-dark-light-block-wrapper-content .ultp-${
						lightMode ? "dark" : "light"
					}-con`,
				).each(function () {
					$(this).closest(".ultp-dl-after-before-con").removeClass("inactive");
				});
				$(
					`.ultp-dark-light-block-wrapper-content .ultp-${
						lightMode ? "light" : "dark"
					}-con`,
				).each(function () {
					$(this).closest(".ultp-dl-after-before-con").addClass("inactive");
				});

				// remove transition
				$(this).find(".ultp-dl-svg-con").removeAttr("style");
				$(this).find(".ultp-dl-text").removeAttr("style");

				handleDarkLight();
			}, delay);
		},
	);

	/**************     set cookie      **************/
	function setCookie(cookieName, cookieValue, expires) {
		const date = new Date();
		date.setTime(date.getTime() + expires * 24 * 60 * 60 * 1000);
		let expiresAt = "expires=" + date.toUTCString();
		document.cookie = cookieName + "=" + cookieValue + ";" + expiresAt + ";";
	}

	/**************    get registered cookie      **************/
	function getCookie(cookieName) {
		let c_name = cookieName + "=";
		let c_array = document.cookie.split(";");
		for (let i = 0; i < c_array.length; i++) {
			let item = c_array[i];
			while (item.charAt(0) == " ") {
				item = item.substring(1);
			}
			if (item.indexOf(c_name) == 0) {
				return item.substring(c_name.length, item.length);
			}
		}
		return "";
	}

	/************** handle dark light color swap      **************/
	function handleDarkLight() {
		if (
			$("#ultp-preset-colors-style-inline-css") &&
			$("#ultp-preset-colors-style-inline-css")[0]
		) {
			const root = $("#ultp-preset-colors-style-inline-css")[0].sheet;
			const base1 = root.cssRules[0].style.getPropertyValue(
				"--postx_preset_Base_1_color",
			);
			const base2 = root.cssRules[0].style.getPropertyValue(
				"--postx_preset_Base_2_color",
			);
			const base3 = root.cssRules[0].style.getPropertyValue(
				"--postx_preset_Base_3_color",
			);

			const contrast1 = root.cssRules[0].style.getPropertyValue(
				"--postx_preset_Contrast_1_color",
			);
			const contrast2 = root.cssRules[0].style.getPropertyValue(
				"--postx_preset_Contrast_2_color",
			);
			const contrast3 = root.cssRules[0].style.getPropertyValue(
				"--postx_preset_Contrast_3_color",
			);

			root.cssRules[0].style.setProperty(
				"--postx_preset_Base_1_color",
				contrast1,
			);
			root.cssRules[0].style.setProperty(
				"--postx_preset_Base_2_color",
				contrast2,
			);
			root.cssRules[0].style.setProperty(
				"--postx_preset_Base_3_color",
				contrast3,
			);

			root.cssRules[0].style.setProperty(
				"--postx_preset_Contrast_1_color",
				base1,
			);
			root.cssRules[0].style.setProperty(
				"--postx_preset_Contrast_2_color",
				base2,
			);
			root.cssRules[0].style.setProperty(
				"--postx_preset_Contrast_3_color",
				base3,
			);
		}
	}

	// Posts were added or replaced by an AJAX request. Other scripts listen to fix up the new markup.
	function contentUpdated() {
		$(document).trigger("ultp_content_updated");
	}

	// The data of a request for a block's posts. Other scripts may add fields to `data` from the
	// `ultp_ajax_request` event; `scope` is the element of the block the request is for.
	function postsRequestData(data, scope) {
		$(document).trigger("ultp_ajax_request", [data, scope]);
		return data;
	}

	// A request put posts into `scope`. `mode` is "replace" or "append" (posts added after the
	// ones already shown). Nobody listens by default.
	function postsLoaded(scope, mode) {
		$(document).trigger("ultp_posts_loaded", [scope, mode]);
	}
	// /*************************************
	//      Tab Block
	//   *************************************/
	// $('.ultp-tabs-content[data-show="click"] .ultp-tab-item-label').on(
	// 	"click",
	// 	function (e) {
	// 		handleTabItem($(this));
	// 	}
	// );
	// $('.ultp-tabs-content[data-show="hover"] .ultp-tab-item-label').hover(
	// 	function (e) {
	// 		handleTabItem($(this));
	// 	}
	// );
	// function handleTabItem(that) {
	// 	if (that.closest("body.block-editor-page").length) {
	// 		return;
	// 	}
	// 	const parent = that.closest(".ultp-tabs-content");
	// 	parent.find(".ultp-tab-item-label").removeClass("pt-active");
	// 	that.addClass("pt-active");

	// 	const tabId = that.data("tabid");
	// 	parent
	// 		.find("> .ultp-tabs-content-warp > .wp-block-ultimate-post-tab-item")
	// 		.removeClass("pt-active");
	// 	parent
	// 		.find(
	// 			`> .ultp-tabs-content-warp > .wp-block-ultimate-post-tab-item.${tabId}`
	// 		)
	// 		.addClass("pt-active");
	// }

	// Helpers for add-on front-end scripts. Keep the names and the arguments: other scripts use them.
	window.ultpFrontendApi = Object.assign(window.ultpFrontendApi || {}, {
		level: 1,
		setSession, // Store a value in sessionStorage (skipped when undefined)
		getUltpNonce, // Get Nonce
		paginationAjaxCall, // Pagination API Call Function
	});
})(jQuery);
