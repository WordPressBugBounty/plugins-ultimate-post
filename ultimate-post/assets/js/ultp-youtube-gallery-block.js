(function ($) {
	("use strict");

	$(document).ready(function () {
		initYouTubeGallery();
	});

	function initYouTubeGallery() {
		// Helper for parsing JSON data
		function parseMaybeJSON(val, fallback) {
			if (typeof val === "string") {
				try {
					return JSON.parse(val);
				} catch (e) {
					return fallback;
				}
			}
			return typeof val === "object" && val !== null ? val : fallback;
		}

		// Escape a value from the YouTube API for use in HTML text or a quoted attribute.
		// Call it once, at the point of interpolation, and only on the final string.
		function escapeHtml(value) {
			if (value === null || value === undefined) return "";
			return String(value).replace(/[&<>"']/g, function (ch) {
				return {
					"&": "&amp;",
					"<": "&lt;",
					">": "&gt;",
					'"': "&quot;",
					"'": "&#39;",
				}[ch];
			});
		}

		// Helper to truncate text responsively
		function truncateText(text, length) {
			if (!text) return "";
			const width = $(window).width();
			let maxLength;
			if (width < 600) {
				maxLength = length.sm || length.lg;
			} else if (width < 900) {
				maxLength = length.md || length.lg;
			} else {
				maxLength = length.lg;
			}
			return text.length > maxLength
				? text.substring(0, maxLength) + "..."
				: text;
		}

		// Helper to sort videos, oldest first
		function sortVideos(videos) {
			const sorted = [...videos];
			return sorted.sort(
				(a, b) =>
					new Date(a.publishedAt).getTime() -
					new Date(b.publishedAt).getTime()
			);
		}

		// Extract playlist ID from URL or direct ID, and handle channel URLs/handles
		function getPlaylistId(input) {
			if (!input) return "";
			try {
				const url = new URL(input);
				if (url.hostname === "www.youtube.com") {
					if (url.pathname.startsWith("/channel/")) {
						return url.pathname.split("/channel/")[1];
					} else if (url.pathname.startsWith("/@")) {
						return url.pathname.substring(2); // Return handle for later resolution
					} else if (url.searchParams.get("list")) {
						return url.searchParams.get("list");
					}
				}
				return input;
			} catch (e) {
				return input;
			}
		}

		// Resolve handle to channel ID using YouTube API
		function resolveHandleToChannelId(handle, apiKey, callback) {
			$.get(
				`https://www.googleapis.com/youtube/v3/search?part=snippet&q=${encodeURIComponent(
					handle
				)}&type=channel&key=${apiKey}`
			)
				.done(function (data) {
					if (data.items && data.items.length > 0) {
						callback(data.items[0].snippet.channelId);
					} else {
						callback(null);
					}
				})
				.fail(function () {
					callback(null);
				});
		}

		// Helper function to generate video player iframe
		function generatePlayerIframe(videoId, config) {
			const safeVideoId = escapeHtml(videoId);
			const params = [
				`autoplay=${config.autoplay ? "1" : "0"}`,
				`loop=${config.loop ? "1" : "0"}`,
				`mute=${config.mute ? "1" : "0"}`,
				`controls=${config.showPlayerControl ? "1" : "0"}`,
				`modestbranding=${config.hideYoutubeLogo ? "1" : "0"}`,
				config.loop ? `playlist=${safeVideoId}` : null,
			]
				.filter(Boolean)
				.join("&");

			return `
				<div class="ultp-ytg-video-wrapper">
					<iframe
						src="https://www.youtube.com/embed/${safeVideoId}?${params}"
						title="YouTube Video"
						frameborder="0"
						allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
						allowfullscreen
					></iframe>
				</div>
			`;
		}

		// Generate text content (title and description)
		function getYoutubeTextContent(
			enableTitle,
			title,
			titleLength,
			enableDesc,
			description,
			descriptionLength,
			videoId
		) {
			let html = '<div class="ultp-ytg-content">';
			if (enableTitle) {
				html += `<div class="ultp-ytg-title"><a href="https://www.youtube.com/watch?v=${escapeHtml(videoId)}" target="_blank" rel="noopener noreferrer">${escapeHtml(truncateText(
					title,
					titleLength
				))}</a></div>`;
			}
			if (enableDesc) {
				html += `<div class="ultp-ytg-description">${escapeHtml(truncateText(
					description,
					descriptionLength
				))}</div>`;
			}
			html += "</div>";
			return html;
		}

		// Initialize YouTube Gallery
		$(".wp-block-ultimate-post-youtube-gallery").each(function () {
			const $block = $(this);
			// An add-on may run this block itself; it then calls preventDefault().
			const initEvent = $.Event("ultp_youtube_gallery_init");
			$block.trigger(initEvent);
			if (initEvent.isDefaultPrevented()) {
				return;
			}
			const $wrapper = $block.find(".ultp-block-wrapper");
			let $container = $block.find(".ultp-ytg-view-grid, .ultp-ytg-container");
			const $loadMoreBtn = $block.find(".ultp-ytg-loadmore-btn");

			// Get configuration from data attributes
			const config = {
				playlistIdOrUrl: $block.data("playlist") || "",
				apiKey: $block.data("api-key") || "",
				videosPerPage: parseMaybeJSON($block.data("videos-per-page"), {
					lg: 9,
					md: 6,
					sm: 3,
				}),
				showVideoTitle: $block.data("show-video-title") == "1",
				videoTitleLength: parseMaybeJSON($block.data("video-title-length"), {
					lg: 50,
					md: 50,
					sm: 50,
				}),
				loadMoreEnable: $block.data("load-more-enable") == "1",
				moreButtonLabel: $block.data("more-button-label") || "More Videos",
				autoplay: $block.data("autoplay") == "1",
				loop: $block.data("loop") == "1",
				mute: $block.data("mute") == "1",
				showPlayerControl: $block.data("show-player-control") == "1",
				hideYoutubeLogo: $block.data("hide-youtube-logo") == "1",
				showDescription: $block.data("show-description") == "1",
				videoDescriptionLength: parseMaybeJSON(
					$block.data("video-description-length"),
					{
						lg: 100,
						md: 100,
						sm: 100,
					}
				),
				imageHeightRatio: $block.data("image-height-ratio") || "16-9",
				galleryColumn: parseMaybeJSON($block.data("gallery-column"), {
					lg: 3,
					md: 2,
					sm: 1,
				}),
				displayType: $block.data("display-type") || "grid",
				enableListView: $block.data("enable-list-view") == "1",
				imgHeight: $block.data("img-height"),
			};

			let playlistId = getPlaylistId(config.playlistIdOrUrl);

			// If it's a handle (starts with @), resolve to channel ID
			if (playlistId.startsWith("@")) {
				const handle = playlistId.substring(1);
				resolveHandleToChannelId(handle, config.apiKey, function (channelId) {
					if (channelId) {
						playlistId = channelId;
						proceedWithPlaylist(playlistId);
					} else {
						$wrapper.html(
							'<p style="color:#888">Invalid handle or API key.</p>'
						);
					}
				});
			} else {
				proceedWithPlaylist(playlistId);
			}

			function proceedWithPlaylist(playlistId) {
				// Convert channel ID to uploads playlist ID
				if (playlistId.startsWith("UC")) {
					playlistId = "UU" + playlistId.substring(2);
				}

				if (!playlistId || !config.apiKey) {
					$wrapper.html(
						'<p style="color:#888">Please provide both YouTube playlist ID/URL and API key.</p>'
					);
					return;
				}

				// State management
				let allVideos = [];
				let shownCount = config.videosPerPage.lg || 9;
				let playingId = null;

				// Update responsive counts
				function updateResponsiveCounts() {
					const width = $(window).width();
					if (width < 600) {
						shownCount = Math.max(shownCount, config.videosPerPage.sm || 3);
					} else if (width < 900) {
						shownCount = Math.max(shownCount, config.videosPerPage.md || 6);
					} else {
						shownCount = Math.max(shownCount, config.videosPerPage.lg || 9);
					}
				}

				// Render videos for grid/list layout
				function renderGridLayout(videos, count) {
					if (!videos.length) {
						$container.html("<p>No videos found in this playlist.</p>");
						return;
					}

					const displayedVideos = videos.slice(0, count);
					let html = "";
					displayedVideos.forEach(function (video) {
						const isPlaying = playingId === video.videoId;
						html += `<div class="ultp-ytg-item${isPlaying ? " active" : ""}">`;
						html += `<div class="ultp-ytg-video">`;

						if (isPlaying) {
							html += generatePlayerIframe(video.videoId, config);
						} else {
							const getSvgIcon = $(".ultp-ytg-play__icon").html();
							html += `
								<img src="${escapeHtml(video.thumbnail)}" alt="${escapeHtml(
								video.title
							)}" loading="lazy" data-video-id="${escapeHtml(
								video.videoId
							)}" style="cursor:pointer;" />
								<div class="ultp-ytg-play__icon">
									${getSvgIcon}
								</div>
							`;
						}

						html += `</div>`;
						html += `<div class="ultp-ytg-inside">`;
						html += getYoutubeTextContent(
							config.showVideoTitle,
							video.title,
							config.videoTitleLength,
							config.showDescription,
							video.description,
							config.videoDescriptionLength,
							video.videoId
						);
						html += `</div></div>`;
					});

					$container.html(html);

					// Update load more button visibility
					if (config.loadMoreEnable && count < videos.length) {
						$loadMoreBtn.show();
					} else {
						$loadMoreBtn.hide();
					}
				}

				function renderVideos(videos, count) {
					renderGridLayout(videos, count);
				}

				// Fetch videos from YouTube API
				$container.html(`
					<div class="ultp-ytg-loading gallery-postx gallery-active">
						<div class="skeleton-box"></div>
						<div class="skeleton-box"></div>
						<div class="skeleton-box"></div>
						<div class="skeleton-box"></div>
						<div class="skeleton-box"></div>
						<div class="skeleton-box"></div>
					</div>
				`);

				$.get("https://www.googleapis.com/youtube/v3/playlistItems", {
					part: "snippet",
					maxResults: 50,
					playlistId: playlistId,
					key: config.apiKey,
				})
					.done(function (data) {
						setTimeout(function () {
							$container.empty(); // Remove loading after 3s
							if (data.error) {
								$container.html(
									`<div class="ultp-ytg-error">${escapeHtml(
										data.error.message || "Failed to fetch playlist."
									)}</div>`
								);
								return;
							}

							const videos = (data.items || [])
								.filter(function (item) {
									return (
										item.snippet.title !== "Private video" &&
										item.snippet.title !== "Deleted video"
									);
								})
								.map(function (item) {
									return {
										videoId: item.snippet.resourceId.videoId,
										title: item.snippet.title,
										thumbnail:
											item.snippet.thumbnails?.[config.imgHeight]?.url ||
											item.snippet.thumbnails?.medium?.url ||
											"",
										publishedAt: item.snippet.publishedAt || "",
										description: item.snippet.description || "",
									};
								});
							allVideos = sortVideos(videos);
							renderVideos(allVideos, shownCount);
						}, 2000);
					})
					.fail(function () {
						setTimeout(function () {
							$container.empty();
							$container.html(
								'<div class="ultp-ytg-error">Failed to fetch videos. Please try again.</div>'
							);
						}, 3000);
					});

				// Event handlers
				// Make clicking the play icon also start the video with loader fallback (per item)
				$block.on("click", ".ultp-ytg-play__icon", function () {
					// Find the previous img[data-video-id] sibling to get the videoId
					const $img = $(this).siblings("img[data-video-id]");
					const videoId = $img.data("video-id");
					if (!videoId) return;
					// Find the closest .ultp-ytg-item and its .ultp-ytg-video
					const $item = $(this).closest(".ultp-ytg-item");
					const $videoDiv = $item.find(".ultp-ytg-video");
					// Show loader only in this video area
					$videoDiv.html(
						'<div class="ultp-ytg-loading"><div class="ytg-loader"></div></div>'
					);
					// Add active class to this item, remove from others
					$item
						.addClass("active")
						.siblings(".ultp-ytg-item")
						.removeClass("active");
					setTimeout(function () {
						playingId = videoId;
						renderGridLayout(allVideos, shownCount);
					}, 1000);
				});

				$block.on("click", ".ultp-ytg-video img[data-video-id]", function () {
					const videoId = $(this).data("video-id");
					if (!videoId) return;

					playingId = videoId;
					renderGridLayout(allVideos, shownCount);
				});

				$loadMoreBtn.on("click", function () {
					shownCount += config.videosPerPage.lg;
					renderGridLayout(allVideos, shownCount);
				});
				// Responsive handling
				$(window).on("resize", function () {
					if (allVideos.length) {
						updateResponsiveCounts();
						renderVideos(allVideos, shownCount);
					}
				});
			}
		});
	}
})(jQuery);
