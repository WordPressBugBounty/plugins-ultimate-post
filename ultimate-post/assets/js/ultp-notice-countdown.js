/**
 * Countdown for PostX banner notices. A banner that has its campaign end time counts down to that
 * time. A banner without one keeps a per-browser timer in localStorage. Both stop at zero and never restart.
 */
jQuery(function($) {
	'use strict';

	const storagePrefix = 'ultp_notice_countdown_';

	const formatCountdown = function(seconds) {
		const days = Math.floor(seconds / 86400);
		const hours = Math.floor((seconds % 86400) / 3600);
		const minutes = Math.floor((seconds % 3600) / 60);
		const secs = seconds % 60;

		return String(days).padStart(2, '0') + ':' + String(hours).padStart(2, '0') + ':' + String(minutes).padStart(2, '0') + ':' + String(secs).padStart(2, '0');
	};

	const parseDurationToSeconds = function(duration) {
		if (typeof duration === 'number' && Number.isFinite(duration) && duration > 0) {
			return Math.floor(duration);
		}

		const durationString = String(duration || '').trim();
		if (/^\d+$/.test(durationString)) {
			return parseInt(durationString, 10);
		}

		return 0;
	};

	const parseEndTimeToSeconds = function(endTime) {
		const seconds = parseInt(endTime, 10);
		return Number.isFinite(seconds) && seconds > 0 ? seconds : 0;
	};

	const nowInSeconds = function() {
		return Math.floor(Date.now() / 1000);
	};

	// Shows the remaining time now and then every second. Stops at zero; does not restart.
	const runCountdown = function(countdownElement, getRemaining) {
		const firstRemaining = Math.max(getRemaining(), 0);
		countdownElement.text(formatCountdown(firstRemaining));

		if (firstRemaining > 0) {
			const timerId = window.setInterval(function() {
				const remaining = Math.max(getRemaining(), 0);
				countdownElement.text(formatCountdown(remaining));

				if (remaining <= 0) {
					window.clearInterval(timerId);
				}
			}, 1000);
		}
	};

	$('.ultp-notice-countdown').each(function() {
		const countdownElement = $(this);
		const noticeKey = String(countdownElement.data('noticeKey') || '');
		const campaignEnd = parseEndTimeToSeconds(countdownElement.data('endTime'));
		const duration = parseDurationToSeconds(countdownElement.data('duration'));

		if (!noticeKey || (campaignEnd <= 0 && duration <= 0)) {
			return;
		}

		if (campaignEnd > 0) {
			runCountdown(countdownElement, function() {
				return campaignEnd - nowInSeconds();
			});
			return;
		}

		// No campaign end time: keep a per-browser timer that starts on the first view.
		const storageKey = storagePrefix + noticeKey;
		let endAt = 0;

		try {
			const storedDataRaw = window.localStorage.getItem(storageKey);
			if (storedDataRaw) {
				const storedData = JSON.parse(storedDataRaw);
				if (storedData && parseInt(storedData.duration, 10) === duration) {
					endAt = parseInt(storedData.endAt, 10) || 0;
				}
			}
		} catch (error) {
			endAt = 0;
		}

		if (endAt <= 0) {
			endAt = nowInSeconds() + duration;
			try {
				window.localStorage.setItem(
					storageKey,
					JSON.stringify({
						endAt: endAt,
						duration: duration,
					})
				);
			} catch (error) {
				// No-op.
			}
		}

		runCountdown(countdownElement, function() {
			return endAt - nowInSeconds();
		});
	});
});
