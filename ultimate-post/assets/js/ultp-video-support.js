(function ($) {
	("use strict");
	$(document).on("click", ".ultp-video-icon", function () {
		const vid = $(this);
		const parent = vid.parents(".ultp-block-item");
		const blockImage = vid.closest(".ultp-block-image");
		const videoContent = blockImage.find("div.ultp-block-video-content");

		let isAutoPlay = parent.find(".ultp-video-icon").attr("enableAutoPlay");
		let enablePopup = parent.find(".ultp-video-icon").attr("enableVideoPopup");
		let videoIframe = parent.find("iframe");

		const hasIframe = videoContent.find("iframe").length > 0;
		const hasVideo = videoContent.find("video").length > 0;

		// Inline video display without popup
		if (!enablePopup && (hasIframe || hasVideo)) {
			blockImage.find("> a img").hide();
			videoContent.css({ display: "block" });
			vid.hide();

			if (hasIframe || hasVideo) {
				videoIframe =
					videoContent.find("iframe").length > 0
						? videoContent.find("iframe")
						: videoContent.find("video");

				if (hasVideo && isAutoPlay) {
					videoContent.find("video").trigger("play");
				}
			}
		}

		// Handle video modal and autoplay
		if (videoIframe.length) {
			parent.find(".ultp-video-modal").addClass("modal_active");

			const videoSrc = videoIframe.attr("src");
			if (videoSrc && isAutoPlay) {
				if (videoSrc.includes("dailymotion.com/player")) {
					videoIframe.attr(
						"src",
						videoSrc.includes("?autoplay=0")
							? videoSrc.replace("?autoplay=0", "&?autoplay=1")
							: `${videoSrc}?autoplay=1`
					);
				} else {
					videoIframe.attr("src", `${videoSrc}&autoplay=1`);
				}
			}

			// Hide loader on video load
			videoIframe.on("load", function () {
				$(".ultp-loader-container").hide();
			});
		} else {
			parent.find(".ultp-video-modal").addClass("modal_active");
			$(".ultp-video-modal.modal_active").find("video").trigger("play");
		}
	});

	// Close On Click
	$(document).on("click", ".ultp-video-close", function () {
		closeVideoModal();
	});
	// Escape for Close Modal
	$(document).on("keyup", function (e) {
		if (e.key == "Escape") {
			closeVideoModal();
		}
	});
	function closeVideoModal() {
		if ($(".ultp-video-modal.modal_active").length > 0) {
			let videoIframe = $(".ultp-video-modal.modal_active").find("iframe");
			if (videoIframe.length) {
				const videoSrc = videoIframe.attr("src");
				if (videoSrc) {
					let stopVideo = "";
					if (videoSrc.includes("dailymotion.com/player")) {
						stopVideo = videoSrc.replaceAll("&?autoplay=1", "?autoplay=0");
					} else {
						stopVideo = videoSrc.replaceAll("&autoplay=1", "");
					}
					if (stopVideo) {
						videoIframe.attr("src", stopVideo);
					}
				}
			} else {
				$(".ultp-video-modal.modal_active").find("video").trigger("pause");
			}
			$(".ultp-video-modal").removeClass("modal_active");
		}
	}
})(jQuery);
