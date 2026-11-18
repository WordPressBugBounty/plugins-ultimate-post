(function ($) {
	("use strict");

	function mediaUrl(value) {
		if (
			typeof value !== "string" ||
			!value.trim() ||
			/[\u0000-\u001f\u007f]/.test(value)
		) {
			return null;
		}
		try {
			const url = new URL(value, document.baseURI);
			return url.protocol === "https:" || url.protocol === "http:"
				? url
				: null;
		} catch {
			return null;
		}
	}

	function videoDimension(value, fallback) {
		if (typeof value !== "string" && typeof value !== "number") {
			return fallback;
		}
		const dimension = String(value).trim();
		return /^\d+(?:\.\d+)?%?$/.test(dimension) &&
			Number.isFinite(parseFloat(dimension)) &&
			parseFloat(dimension) > 0
			? dimension
			: fallback;
	}

	function enabled(value) {
		return value === true || value === 1 || value === "1" || value === "true";
	}

	function providerVideoId(videoData) {
		let videoId = videoData.id;
		// Extract video ID if not available
		if (videoId === undefined || videoId === null || videoId === "") {
			const url = mediaUrl(videoData.url);
			if (!url) return "";
			const hostname = url.hostname.toLowerCase();
			if (videoData.type === "youtube") {
				if (hostname === "youtu.be" || hostname === "www.youtu.be") {
					videoId = url.pathname.slice(1);
				} else if (
					["youtube.com", "www.youtube.com", "m.youtube.com"].includes(hostname)
				) {
					videoId =
						url.pathname === "/watch"
							? url.searchParams.get("v")
							: (url.pathname.match(/^\/(?:embed|v)\/([^/]+)\/?$/) || [])[1];
				}
			} else if (
				["vimeo.com", "www.vimeo.com", "player.vimeo.com"].includes(hostname)
			) {
				videoId = (url.pathname.match(/\/(\d+)\/?$/) || [])[1];
			}
		}
		if (typeof videoId !== "string" && typeof videoId !== "number") return "";
		const id = String(videoId);
		const pattern = videoData.type === "youtube" ? /^[A-Za-z0-9_-]{11}$/ : /^\d+$/;
		return pattern.test(id) ? id : "";
	}

	// Builds the embed markup of a post's video from the data attributes of its wrapper.
	// Returns an empty string when the video cannot be embedded. Shared with the add-ons that show
	// the video elsewhere (window.ultpFrontendApi.buildVideoEmbed).
	function buildVideoEmbed(videoContent, isAutoPlay) {
		const videoData = {
			url: videoContent.data("video-url"),
			id: videoContent.data("video-id"),
			type: videoContent.data("video-type"),
			autoplay: videoContent.data("autoplay"),
			loop: videoContent.data("loop"),
			mute: videoContent.data("mute"),
			controls: videoContent.data("controls"),
			preload: videoContent.data("preload"),
			poster: videoContent.data("poster"),
			playsinline: videoContent.data("playsinline"),
			width: videoContent.data("width"),
			height: videoContent.data("height"),
		};

		if (!["youtube", "vimeo", "local"].includes(videoData.type)) return "";
		const videoId =
			videoData.type === "local" ? "local" : providerVideoId(videoData);

		// Return early if no video ID found and it's not a local video
		if (!videoId && videoData.type !== "local") {
			console.error(`${videoData.type} video ID not found`);
			return "";
		}

		// Generate iframe based on video type
		let embed;

		switch (videoData.type) {
			case "youtube":
				const youtubeParams = new URLSearchParams({
					autoplay: isAutoPlay ? "1" : "0",
					loop: enabled(videoData.loop) ? "1" : "0",
					mute: enabled(videoData.mute) || isAutoPlay ? "1" : "0",
					controls: enabled(videoData.controls) ? "1" : "0",
					playsinline: enabled(videoData.playsinline) ? "1" : "0",
					modestbranding: "1",
					rel: "0",
				});

				if (enabled(videoData.loop) && videoId) {
					youtubeParams.append("playlist", videoId);
				}

				embed = document.createElement("iframe");
				embed.setAttribute(
					"src",
					`https://www.youtube.com/embed/${videoId}?${youtubeParams.toString()}`
				);
				embed.setAttribute(
					"allow",
					"accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
				);
				break;

			case "vimeo":
				const vimeoParams = new URLSearchParams({
					autoplay: isAutoPlay ? "1" : "0",
					loop: enabled(videoData.loop) ? "1" : "0",
					muted: enabled(videoData.mute) || isAutoPlay ? "1" : "0",
					controls: enabled(videoData.controls) ? "1" : "0",
					playsinline: enabled(videoData.playsinline) ? "1" : "0",
					dnt: "1",
				});

				embed = document.createElement("iframe");
				embed.setAttribute(
					"src",
					`https://player.vimeo.com/video/${videoId}?${vimeoParams.toString()}`
				);
				embed.setAttribute("allow", "autoplay; fullscreen; picture-in-picture");
				break;

			case "local":
				const url = mediaUrl(videoData.url);
				if (!url) return "";
				const videoFormat = url.pathname
					.split(".")
					.pop()
					.toLowerCase()
					.split("?")[0];
				const formats = {
					mp4: "mp4",
					webm: "webm",
					ogg: "ogg",
					avi: "mp4",
					mov: "mp4",
				};
				const mimeType = formats[videoFormat] || "mp4";

				embed = document.createElement("video");
				embed.setAttribute("class", "ultp-video-html");
				if (isAutoPlay) {
					embed.setAttribute("autoplay", "");
					embed.setAttribute("muted", "");
				}
				for (const attribute of ["loop", "controls", "playsinline"]) {
					if (enabled(videoData[attribute])) embed.setAttribute(attribute, "");
				}
				const poster = mediaUrl(videoData.poster);
				if (poster) embed.setAttribute("poster", poster.href);
				if (["none", "metadata", "auto"].includes(videoData.preload)) {
					embed.setAttribute("preload", videoData.preload);
				}
				const source = document.createElement("source");
				source.setAttribute("src", url.href);
				source.setAttribute("type", `video/${mimeType}`);
				embed.appendChild(source);
				const fallback = document.createElement("p");
				fallback.textContent = "Your browser does not support the video tag. ";
				const link = document.createElement("a");
				link.setAttribute("href", url.href);
				link.setAttribute("target", "_blank");
				link.setAttribute("rel", "noopener noreferrer");
				link.textContent = "Download the video";
				fallback.appendChild(link);
				embed.appendChild(fallback);
				break;

			default:
				console.error(`Unsupported video type: ${videoData.type}`);
				return "";
		}

		embed.setAttribute("width", videoDimension(videoData.width, "100%"));
		embed.setAttribute(
			"height",
			videoDimension(videoData.height, videoData.type === "local" ? "auto" : "315")
		);
		if (videoData.type !== "local") {
			embed.setAttribute("frameborder", "0");
			embed.setAttribute("allowfullscreen", "");
			embed.setAttribute("loading", "lazy");
		}
		// Fix: Clear existing video content before adding new
		videoContent.empty();
		// Preserve the string API for add-ons. The DOM serializer escapes attribute values.
		return embed.outerHTML;
	}

	$(document).on("click", ".ultp-video-icon", function () {
		const vid = $(this);
		let isAutoPlay =
			vid.attr("enableautoplay") === "true" ||
			vid.attr("enableautoplay") === "1";

		const blockImage = vid.closest(".ultp-block-image");

		// Icons that ask for a popup are handled by the script that provides it.
		const enablePopup =
			vid.attr("enableVideoPopup") === "true" ||
			vid.attr("enableVideoPopup") === "1";
		if (enablePopup) {
			return;
		}

		// Inline video: use the video content of the block image
		const videoContent = blockImage.find(
			"div.ultp-block-video-content .ultp-video-wrapper"
		);
		blockImage.find("div.ultp-block-video-content").show();

		// Check if video content exists
		if (videoContent.length === 0) {
			console.error("Video content wrapper not found");
			return;
		}

		const embedHtml = buildVideoEmbed(videoContent, isAutoPlay);

		// Return early if no embed HTML generated
		if (!embedHtml) {
			console.error("Failed to generate video embed");
			return;
		}

		// Append the generated embed HTML
		videoContent.html(embedHtml);

		// Inline video display without popup
		blockImage.find("> a img").hide();
		videoContent.parent().css({ display: "block" });
		vid.hide();

		// Auto-play handling for local videos
		const videoElement = videoContent.find("video");
		if (isAutoPlay && videoElement.length > 0) {
			setTimeout(() => {
				videoElement[0]
					.play()
					.catch((e) => console.log("Autoplay prevented:", e));
			}, 500);
		}

		// Hide loader when video loads
		const allVideoElements = videoContent.find("iframe, video");
		if (allVideoElements.length) {
			allVideoElements.on("load loadeddata canplay", function () {
				$(".ultp-loader-container").hide();
			});
		}
	});

	// Helpers for add-on scripts (ultp.js adds the rest of the object).
	window.ultpFrontendApi = window.ultpFrontendApi || {};
	window.ultpFrontendApi.buildVideoEmbed = buildVideoEmbed;
})(jQuery);
