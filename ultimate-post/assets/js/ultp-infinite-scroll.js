(function ($) {
	("use strict");
	$.fn.UltpInfiniteScroll = async function (
		block, // Current Block
		setSession, // set Unique Post Id
		getUltpNonce, // Get Nonce
		paginationAjaxCall, // Pagination API Call Function
	) {
		const ultpNonce = await getUltpNonce();

		const wrap = block.find(".ultp-block-wrapper");
		const exclude = wrap.data("expost");
		const builder = wrap.data("builder");
		const blockid = wrap.attr("data-blockid");
		const blockName = wrap.data("blockname");
		const filterType = wrap.attr("data-filter_type") || "";

		let filterValue = [];

		// pageNum starts at 2 — page 1 is already rendered
		let pageNum = 2;
		let noMoreData = false;
		let isLoading = false; // <-- ADD THIS

		// Post Id
		let post_ID =
			block.parents(".ultp-shortcode").length !== 0 &&
			wrap.data("selfpostid") === "no"
				? block.parents(".ultp-shortcode").data("postid")
				: wrap.data("postid");

		if (block.find(".ultp-builder-content").length > 0) {
			post_ID = block.find(".ultp-builder-content").data("postid");
		}

		// Widget Block ID
		let widgetBlockId = "";
		const widgetBlock = block.parents(".widget_block:first");
		if (widgetBlock.length > 0) {
			const widget_items = widgetBlock.attr("id").split("-");
			widgetBlockId = widget_items[widget_items.length - 1];
		}

		// Unique Post
		const ultpCurrentUniquePosts = JSON.stringify(
			wrap.find(".ultp-current-unique-posts").data("current-unique-posts"),
		);

		// Window Scroll
		let scrollTimer = null;
		function onScroll() {
			if (scrollTimer) return;
			scrollTimer = setTimeout(function () {
				scrollTimer = null;
				if (isEndReached() && !noMoreData) {
					loadContent();
				}
			}, 150);
		}

		// Remove any previous handler for this block before adding a new one
		$(window).off("scroll.ultp-iscroll-" + blockid);
		$(window).on("scroll.ultp-iscroll-" + blockid, onScroll);

		// Initial check in case content is smaller than viewport height
		onScroll();

		// Load Scrolled Content
		function loadContent() {
			if (isLoading) return; // <-- ADD THIS
			isLoading = true; // <-- ADD THIS
			// Build adv filter data from block-wrapper element
			filterValue =
				typeof wrap.attr("data-filter_value") === "string"
					? JSON.parse(wrap.attr("data-filter_value"))
					: (wrap.attr("data-filter_value") ?? []);

			const advFilterData = {};

			if (Array.isArray(filterValue) && filterValue.length > 0) {
				advFilterData.isAdv = true;
				advFilterData.filterShow = true;
				advFilterData.checkFilter = true;
				advFilterData.order = wrap.attr("data-filter_order") || "";
				advFilterData.orderby = wrap.attr("data-filter_orderby") || "";
				advFilterData.adv_sort = wrap.attr("data-filter_queryQuick") || "";
				const authorData =
					typeof wrap.attr("data-filter_author") === "string"
						? (JSON.parse(wrap.attr("data-filter_author")) ?? [])
						: (wrap.attr("data-filter_author") ?? []);
				if (Array.isArray(authorData) && authorData.length > 0) {
					advFilterData.author = authorData;
				}
			}

			// Forward active search term to paginated scroll requests
			const searchVal = wrap.attr("data-filter_search") || "";
			if (searchVal) {
				advFilterData.search = searchVal;
				if (!advFilterData.isAdv) {
					advFilterData.isAdv = true;
					advFilterData.filterShow = true;
					advFilterData.checkFilter = true;
				}
			}

			// Get the LATEST unique posts for this specific block on each call
			const ultpCurrentUniquePosts = JSON.stringify(
				wrap.find(".ultp-current-unique-posts").data("current-unique-posts"),
			);

			console.log(pageNum, "pageNum");

			paginationAjaxCall({
				wrap, // .ultp-block-wrapper Selector
				pageNum,
				blockid,
				ultpNonce,
				blockName,
				post_ID,
				filterType,
				ultpCurrentUniquePosts,
				widgetBlockId,
				exclude, // exclude post ids
				builder, // builder type/data
				filterValue,
				advFilterData,
				onBeforeSend() {
					return;
				},
				onSuccess(data) {
					isLoading = false; // <-- ADD THIS
					if (!data || data.trim() === "") {
						noMoreData = true;
						$(window).off("scroll.ultp-iscroll-" + blockid);
						return;
					}
					if (!data || data.trim() === "") {
						noMoreData = true;
						$(window).off("scroll.ultp-iscroll-" + blockid);
						return;
					}
					let incoming = $(data);
					const uniquePostsDataEl = incoming.filter(
						".ultp-current-unique-posts",
					);

					if (uniquePostsDataEl.length > 0) {
						// Update global session unique IDs from the server response
						const newGlobalUniqueIds =
							uniquePostsDataEl.data("ultp-unique-ids");
						setSession("ultp_uniqueIds", JSON.stringify(newGlobalUniqueIds));

						// Update the block's own list of current posts
						const mainUniquePostsEl = wrap
							.find(".ultp-current-unique-posts")
							.first();
						const existingBlockIds =
							mainUniquePostsEl.data("current-unique-posts") || [];
						const newBlockIds =
							uniquePostsDataEl.data("current-unique-posts") || [];
						const combinedBlockIds = [...existingBlockIds, ...newBlockIds];
						mainUniquePostsEl.attr(
							"data-current-unique-posts",
							JSON.stringify(combinedBlockIds),
						);

						// Remove the data carrier div from the incoming content
						incoming = incoming.not(uniquePostsDataEl);
					}

					const itemsWrap = wrap.find(".ultp-block-items-wrap");
					itemsWrap.append(incoming);

					// Animate only the newly added items
					const newItems = itemsWrap.children().slice(-incoming.length);
					animateBlockItem(newItems);

					pageNum++;

					// Check again if more content is needed to fill the viewport
					onScroll();
				},
			});
		}

		// Calculate if user has scrolled near the end of the block
		function isEndReached() {
			const gridBottom = block.offset().top + block.outerHeight();
			const scrollBottom = $(window).scrollTop() + $(window).height();
			return scrollBottom >= gridBottom - 100;
		}
	};

	// Card Item Animation Config.
	function animateBlockItem(blockItems) {
		if (blockItems.length > 0) {
			blockItems.addClass("ultp-infscroll-animate-in");
			if (!window.IntersectionObserver) {
				blockItems.each(function (i) {
					const block = $(this);
					setTimeout(function () {
						block.addClass("ultp-infscroll-visible");
					}, i * 80);
				});
				return;
			}

			// Fire the animation only when each card actually enters the viewport
			const observer = new IntersectionObserver(
				function (entries) {
					const visible = entries.filter(function (e) {
						return e.isIntersecting;
					});
					visible.forEach(function (entry, i) {
						setTimeout(function () {
							$(entry.target).addClass("ultp-infscroll-visible");
						}, i * 70); // stagger within each visible batch
						observer.unobserve(entry.target);
					});
				},
				{ threshold: 0.08, rootMargin: "0px 0px -20px 0px" },
			);

			blockItems.each(function () {
				observer.observe(this);
			});
		}
	}
})(jQuery);
