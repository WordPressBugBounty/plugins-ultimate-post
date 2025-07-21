(function ($) {
    "use strict";

    const isFront = $("body.postx-admin-page").length == 0;
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
            $.ajax({
                url: ultp_data_frontend.ajax,
                type: "POST",
                data: {
                    action: "ultp_share_count",
                    shareCount: count,
                    postId: id,
                    wpnonce: ultp_data_frontend.security,
                },
                error: function (xhr) {
                    console.log(
                        "Error occured.please try again" + xhr.statusText + xhr.responseText
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
                ".wp-block-ultimate-post-post_share .ultp-block-wrapper .ultp-disable-sticky-footer"
            ).addClass("remove-sticky");
        } else {
            $(
                ".wp-block-ultimate-post-post_share .ultp-block-wrapper .ultp-disable-sticky-footer"
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
            $(".wp-block-ultimate-post-table-of-content").addClass("ultp-toc-scroll");
        } else {
            $(".ultp-toc-backtotop").removeClass("tocshow");
            $(".wp-block-ultimate-post-table-of-content").removeClass(
                "ultp-toc-scroll"
            );
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
            500
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
        }
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
        function (e) { }
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
        const ultpUniqueIds = sessionStorage.getItem("ultp_uniqueIds");
        const ultpCurrentUniquePosts = JSON.stringify(
            wrap.find(".ultp-current-unique-posts").data("current-unique-posts")
        );

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

        $.ajax({
            url: ultp_data_frontend.ajax,
            type: "POST",
            data: {
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
                ultpUniqueIds: ultpUniqueIds || [],
                ultpCurrentUniquePosts: ultpCurrentUniquePosts || [],

                ...advFilterData,

                wpnonce: ultp_data_frontend.security,
            },
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
                    setSession(
                        "ultp_uniqueIds",
                        JSON.stringify(
                            wrap.find(".ultp-current-unique-posts").data("ultp-unique-ids")
                        )
                    );
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
                handleDailyMotion();
            },
            error: function (xhr) {
                console.log(
                    "Error occured.please try again" + xhr.statusText + xhr.responseText
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

        const ultpUniqueIds = sessionStorage.getItem("ultp_uniqueIds");
        const ultpCurrentUniquePosts = JSON.stringify(
            parents.find(".ultp-current-unique-posts").data("current-unique-posts")
        );

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

        $.ajax({
            url: ultp_data_frontend.ajax,
            type: "POST",
            data: {
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
                ultpUniqueIds: ultpUniqueIds || [],
                ultpCurrentUniquePosts: ultpCurrentUniquePosts || [],

                ...advFilterData,

                wpnonce: ultp_data_frontend.security,
            },
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

                    parents.find(".ultp-current-unique-posts").remove();

                    const insertPoint = parents.find(".ultp-loadmore-insert-before");

                    if (insertPoint.length) {
                        // Adv Pagination block loadmore fix for post modules
                        if (that.data("blockname").includes("post-module")) {
                            $(
                                '<div style="clear:left;width:100%;padding-block:15px;"></div>'
                            ).insertBefore(insertPoint);
                        }

                        $(data).insertBefore(insertPoint);
                    } else {
                        const cardDiv = parents.find(".ultp-block-items-wrap");

                        if (that.data("blockname").includes("post-module")) {
                            cardDiv.append(
                                $(
                                    '<div style="clear:left;width:100%;padding-block:15px;"></div>'
                                )
                            );
                        }

                        cardDiv.append(data);
                    }

                    setSession(
                        "ultp_uniqueIds",
                        JSON.stringify(
                            parents.find(".ultp-current-unique-posts").data("ultp-unique-ids")
                        )
                    );
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
                    "Error occured.please try again" + xhr.statusText + xhr.responseText
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
    $(document).on("click", ".ultp-filter-wrap li a", function (e) {
        e.preventDefault();

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

            const ultpUniqueIds = sessionStorage.getItem("ultp_uniqueIds");
            const ultpCurrentUniquePosts = JSON.stringify(
                wrap.find(".ultp-current-unique-posts").data("current-unique-posts")
            );

            if (parents.data("blockid")) {
                $.ajax({
                    url: ultp_data_frontend.ajax,
                    type: "POST",
                    data: {
                        action: "ultp_filter",
                        taxtype: parents.data("taxtype"),
                        taxonomy: that.data("taxonomy"),
                        blockId: parents.data("blockid"),
                        postId: post_ID,
                        blockName: parents.data("blockname"),
                        widgetBlockId: widgetBlockId,
                        ultpUniqueIds: ultpUniqueIds || [],
                        ultpCurrentUniquePosts: ultpCurrentUniquePosts || [],
                        wpnonce: ultp_data_frontend.security,
                    },
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
                        setSession(
                            "ultp_uniqueIds",
                            JSON.stringify(
                                wrap.find(".ultp-current-unique-posts").data("ultp-unique-ids")
                            )
                        );
                        handleDailyMotion();
                    },
                    error: function (xhr) {
                        console.log(
                            "Error occured.please try again" +
                            xhr.statusText +
                            xhr.responseText
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

    // set session value for unique content on page reload
    if ($(".ultp-current-unique-posts").length > 0) {
        $(".ultp-current-unique-posts").each(function () {
            setSession(
                "ultp_uniqueIds",
                JSON.stringify($(this).data("ultp-unique-ids"))
            );
        });
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
            url
        );
    }

    $(document).on("click", ".ultp-pagination-ajax-action li", function (e) {
        e.preventDefault();
        let that = $(this),
            parents = that.closest(".ultp-pagination-ajax-action"),
            wrap = that.closest(".ultp-block-wrapper");

        const blockid = parents.attr("data-blockid");

        // Pagination block integration
        if (wrap.length < 1) {
            const pagiFor = parents.data("for");
            if (pagiFor) {
                wrap = $("." + pagiFor + " .ultp-block-wrapper");
            }
        }

        if (parents.is(".ultp-disable-editor-click")) {
            return;
        }
        let pageNum = 1;
        let pages = parents.attr("data-pages");

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

        const ultpUniqueIds = sessionStorage.getItem("ultp_uniqueIds");
        const ultpCurrentUniquePosts = JSON.stringify(
            wrap.find(".ultp-current-unique-posts").data("current-unique-posts")
        );

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

        if (pageNum) {
            if (blockid) {
                setCurrPage(blockid, pageNum, getCurrPage(blockid));
            }

            $.ajax({
                url: ultp_data_frontend.ajax,
                type: "POST",
                data: {
                    exclude: parents.data("expost"),
                    action: "ultp_pagination",
                    paged: pageNum,
                    blockId: parents.data("blockid"),
                    postId: post_ID,
                    blockName: parents.data("blockname"),
                    builder: parents.data("builder"),
                    widgetBlockId: widgetBlockId,
                    ultpUniqueIds: ultpUniqueIds || [],
                    ultpCurrentUniquePosts: ultpCurrentUniquePosts || [],

                    filterType: parents.data("filter-type") || "",
                    filterValue: filterValue,

                    ...advFilterData,

                    wpnonce: ultp_data_frontend.security,
                },
                beforeSend: function () {
                    wrap.addClass("ultp-loading-active");
                },
                success: function (data) {
                    wrap.find(".ultp-block-items-wrap").html(data);
                    setSession(
                        "ultp_uniqueIds",
                        JSON.stringify(
                            wrap.find(".ultp-current-unique-posts").data("ultp-unique-ids")
                        )
                    );
                    if ($(window).scrollTop() > wrap.offset().top) {
                        $([document.documentElement, document.body]).animate(
                            {
                                scrollTop: wrap.offset().top - 80,
                            },
                            100
                        );
                    }
                },
                complete: function () {
                    wrap.removeClass("ultp-loading-active");
                    handleDailyMotion();
                },
                error: function (xhr) {
                    console.log(
                        "Error occured.please try again" + xhr.statusText + xhr.responseText
                    );
                    wrap.removeClass("ultp-loading-active");
                },
            });
        }
    });

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
            ".wp-block-ultimate-post-post-slider-1, .wp-block-ultimate-post-post-slider-2"
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

            let layTemp =
                selector.data("layout") == "slide2" ||
                selector.data("layout") == "slide3" ||
                selector.data("layout") == "slide5" ||
                selector.data("layout") == "slide6" ||
                selector.data("layout") == "slide8";

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
                // Slider 2
                if (selector.data("fade") && layTemp) {
                    settings.fade = selector.data("fade") ? true : false;
                } else if (!selector.data("fade") && layTemp) {
                    (settings.slidesToShow = selector.data("slidelg") || 1),
                        (settings.responsive = [
                            {
                                breakpoint: 991,
                                settings: {
                                    slidesToShow: selector.data("slidesm") || 1,
                                    slidesToScroll: 1,
                                },
                            },
                            {
                                breakpoint: 767,
                                settings: {
                                    slidesToShow: selector.data("slidexs") || 1,
                                    slidesToScroll: 1,
                                },
                            },
                        ]);
                } else {
                    (settings.slidesToShow = selector.data("slidelg") || 1),
                        (settings.centerMode = true);
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
    slideshowDisplay();

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
    // Post Grid Popup Modal
    // *************************************
    function handleDailyMotion() {
        $(
            ".ultp-video-modal .ultp-video-modal__content .ultp-video-wrapper > iframe"
        ).each(function () {
            const that = $(this);
            const videoSrc = that.attr("src");
            if (
                videoSrc &&
                videoSrc.includes("dailymotion.com/player") &&
                videoSrc[videoSrc.length - 1] == "&"
            ) {
                that.attr(
                    "src",
                    videoSrc.slice(0, videoSrc.length - 1) + "?autoplay=0"
                );
            }
        });
    }
    handleDailyMotion();

    // $(document).on("click", ".ultp-block-item .ultp-video-icon", function () {
    //     const parent = $(this).parents(".ultp-block-item");
    //     let videoIframe = parent.find("iframe");
    //     parent.find(".ultp-video-modal").addClass("modal_active");
    //     if (videoIframe.length) {
    //         let isAutoPlay = parent.find(".ultp-video-icon").attr("enableAutoPlay");
    //         // Update Src For Autoplay
    //         const videoSrc = videoIframe.attr("src");
    //         if (videoSrc && isAutoPlay) {
    //             if (videoSrc.includes("dailymotion.com/player")) {
    //                 videoIframe.attr(
    //                     "src",
    //                     videoSrc.includes("?autoplay=0")
    //                         ? videoSrc.replace("?autoplay=0", "&?autoplay=1")
    //                         : `${videoSrc}?autoplay=1`
    //                 );
    //             } else {
    //                 videoIframe.attr("src", `${videoSrc}&autoplay=1`);
    //             }
    //         }
    //         // Spinner for Video
    //         parent.find("iframe").on("load", function () {
    //             $(".ultp-loader-container").css("display", "none");
    //         });
    //     } else {
    //         $(".ultp-video-modal.modal_active").find("video").trigger("play");
    //     }
    // });
    
    $(document).on("click", ".ultp-video-icon", function () {
        const vid = $(this);
        const parent = vid.parents(".ultp-block-item");
        const blockImage = vid.closest('.ultp-block-image');
        const videoContent = blockImage.find('div.ultp-block-video-content');

        let isAutoPlay = parent.find(".ultp-video-icon").attr("enableAutoPlay");
        let enablePopup = parent.find(".ultp-video-icon").attr("enableVideoPopup");
        let videoIframe = parent.find("iframe");

        const hasIframe = videoContent.find("iframe").length > 0;
        const hasVideo = videoContent.find("video").length > 0;

        // Inline video display without popup
        if (!enablePopup && (hasIframe || hasVideo)) {
            blockImage.find('> a img').hide();
            videoContent.css({ display: 'block' });
            vid.hide();

            if (hasIframe || hasVideo) {
                videoIframe = videoContent.find("iframe").length > 0
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

    // *************************************
    //  Video Scroll
    // *************************************
    let isSticky = true;
    $(window).on("scroll", function () {
        let windowHeight = $(this).scrollTop();
        $(".wp-block-ultimate-post-post-image").each(function () {
            let contentSelector = $(this).find(
                ".ultp-builder-video video , .ultp-builder-video iframe"
            );
            if ($(this).find(".ultp-video-block").hasClass("ultp-sticky-video")) {
                // block height and position
                let blockContent = $(this).find(".ultp-image-wrapper");
                let blockPosition = blockContent.offset();
                // Video Html height and position
                let videoContent = contentSelector.height();
                let videoPosition = contentSelector.offset();
                // Exclude Adminbar height
                let windowTotalHeight = windowHeight + ($("#wpadminbar").height() || 0);
                let totalHeight = videoPosition.top + videoContent;
                // Scrolling Top to bottom
                if (windowTotalHeight > videoPosition.top) {
                    if (windowTotalHeight > totalHeight && isSticky) {
                        $(this)
                            .find(".ultp-image-wrapper")
                            .css("height", blockContent.height());
                        $(this).find(".ultp-sticky-video").addClass("ultp-sticky-active");
                    }
                }
                // Scrolling bottom to top
                if (windowTotalHeight < blockContent.height() + blockPosition.top) {
                    $(this).find(".ultp-sticky-video").removeClass("ultp-sticky-active");
                    $(this).find(".ultp-image-wrapper").css("height", "auto");
                }
                // Close Button
                $(".ultp-sticky-close").on("click", function () {
                    $(this).find(".ultp-image-wrapper").css("height", "auto");
                    $(".ultp-sticky-video").removeClass("ultp-sticky-active");
                    isSticky = false;
                });
            }
        });
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

    function applyFilter(parent, blockId, blockName, postId, grids) {
        const selectedFilters = [];
        const filterData = {};
        const pagis = parent.data("pagi");

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

            if (getTax(type) === "custom_tax") {
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

            if (getTax(type) === "custom_tax") {
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

        const searchVal = parent.find(".ultp-filter-search input");
        if (searchVal.length > 0) {
            filterData.search = searchVal.val();
        }

        const ultpUniqueIds = sessionStorage.getItem("ultp_uniqueIds");
        const ultpCurrentUniquePosts = JSON.stringify(
            parent.find(".ultp-current-unique-posts").data("current-unique-posts")
        );

        let widgetBlockId = "";
        let widgetBlock = parent.parents(".widget_block:first");
        if (widgetBlock.length > 0) {
            let widget_items = widgetBlock.attr("id").split("-");
            widgetBlockId = widget_items[widget_items.length - 1];
        }

        $(grids).each(function () {
            const grid = $(this);

            $.ajax({
                url: ultp_data_frontend.ajax,
                type: "POST",
                data: {
                    action: "ultp_adv_filter",

                    ...filterData,

                    blockId: blockId,
                    blockName: blockName,
                    postId: postId,
                    ultpUniqueIds: ultpUniqueIds || [],
                    ultpCurrentUniquePosts: ultpCurrentUniquePosts || [],
                    widgetBlockId: widgetBlockId,
                    wpnonce: ultp_data_frontend.security,
                },
                beforeSend: function () {
                    grid.addClass("ultp-loading-active");
                },
                success: function (response) {
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
                                "</div>"
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

                    // Pagination Block Integration
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
                    setSession(
                        "ultp_uniqueIds",
                        JSON.stringify(
                            parent.find(".ultp-current-unique-posts").data("ultp-unique-ids")
                        )
                    );
                },
                error: function (xhr) {
                    console.log(
                        "Error occured.please try again" + xhr.statusText + xhr.responseText
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
        const postId = parent.attr("data-postid");
        const selectedFilterTemp = $(this).find(".ultp-filter-clear-template");
        const clearBtn = $(this).find(".ultp-filter-clear-button");
        const firstElClass = "ultp-block-" + clearBtn.data("blockid") + "-first";

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

            // Resetting search input
            that.find(".ultp-filter-search input").val("");

            // Resetting filter buttons
            that.find('.ultp-filter-button[data-is-active="true"]').each(function () {
                $(this).removeClass("ultp-filter-button-active");
                $(this).attr("data-is-active", "false");
            });
        }

        function fetchPostsWithFilter() {
            gridData.forEach((data) => {
                applyFilter(parent, data["blockId"], data["name"], postId, grids);
            });
        }

        that.find(".ultp-filter-select").each(function () {
            const dropdown = $(this).find(".ultp-filter-select-options");
            const selected = $(this).find(".ultp-filter-select-field-selected");
            const icon = $(this).find(".ultp-filter-select-field-icon");
            const filter = $(this);
            const type = $(this).attr("data-type");
            const input = $(this).find(".ultp-filter-select-search");

            function showDropdown(show) {
                if (show) {
                    $(".ultp-filter-select .ultp-filter-select-options").css(
                        "display",
                        "none"
                    );
                    $(".ultp-filter-select .ultp-filter-select-field-icon").removeClass(
                        "ultp-dropdown-icon-rotate"
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
                                .text(getFormattedSelectedFilter(type, text));

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
                            currValue.toLowerCase().includes(value) ? "list-item" : "none"
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
            $(this).on("click", function () {
                const isActive = $(currFBtn).attr("data-is-active") === "true";
                const value = $(this).data("selected");

                // Clicking all button will disable other
                if (value === "_all") {
                    const otherBtns = that.find(
                        '.ultp-filter-button[data-selected]:not([data-selected="_all"])'
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
                        `.ultp-filter-button[data-type="${fBtnType}"]`
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
                }

                fetchPostsWithFilter();
            });
        });

        clearBtn.on("click", function () {
            resetFilters();
            fetchPostsWithFilter();
        });

        let searchTimeout;

        function performSearch(isDebounce) {
            clearTimeout(searchTimeout);
            searchTimeout = isDebounce
                ? setTimeout(fetchPostsWithFilter, 500)
                : fetchPostsWithFilter();
        }

        parent
            .find(".ultp-filter-search input")
            .off("input")
            .on("input", function () {
                performSearch(true);
            });

        parent.find(".ultp-filter-search input").on("keydown", function (e) {
            if (e.key === "Enter") {
                performSearch(false);
            }
        });

        parent.find(".ultp-filter-search-icon").on("click", function () {
            performSearch(false);
        });
    });

    // *************************************
    //   Search Block
    // *************************************
    if ($(".wp-block-ultimate-post-advanced-search").length) {
        let postPages = 1;

        // Search Clear Text Button
        $(document).on("click", ".ultp-search-clear", function () {
            postPages = 1;
            const blockId = $(this).data("blockid");
            $(this)
                .parents(".ultp-search-inputwrap")
                .find(".ultp-searchres-input")
                .val("");
            $(this).removeClass("active");
            $(`.ultp-block-${blockId}`).find(".ultp-result-data").html("");
            $(`.ultp-block-${blockId}`)
                .find(
                    ".ultp-search-noresult, .ultp-viewall-results, .ultp-result-loader"
                )
                .removeClass("active");
        });

        // Popup Window Close
        $(document).on("click", ".ultp-popupclose-icon", function () {
            $(this).parents(".result-data").removeClass("popup-active");
        });

        // Search Button Popup Icon
        $(document).on("click", ".ultp-searchpopup-icon", function () {
            const el = $(this).parents(".ultp-search-frontend");
            const blockId = el.data("blockid");
            handleSetPosition(
                el,
                $(`.result-data.ultp-block-${blockId}`).length ? false : true
            );
            $(`.result-data.ultp-block-${blockId}`).toggleClass("popup-active");
        });
        // In search result page clear button active
        if ($(".ultp-searchres-input").val().length > 2) {
            $(".ultp-searchres-input")
                .closest(".ultp-search-inputwrap")
                .find(".ultp-search-clear")
                .addClass("active");
        }
        // Input On Change Action
        $(document).on("input", ".ultp-searchres-input", function (e) {
            searchResultAPI($(this), e.target.value);
        });

        const searchResultAPI = (
            that,
            searchText,
            blockId = "",
            isAppend = true
        ) => {
            blockId = blockId
                ? blockId
                : that
                    .parents(".ultp-search-inputwrap")
                    .find(".ultp-search-clear")
                    .data("blockid");
            const el = $(
                `.wp-block-ultimate-post-advanced-search.ultp-block-${blockId}`
            ).find(".ultp-search-frontend");
            const selector = $(`.result-data.ultp-block-${blockId}`);
            // Set PopUp Positions
            handleSetPosition(el, selector.length ? false : true);

            if (searchText.length > 2) {
                if (el.data("ajax")) {
                    selector.find(".ultp-search-result").addClass("ultp-search-show");
                    selector.find(".ultp-result-loader").addClass("active");
                    selector.addClass("popup-active");
                    wp.apiFetch({
                        path: "/ultp/ultp_search_data",
                        method: "POST",
                        data: {
                            searchText: searchText,
                            date: parseInt(el.data("date")),
                            image: parseInt(el.data("image")),
                            author: parseInt(el.data("author")),
                            excerpt: parseInt(el.data("excerpt")),
                            category: parseInt(el.data("catenable")),
                            excerptLimit: parseInt(el.data("excerptlimit")),
                            postPerPage: el.data("allresult") ? el.data("postno") : 10,
                            exclude:
                                typeof el.data("searchposttype") !== "string" &&
                                el.data("searchposttype").length > 0 &&
                                el.data("searchposttype"),
                            paged: postPages,
                            wpnonce: ultp_data_frontend.security,
                        },
                    }).then((res) => {
                        if (res.post_data) {
                            if (isAppend) {
                                selector
                                    .find(".ultp-search-result")
                                    .addClass("ultp-search-show");
                                selector.find(".ultp-result-data").addClass("ultp-result-show");
                                selector.find(".ultp-result-data").html(res.post_data);
                            } else {
                                selector
                                    .find(".ultp-search-result")
                                    .addClass("ultp-search-show");
                                selector.find(".ultp-result-data").addClass("ultp-result-show");

                                selector
                                    .find(".ultp-result-data")
                                    .append(res.post_data)
                                    .fadeIn(500, function () {
                                        $(this).animate(
                                            { scrollTop: $(this).prop("scrollHeight") },
                                            400
                                        );
                                    });
                            }
                            selector
                                .find(".ultp-search-noresult, .ultp-result-loader")
                                .removeClass("active");
                            const itemCount = selector.find(
                                ".ultp-result-data .ultp-search-result__item"
                            ).length;
                            selector
                                .find(".ultp-viewall-results")
                                .addClass("active")
                                .find("span")
                                .text(`(${res.post_count - itemCount})`);
                        } else {
                            selector
                                .find(".ultp-result-data")
                                .removeClass("ultp-result-show");
                            selector.find(".ultp-result-data").html("");
                            selector.find(".ultp-search-noresult").addClass("active");
                            selector
                                .find(".ultp-result-loader, .ultp-viewall-results")
                                .removeClass("active");
                        }
                        if (el.data("allresult")) {
                            const itemCount = selector.find(
                                ".ultp-result-data .ultp-search-result__item"
                            ).length;
                            if (res.post_count && res.post_count > itemCount) {
                                selector
                                    .find(".ultp-viewall-results")
                                    .addClass("active")
                                    .find("span")
                                    .text(`(${res.post_count - itemCount})`);
                            } else {
                                selector.find(".ultp-viewall-results").removeClass("active");
                            }
                        }
                    });
                }
            } else {
                selector.find(".ultp-search-result").removeClass("ultp-search-show");
                selector.find(".ultp-result-data").removeClass("ultp-result-show");
                selector.find(".ultp-search-noresult").removeClass("active");
            }
            if (searchText.length < 3) {
                postPages = 1;
                selector.find(".ultp-result-data").html("");
                selector.find(".ultp-viewall-results").removeClass("active");
                selector.find(".ultp-search-noresult").removeClass("active");

                // Clear Button hide
                el.find(".ultp-search-clear").removeClass("active");
                $(`.result-data.ultp-block-${blockId}`)
                    .find(".ultp-search-clear")
                    .removeClass("active");
            } else {
                // Clear Button Show
                el.find(".ultp-search-clear").addClass("active");
                $(`.result-data.ultp-block-${blockId}`)
                    .find(".ultp-search-clear")
                    .addClass("active");
            }
        };

        // View All Result
        $(document).on("click", ".ultp-viewall-results", function (e) {
            postPages++;
            const blockId = $(this).closest(".result-data").data("blockid");
            searchResultAPI(
                $(this),
                $(`.ultp-block-${blockId} .ultp-searchres-input`).val(),
                blockId,
                false
            );
        });

        // Outside Click Close Popup [Done]
        if ($(".wp-block-ultimate-post-advanced-search").length > 0) {
            $(document).on("click", function (e) {
                if (
                    !$(e.target).closest(".ultp-searchpopup-icon").length &&
                    !$(e.target).closest(".ultp-searchres-input").length
                ) {
                    if (!$(e.target).closest(".result-data.popup-active").length) {
                        $(".result-data").removeClass("popup-active");
                    }
                }
                if (!$(e.target).closest(".ultp-search-frontend").length) {
                    if (!$(e.target).closest(".result-data.popup-active").length) {
                        $(".result-data").removeClass("popup-active");
                    }
                }
            });
        }

        // Enter Key In Search Box
        $(document).on("keyup", ".ultp-searchres-input", function (e) {
            const blockId = $(this)
                .closest(".ultp-search-inputwrap")
                .find(".ultp-search-clear")
                .data("blockid");
            const goSearch = $(
                `.wp-block-ultimate-post-advanced-search.ultp-block-${blockId}`
            )
                .find(".ultp-search-frontend")
                .data("gosearch");
            const newTabData = $(
                `.wp-block-ultimate-post-advanced-search.ultp-block-${blockId}`
            )
                .find(".ultp-search-frontend")
                .data("enablenewtab");
            let tabTarget = "_self";
            if (newTabData) {
                tabTarget = "_blank";
            }
            if (goSearch) {
                if (e.key == "Enter" && $(this).val().length > 2) {
                    const el = $(
                        `.wp-block-ultimate-post-advanced-search.ultp-block-${blockId}`
                    ).find(".ultp-search-frontend");
                    let exclude =
                        typeof el.data("searchposttype") !== "string" &&
                        el.data("searchposttype")?.length > 0 &&
                        el?.data("searchposttype");
                    exclude = exclude.length
                        ? `&ultp_exclude=${JSON.stringify(
                            exclude.map((e) => {
                                return e.value;
                            })
                        )}`
                        : "";
                    window.open(
                        `${ultp_data_frontend.home_url}/?s=${$(this).val()}${exclude}`,
                        tabTarget
                    );
                }
            }
        });

        // Search Button Click Event
        $(document).on("click", ".ultp-search-button", function (e) {
            const blockId = $(this)
                .closest(".ultp-searchform-content")
                .find(".ultp-search-clear")
                .data("blockid");
            const goSearch = $(
                `.wp-block-ultimate-post-advanced-search.ultp-block-${blockId}`
            )
                .find(".ultp-search-frontend")
                .data("gosearch");
            const newTabData = $(
                `.wp-block-ultimate-post-advanced-search.ultp-block-${blockId}`
            )
                .find(".ultp-search-frontend")
                .data("enablenewtab");
            let tabTarget = "_self";
            if (newTabData) {
                tabTarget = "_blank";
            }
            if (goSearch) {
                const el = $(
                    `.wp-block-ultimate-post-advanced-search.ultp-block-${blockId}`
                ).find(".ultp-search-frontend");
                let exclude =
                    typeof el.data("searchposttype") !== "string" &&
                    el.data("searchposttype")?.length > 0 &&
                    el?.data("searchposttype");
                exclude = exclude.length
                    ? `&ultp_exclude=${JSON.stringify(
                        exclude.map((e) => {
                            return e.value;
                        })
                    )}`
                    : "";
                window.open(
                    `${ultp_data_frontend.home_url}/?s=${$(this)
                        .closest(".ultp-searchform-content")
                        .find(".ultp-searchres-input")
                        .val()}${exclude}`,
                    tabTarget
                );
            } else {
                $(`.result-data.ultp-block-${blockId}`).addClass("popup-active");
            }
        });

        // Search Input Click Popup Show
        $(document).on("click", ".ultp-searchres-input", function (e) {
            const blockId = $(this)
                .closest(".ultp-searchform-content")
                .find(".ultp-search-clear")
                .data("blockid");
            $(".result-data").removeClass("popup-active");
            $(`.result-data.ultp-block-${blockId}`).addClass("popup-active");
        });

        // Resize Window Popup Position Reset
        $(window).on("resize", function () {
            if ($(".ultp-search-result").length > 0) {
                $(".ultp-search-frontend").each(function (el) {
                    handleSetPosition($(el));
                });
            }
        });

        // Popup Top/Left Position and Append Result Content
        const handleSetPosition = (el, newBlock = false) => {
            const blockId = el.data("blockid");
            const popupType = el.data("popuptype");
            const popupposition = el.data("popupposition");

            if (newBlock) {
                const viewAllResult = el.data("allresult");
                const searchResultTemplate = `<div class="ultp-search-result" data-image=${el.data("image") || false
                    } data-author=${el.data("author") || false} data-date=${el.data("date") || false
                    } data-excerpt=${el.data("excerpt") || false
                    } data-excerptlimit=${el.data("excerptlimit")} data-allresult=${viewAllResult || false
                    } data-catenable=${el.data("catenable") || false} data-postno=${el.data("postno") || false
                    } data-gosearch=${el.data("gosearch") || false} data-popupposition=${popupposition || false
                    }>
                    <div class="ultp-result-data"></div>
                    <div class="ultp-search-result__item ultp-search-noresult">${el.data(
                        "noresultext"
                    )}</div>
                    <div class="ultp-search-result__item ultp-result-loader"></div>
                    ${viewAllResult
                        ? `<div class="ultp-viewall-results ultp-search-result__item">${el.data(
                            "viewmoretext"
                        )}<span></span></div><div class="ultp-search-result__item ultp-viewmore-loader"></div>`
                        : ""
                    }
                    </div>`;

                if (popupType) {
                    const canvas = $(`.ultp-block-${blockId}`)
                        .find(".ultp-search-canvas")
                        .detach();
                    $("body").append(
                        `<div class="result-data ultp-block-${blockId} ultp-search-animation-${popupType}" data-blockid=${blockId}><div class="ultp-search-canvas">${canvas.html() + (el.data("ajax") ? searchResultTemplate : "")
                        }</div></div>`
                    );
                } else {
                    $("body").append(
                        `<div class="result-data ultp-block-${blockId}" data-blockid=${blockId}>${searchResultTemplate}</div>`
                    );
                }
            }

            let posSelector = "";
            if (!popupType) {
                posSelector = el.find(".ultp-searchform-content");
                const elementPosition = posSelector.offset();
                return $(`body > .ultp-block-${blockId}`).css({
                    width: `${posSelector.width()}px`,
                    top: `${elementPosition?.top + posSelector.height()}px`,
                    left: `${elementPosition?.left}px`,
                });
            }
            if (popupType == "popup") {
                posSelector = el.find(".ultp-searchpopup-icon");
                const elementPosition = posSelector.offset();
                const contentPosition =
                    popupposition == "right"
                        ? elementPosition?.left > $(`body > .ultp-block-${blockId}`).width()
                        : $(document).width() - elementPosition?.left >
                        $(`body > .ultp-block-${blockId}`).width();
                let right = "";
                let left = "";
                if (popupposition == "right") {
                    right = contentPosition
                        ? $(document).width() -
                        elementPosition?.left -
                        posSelector.outerWidth() +
                        "px"
                        : "unset";
                    left = contentPosition
                        ? "auto"
                        : elementPosition?.left +
                        (popupposition == "right" ? 10 : 0) +
                        "px";
                } else {
                    right = contentPosition
                        ? "unset"
                        : $(document).width() -
                        elementPosition?.left -
                        posSelector.outerWidth() +
                        "px";
                    left = contentPosition
                        ? elementPosition?.left + (popupposition == "right" ? 10 : 0) + "px"
                        : "auto";
                }
                return $(`body > .ultp-block-${blockId}`).css({
                    top: `${elementPosition?.top + posSelector.outerHeight()}px`,
                    right: right,
                    left: left,
                });
            }
        };
    }

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
                `.ultp-${lightMode ? "dark" : "light"}-con`
            );
            const opponentParents = opponent.closest(".ultp-dl-after-before-con");

            const iconlay = currentParents.data("iconlay");
            const iconsize = currentParents.data("iconsize");
            const iconrev = currentParents.data("iconrev");

            let delay = 0;
            if (["layout5", "layout6", "layout7"].includes(iconlay)) {
                delay = iconlay == "layout7" ? 500 : 400;
                const extraSize =
                    iconlay == "layout7"
                        ? $(this).find(".ultp-dl-text").width()
                        : iconsize / 2;
                if (lightMode) {
                    $(this)
                        .find(".ultp-dl-svg-con")
                        .css({
                            transform: `translateX(calc(${100 * (iconrev ? -1 : 1)}% ${iconrev ? "-" : "+"
                                } ${extraSize}px))`,
                            transition: `transform ${delay / 1000}s ease`,
                        });
                    if (iconlay == "layout6") {
                        $(this)
                            .find(".ultp-dl-text")
                            .css({
                                transform: `translateX(calc(${100 * (iconrev ? 1 : -1)}% ${iconrev ? "+" : "-"
                                    } ${extraSize}px))`,
                                transition: `transform ${delay / 1000}s ease`,
                            });
                    } else if (iconlay == "layout7") {
                        $(this)
                            .find(".ultp-dl-text")
                            .css({
                                transform: `translateX(calc(${(iconrev ? 1 : -1) * iconsize
                                    }px))`,
                                transition: `transform ${delay / 1000}s ease`,
                            });
                    }
                } else {
                    $(this)
                        .find(".ultp-dl-svg-con")
                        .css({
                            transform: `translateX(calc(${100 * (iconrev ? 1 : -1)}% ${iconrev ? "+" : "-"
                                } ${extraSize}px))`,
                            transition: `transform ${delay / 1000}s ease`,
                        });
                    if (iconlay == "layout6") {
                        $(this)
                            .find(".ultp-dl-text")
                            .css({
                                transform: `translateX(calc(${100 * (iconrev ? -1 : 1)}% ${iconrev ? "-" : "+"
                                    } ${extraSize}px))`,
                                transition: `transform ${delay / 1000}s ease`,
                            });
                    } else if (iconlay == "layout7") {
                        $(this)
                            .find(".ultp-dl-text")
                            .css({
                                transform: `translateX(calc(${(iconrev ? -1 : 1) * iconsize
                                    }px))`,
                                transition: `transform ${delay / 1000}s ease`,
                            });
                    }
                }
            }
            setCookie("ultplocalDLMode", lightMode ? "ultpdark" : "ultplight", 60);
            setTimeout(() => {
                currentParents.addClass("inactive");
                opponentParents.removeClass("inactive");

                // handle dark light image block
                if (lightMode) {
                    $(".wp-block-ultimate-post-image .ultp-light-image-block").addClass(
                        "inactive"
                    );
                    $(".wp-block-ultimate-post-image .ultp-dark-image-block").removeClass(
                        "inactive"
                    );
                } else if (!lightMode) {
                    $(".wp-block-ultimate-post-image .ultp-dark-image-block").addClass(
                        "inactive"
                    );
                    $(
                        ".wp-block-ultimate-post-image .ultp-light-image-block"
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
                    `.ultp-dark-light-block-wrapper-content .ultp-${lightMode ? "dark" : "light"
                    }-con`
                ).each(function () {
                    $(this).closest(".ultp-dl-after-before-con").removeClass("inactive");
                });
                $(
                    `.ultp-dark-light-block-wrapper-content .ultp-${lightMode ? "light" : "dark"
                    }-con`
                ).each(function () {
                    $(this).closest(".ultp-dl-after-before-con").addClass("inactive");
                });

                // remove transition
                $(this).find(".ultp-dl-svg-con").removeAttr("style");
                $(this).find(".ultp-dl-text").removeAttr("style");

                handleDarkLight();
            }, delay);
        }
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
                "--postx_preset_Base_1_color"
            );
            const base2 = root.cssRules[0].style.getPropertyValue(
                "--postx_preset_Base_2_color"
            );
            const base3 = root.cssRules[0].style.getPropertyValue(
                "--postx_preset_Base_3_color"
            );

            const contrast1 = root.cssRules[0].style.getPropertyValue(
                "--postx_preset_Contrast_1_color"
            );
            const contrast2 = root.cssRules[0].style.getPropertyValue(
                "--postx_preset_Contrast_2_color"
            );
            const contrast3 = root.cssRules[0].style.getPropertyValue(
                "--postx_preset_Contrast_3_color"
            );

            root.cssRules[0].style.setProperty(
                "--postx_preset_Base_1_color",
                contrast1
            );
            root.cssRules[0].style.setProperty(
                "--postx_preset_Base_2_color",
                contrast2
            );
            root.cssRules[0].style.setProperty(
                "--postx_preset_Base_3_color",
                contrast3
            );

            root.cssRules[0].style.setProperty(
                "--postx_preset_Contrast_1_color",
                base1
            );
            root.cssRules[0].style.setProperty(
                "--postx_preset_Contrast_2_color",
                base2
            );
            root.cssRules[0].style.setProperty(
                "--postx_preset_Contrast_3_color",
                base3
            );
        }
    }

    /*************************************
         Tab Block
      *************************************/
    $('.ultp-tabs-content[data-show="click"] .ultp-tab-item-label').on(
        "click",
        function (e) {
            handleTabItem($(this));
        }
    );
    $('.ultp-tabs-content[data-show="hover"] .ultp-tab-item-label').hover(
        function (e) {
            handleTabItem($(this));
        }
    );
    function handleTabItem(that) {
        if (that.closest("body.block-editor-page").length) {
            return;
        }
        const parent = that.closest(".ultp-tabs-content");
        parent.find(".ultp-tab-item-label").removeClass("pt-active");
        that.addClass("pt-active");

        const tabId = that.data("tabid");
        parent
            .find("> .ultp-tabs-content-warp > .wp-block-ultimate-post-tab-item")
            .removeClass("pt-active");
        parent
            .find(
                `> .ultp-tabs-content-warp > .wp-block-ultimate-post-tab-item.${tabId}`
            )
            .addClass("pt-active");
    }

    /*************************************
         Menu Block
      *************************************/
    setTimeout(() => {
        handleMegaMenuWidth("setTimeout");
    }, 10);
    handleMegaMenuWidth("normal");

    function handleMegaMenuWidth(t) {
        if ($(".editor-styles-wrapper")?.length) {
            return;
        }
        const hasMegaMenu = $(
            ".wp-block-ultimate-post-menu-item.hasMegaMenuChild > .ultp-menu-item-wrapper > .ultp-menu-item-content"
        );
        if (hasMegaMenu.length > 0) {
            hasMegaMenu.each(function () {
                if ($(this).hasClass("ultpMegaWindowWidth")) {
                    const bodyWrapperWidth = $("body")?.width() || 1200;
                    const bodyWrapperLeft = $("body")?.offset()?.left || 0;
                    const contentLeft = $(this)?.offset()?.left || 0;
                    $(this)
                        .find(
                            " > .wp-block-ultimate-post-mega-menu > .ultp-mega-menu-wrapper"
                        )
                        .css({
                            maxWidth: `${bodyWrapperWidth}px`,
                            boxSizing: "border-box",
                        });
                    // $(this).css({ "left" : `${bodyWrapperLeft - contentLeft}px`});

                    const siblingLeft =
                        $(this).siblings(".ultp-menu-item-label-container")?.offset()
                            ?.left || 0;
                    $(this).css({ left: `${bodyWrapperLeft - siblingLeft}px` });

                    //do not remove $('.wp-block-ultimate-post-menu-item.hasMegaMenuChild > .ultp-menu-item-wrapper > .ultp-menu-item-content.ultpMegaWindowWidth > .block-editor-inner-blocks > .block-editor-block-list__layout > .wp-block > .wp-block-ultimate-post-mega-menu > .ultp-mega-menu-wrapper').css({ "maxWidth" : `${editorMainWrapperWidth}px`, "boxSizing": "border-box"});
                    //do not remove $('.wp-block-ultimate-post-menu-item.hasMegaMenuChild > .ultp-menu-item-wrapper > .ultp-menu-item-content.ultpMegaWindowWidth > .block-editor-inner-blocks').css({ "left" : `-${contentLeft - editorMainWrapperLeft}px`});
                } else if ($(this).hasClass("ultpMegaMenuWidth")) {
                    const closetMenuWidth =
                        $(this).closest(".wp-block-ultimate-post-menu")?.width() || 800;
                    const closetMenuLeft =
                        $(this).closest(".wp-block-ultimate-post-menu")?.offset()?.left ||
                        0;
                    const contentLeft = $(this)?.offset()?.left || 0;
                    $(this)
                        .find(
                            " > .wp-block-ultimate-post-mega-menu > .ultp-mega-menu-wrapper"
                        )
                        .css({ maxWidth: `${closetMenuWidth}px`, boxSizing: "border-box" });
                    $(this).css({ left: `${closetMenuLeft - contentLeft}px` });
                    const siblingLeft =
                        $(this).siblings(".ultp-menu-item-label-container")?.offset()
                            ?.left || 0;
                    $(this).css({ left: `${closetMenuLeft - siblingLeft}px` });
                } else {
                    $(this)
                        .find(
                            " > .wp-block-ultimate-post-mega-menu > .ultp-mega-menu-wrapper"
                        )
                        .css({ maxWidth: ``, boxSizing: "" });
                    $(this).css({ left: `` });
                }
            });
        }
    }

    /*
          Menu responsive
      */

    if (isFront) {
        $(window).on("resize", function () {
            handleMegaMenuWidth("resize");
        });
    }
    let currentStack = "";
    let prevStack = [];
    let toAppend;
    let toAppendBack;
    let rawMenu;
    let mv_rcsstype;
    let mv_rstr;
    let mv_naviIcon;
    let mv_naviExpandIcon;
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
                if (mv_rstr == "mv_dissolve" || mv_rstr == "mv_slide") {
                    handleResponsiveMenuHtml(e, "back");
                } else {
                    handleMenuDropDownStyle(e, "back");
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
                if (mv_rstr == "mv_dissolve" || mv_rstr == "mv_slide") {
                    handleResponsiveMenuHtml(e, "next");
                } else {
                    handleMenuDropDownStyle(e, "next");
                }
            }
        );
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
            mv_naviExpandIcon = that
                .closest(".wp-block-ultimate-post-menu")
                .find(
                    "> .ultp-mobile-view-container > .ultp-mv-icons > .ultp-mv-label-icon-expand svg"
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
                toAppendBack.html(mv_HeadText);
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
                    d_none = parentItem
                        .find(
                            "> .ultp-menu-item-wrapper > .ultp-menu-item-content > .wp-block-ultimate-post-mega-menu"
                        )
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

                    if (mv_rstr == "mv_dissolve") {
                        toAppend.find("> *").animate(
                            {
                                opacity: 0.2,
                            },
                            mv_animationDuration,
                            function () {
                                toAppend.html(
                                    mv_rcsstype == "custom"
                                        ? theHtml.replaceAll("ultpMenuCss", "ultpMenu__Css")
                                        : theHtml
                                );

                                toAppend.find("> *").css("opacity", ".1");
                                toAppend.find("> *").animate(
                                    {
                                        opacity: 1,
                                    },
                                    mv_animationDuration
                                );
                            }
                        );
                    } else {
                        // Slide View
                        toAppend.html(
                            mv_rcsstype == "custom"
                                ? theHtml.replaceAll("ultpMenuCss", "ultpMenu__Css")
                                : theHtml
                        );
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
                toAppendBack.html(mv_HeadText);
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

                if (mv_rstr == "mv_dissolve") {
                    toAppend.find("> *").animate(
                        {
                            opacity: 0.2,
                        },
                        mv_animationDuration,
                        function () {
                            toAppend.html(
                                mv_rcsstype == "custom"
                                    ? backHtml.replaceAll("ultpMenuCss", "ultpMenu__Css")
                                    : backHtml
                            );
                        }
                    );
                    toAppend.find("> *").animate(
                        {
                            opacity: 1,
                        },
                        mv_animationDuration
                    );
                } else {
                    // Slide View
                    toAppend.html(
                        mv_rcsstype == "custom"
                            ? backHtml.replaceAll("ultpMenuCss", "ultpMenu__Css")
                            : backHtml
                    );
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

    function handleMenuDropDownStyle(e, type) {
        const that = $(e.target);
        const parentItem = that.closest(".wp-block-ultimate-post-menu-item");
        let c_type = "next";
        if (parentItem.hasClass("ultp-menu-res-css")) {
            c_type = "back";
        } else {
            parentItem.addClass("ultp-menu-res-css");
        }
        let target;
        let theHeight;
        if (c_type == "next") {
            parentItem.addClass("ultp-hammenu-accordian-active");
            if (mv_naviExpandIcon) {
                parentItem
                    .find(
                        "> .ultp-menu-item-wrapper > .ultp-menu-item-label-container .ultp-menu-item-dropdown"
                    )
                    .html(mv_naviExpandIcon);
            }

            if (parentItem.hasClass("hasListMenuChild")) {
                target = parentItem.find(
                    "> .ultp-menu-item-wrapper > .ultp-menu-item-content > .wp-block-ultimate-post-list-menu > .ultp-list-menu-wrapper > .ultp-list-menu-content"
                );
            } else {
                target = parentItem.find(
                    "> .ultp-menu-item-wrapper > .ultp-menu-item-content"
                );
            }
            if (!target.length) {
                target = parentItem.find(
                    "> .ultp-menu-item-wrapper > .ultp-menu-item-content"
                );
            }
            theHeight = target.outerHeight();
            const paddingObj = parentItem.find(
                "> .ultp-menu-item-wrapper > .ultp-menu-item-content"
            );
            const paddingTop = paddingObj.css("padding-top");
            const paddingBottom = paddingObj.css("padding-bottom");

            parentItem
                .find("> .ultp-menu-item-wrapper > .ultp-menu-item-content")
                .html(target.html());
            parentItem
                .find("> .ultp-menu-item-wrapper > .ultp-menu-item-content")
                .css({ height: "0px", "padding-top": "0", "padding-bottom": "0" });
            parentItem
                .find("> .ultp-menu-item-wrapper > .ultp-menu-item-content")
                .animate(
                    {
                        height: theHeight + "px",
                        "padding-top": paddingTop,
                        "padding-bottom": paddingBottom,
                    },
                    mv_animationDuration,
                    function () {
                        $(this).css({
                            height: "",
                            "padding-top": "",
                            "padding-bottom": "",
                        });
                    }
                );
        } else if (c_type == "back") {
            parentItem.removeClass("ultp-hammenu-accordian-active");
            parentItem
                .find(".wp-block-ultimate-post-menu-item")
                .removeClass("ultp-hammenu-accordian-active");
            parentItem
                .find("> .ultp-menu-item-wrapper > .ultp-menu-item-content")
                .animate(
                    {
                        height: "0",
                        paddingTop: "0",
                        paddingBottom: "0",
                    },
                    mv_animationDuration,
                    function () {
                        $(this).css({
                            height: "",
                            paddingTop: "",
                            paddingBottom: "",
                        });
                        if (mv_naviIcon) {
                            parentItem
                                .find(
                                    ".ultp-menu-item-wrapper .ultp-menu-item-label-container .ultp-menu-item-dropdown"
                                )
                                .each(function () {
                                    if ($(this).html()) {
                                        $(this).html(mv_naviIcon);
                                    }
                                });
                        }
                        parentItem.removeClass("ultp-menu-res-css");
                        parentItem
                            .find(".wp-block-ultimate-post-menu-item")
                            .removeClass("ultp-menu-res-css");
                    }
                );
        }
    }
    // *************************************
    // Row Sticky
    // *************************************
    function HandleRowSticky() {
        if ($(".wp-block-ultimate-post-row.row_sticky_active").length > 0) {
            $(".wp-block-ultimate-post-row.row_sticky_active").each(function () {
                let windowScroll = 0;
                const rowSelector = $(this);
                if (
                    rowSelector.hasClass("row_sticky") ||
                    rowSelector.hasClass("row_scrollToStickyTop")
                ) {
                    const elementHeight = rowSelector.height();
                    const elementWidth = rowSelector.outerWidth();
                    const elementPosition = rowSelector.offset().top;
                    const elementPositionLeft = rowSelector.offset().left;
                    $(window).on("scroll", function () {
                        if (rowSelector.hasClass("row_sticky")) {
                            if (
                                !rowSelector.hasClass("alignfull") &&
                                rowSelector.hasClass("stickyTopActive")
                            ) {
                                rowSelector.css({
                                    left: elementPositionLeft,
                                    "max-width": `${elementWidth}px`,
                                    width: "100%",
                                });
                            }
                            if (
                                windowScroll < $(this).scrollTop() &&
                                elementPosition < $(this).scrollTop() + elementHeight
                            ) {
                                $(rowSelector).animate(
                                    { height: elementHeight - elementHeight }, // Target height
                                    10,
                                    "swing",
                                    function () {
                                        rowSelector
                                            .addClass("stickyTopActive")
                                            .removeClass("stickyTopDeActive");
                                    }
                                );
                            } else {
                                if (elementPosition + elementHeight > $(this).scrollTop()) {
                                    rowSelector
                                        .addClass("stickyTopDeActive")
                                        .removeClass("stickyTopActive");
                                }
                                // rowSelector.css({ 'position': 'static !important;'});
                            }
                        } else {
                            if (
                                windowScroll < $(this).scrollTop() &&
                                elementHeight < $(this).scrollTop()
                            ) {
                                rowSelector
                                    .addClass("stickyTopActive")
                                    .removeClass("stickyTopDeActive");
                            } else {
                                rowSelector
                                    .addClass("stickyTopDeActive")
                                    .removeClass("stickyTopActive");
                            }
                        }
                        windowScroll = $(this).scrollTop();
                    });
                }
            });
        }
    }

    if (isFront) {
        HandleRowSticky();
        handleAccordionBlock();
        handleUltpGalleryBlock(3, 10);
        handleGalleryLayout();
    }

    function handleAccordionBlock() {
        if ($(".wp-block-ultimate-post-accordion").length > 0) {
            $(".wp-block-ultimate-post-accordion").each(function () {
                const iniselect = $(this).data("active");
                const autoCollapse = $(this).data("autocollapse");
                const accordionItem = $(this)
                    .children()
                    .children(".wp-block-ultimate-post-accordion-item");
                accordionItem.each(function (idx) {
                    const acItem = $(this);
                    // For initial Select
                    if (idx == iniselect) {
                        $(this).addClass("active active-accordion");
                        acItem
                            .find(".ultp-accordion-item__content").first()
                            .css({ display: "block" });
                    } else {
                        $(this).removeClass("active active-accordion");
                    }
                    // on Click
                    $(this)
                        .children(".ultp-accordion-item")
                        .children(".ultp-accordion-item__navigation")
                        .on("click", function () {
                            const accordioClosestItem = $(this)
                                .parent()
                                .parent(".wp-block-ultimate-post-accordion-item");
                            const content = accordioClosestItem.find(".ultp-accordion-item__content").first();
                            const currentAccordion = accordioClosestItem.parent().parent('.wp-block-ultimate-post-accordion');
                            if (content.is(":visible")) {
                                content.stop(true, true).slideUp(300, function () {
                                    accordioClosestItem.removeClass("active active-accordion");
                                });
                            } else {
                                if (autoCollapse) {
                                    currentAccordion
                                        .find(".ultp-accordion-item__content:visible").first()
                                        .stop(true, true)
                                        .slideUp(300, function () {
                                            accordioClosestItem.siblings().removeClass("active active-accordion");
                                            accordioClosestItem.addClass("active active-accordion");
                                        });
                                }
                                accordioClosestItem.addClass("active active-accordion");
                                content.stop(true, true).slideDown(300);
                            }
                        });
                });
            });
        }
    }

    function handleUltpGalleryBlock() {
        if ($('.wp-block-ultimate-post-gallery').length > 0) {
            $('.wp-block-ultimate-post-gallery').each(function () {
                const gallery = $(this).find('.ultp-gallery-item');
                const loadMoreButton = $(this).find('.ultp-gallery-loadMore');
                const lightBox = $(this).find('.ultp-gallery-lightbox');

                const enableLightbox = $(this).data('lightbox');
                const lightboxIndicator = $(this).data('indicators');
                const lightboxCaption = $(this).data('caption');

                const fullScreen = $(this).find('.ultp-gallery-lightbox__full-screen');
                const galleryIndicator = $(this).find('.ultp-gallery-lightbox__indicator-control');

                const zoomIn = $(this).find('.ultp-gallery-lightbox__zoom-in');
                const zoomOut = $(this).find('.ultp-gallery-lightbox__zoom-out');
                const galleryClose = $(this).find('.ultp-gallery-lightbox__close');

                const galleryControl = $(this).find('.ultp-gallery-lightbox__control');
                let galleryIndex = null;

                let lightboxVisible = true;

                /* 
                    Lightbox Query Start
                */
                /* Lightbox Enable Disable */
                $(document).on('click', function (event) {
                    const $target = $(event.target);
                    const $lightbox = $('.ultp-lightbox');

                    const isInsideLightboxControl = $target.closest('.ultp-gallery-lightbox__control, .ultp-lightbox__left-icon, .ultp-lightbox__right-icon, .ultp-lightbox__img-container, .ultp-lightbox-indicator__item-img').length > 0;

                    if (!isInsideLightboxControl && !lightboxVisible) {
                        $lightbox.hide();
                        galleryControl.hide();
                        lightboxVisible = true;
                    }

                    if ($lightbox.is(':visible')) {
                        lightboxVisible = false;
                    }
                });

                /*  lightbox close */
                galleryClose.on('click', function () {
                    const lightbox = $(this).parent().parent('.ultp-gallery-wrapper').find('.ultp-lightbox');
                    lightbox.hide();
                    galleryControl.hide();
                    if (document.exitFullscreen) {
                        document.exitFullscreen().catch(err => {
                            console.error("Failed to exit fullscreen:", err);
                        });
                    } else if (document.webkitExitFullscreen) {
                        document.webkitExitFullscreen();
                    } else if (document.msExitFullscreen) {
                        document.msExitFullscreen();
                    }
                    galleryIndex = null;
                })

                /*  lightbox zoom in */
                let scale = 1;
                zoomIn.on('click', function () {
                    scale += 0.5;
                    const mainImg = $(this)
                        .closest('.ultp-gallery-wrapper')
                        .find('.ultp-lightbox__inside img');

                    mainImg.css({
                        transform: `scale(${scale})`
                    });
                    if (scale > 1) {
                        $('.ultp-lightbox__caption').slideUp(300);
                        $('.ultp-lightbox-indicator').slideUp(300);


                    } else if (!($('.ultp-lightbox__caption').is(':visible'))) {
                        $('.ultp-lightbox__caption').fadeIn(300);
                    }
                });

                /*  lightbox zoom Out */
                zoomOut.on('click', function () {
                    scale -= 0.5;
                    const mainImg = $(this)
                        .closest('.ultp-gallery-wrapper')
                        .find('.ultp-lightbox__inside img');

                    mainImg.css({
                        transform: `scale(${scale})`
                    });

                    if (!($('.ultp-lightbox__caption').is(':visible')) && scale == 1) {
                        $('.ultp-lightbox__caption').fadeIn(300);
                    }
                    if (!($('.ultp-lightbox-indicator').is(':visible')) && scale == 1) {
                        $('.ultp-lightbox-indicator').fadeIn(300);
                    }


                });
                /*  lightbox Indicator */
                galleryIndicator.off('click').on('click', function (e) {
                    e.stopPropagation();
                    const indicator = $(this)
                        .closest('.ultp-gallery-wrapper')
                        .find('.ultp-lightbox-indicator');

                    indicator.slideToggle(300);
                });
                /*  Full screen enable */
                fullScreen.on('click', function () {
                    const galleryTab = document.documentElement; // fullscreen whole page
                    const isFullscreen = document.fullscreenElement || document.webkitFullscreenElement || document.msFullscreenElement;
                    if (!isFullscreen) {
                        // Enter fullscreen
                        if (galleryTab.webkitRequestFullscreen) {
                            // Safari - no Promise, so just call it
                            galleryTab.webkitRequestFullscreen();
                        } else if (galleryTab.msRequestFullscreen) {
                            // IE11 - no Promise
                            galleryTab.msRequestFullscreen();
                        }
                    } else {
                        // Exit fullscreen
                        if (document.exitFullscreen) {
                            document.exitFullscreen().catch(err => {
                                console.error("Failed to exit fullscreen:", err);
                            });
                        } else if (document.webkitExitFullscreen) {
                            document.webkitExitFullscreen();
                        } else if (document.msExitFullscreen) {
                            document.msExitFullscreen();
                        }
                    }
                })
                
                enableLightbox && lightBox.each(function () {
                    $(this).off().on('click', function (e) {
                        e.stopPropagation();
                        const lightboxSelector = $(this);
                        lightboxVisible = false;
                        const imgId = lightboxSelector.data('id');
                        const imgIndex = lightboxSelector.data('index');
                        const imgSrc = lightboxSelector.data('img');
                        const imgCaption = lightboxSelector.closest('.ultp-gallery-item').data('caption');
                        const glSelector = lightboxSelector.parent().parent();
                        handleLightBox(lightboxSelector, imgId, imgIndex, imgSrc, imgCaption, glSelector, lightboxCaption, lightboxIndicator);
                    });
                });
                
                $(document).on('click', '.ultp-lightbox__right-icon, .ultp-lightbox__left-icon', function () {
                    const $this = $(this);
                    const isRight = $this.hasClass('ultp-lightbox__right-icon');
                    const $lightbox = $this.closest('.ultp-lightbox');
                    const $galleryContainer = $this.closest('.ultp-gallery-container');
                    const $indicator = $lightbox.find('.ultp-lightbox-indicator');
                    const $mainImg = $lightbox.find('.ultp-lightbox__img');
                    const imgCaption = $lightbox.find('.ultp-lightbox__caption');
                    const $galleryItems = $galleryContainer.children();
                    const activeIndex = $lightbox.data('index');

                    if (galleryIndex == null) {
                        galleryIndex = activeIndex;
                    }

                    // Calculate new index
                    galleryIndex = isRight
                        ? (galleryIndex + 1) % $galleryItems.length
                        : (galleryIndex - 1 + $galleryItems.length) % $galleryItems.length;

                    const $targetItem = $galleryItems.eq(galleryIndex);
                    const newImgSrc = $targetItem.find('.ultp-gallery-media img').attr('src');
                    const itemId = $targetItem.data('id');
                    const newCaption = $targetItem.data('caption');

                    // Animate image fade out -> change src -> fade in
                    $mainImg.fadeOut(200, function () {
                        $mainImg.attr('src', newImgSrc).fadeIn(200);
                    });

                    // Optional: animate caption change as well
                    imgCaption.fadeOut(200, function () {
                        imgCaption.text(newCaption).fadeIn(200);
                    });

                    // Update indicator
                    const $activeIndicator = $indicator.children().eq(galleryIndex);
                    $activeIndicator.addClass('lightbox-active').siblings().removeClass('lightbox-active');

                    if ($activeIndicator.data('id') !== itemId) {
                        $activeIndicator.removeClass('lightbox-active');
                    }
                });


                $(document).on('click', '.ultp-lightbox-indicator__item', function () {
                    const indicator = $(this);
                    const getImgSrc = indicator.find('img').attr('src');
                    const lightbox = indicator.closest('.ultp-lightbox');
                    const mainImg = lightbox.find('.ultp-lightbox__img');
                    galleryIndex = indicator.data('index');

                    mainImg.fadeOut(200, function () {
                        mainImg.attr('src', getImgSrc).fadeIn(200);
                    });
                    indicator.addClass('lightbox-active').siblings().removeClass('lightbox-active');
                });

                enableLightbox && !(lightBox.length > 0) && gallery.off().on('click', function (event) {
                    const downloadButton = $(event.target).closest('svg').parent().is('.ultp-gallery-action a');
                    if (!(gallery.find('.ultp-lightbox').is(':visible')) && !downloadButton) {
                        const glSelector = $(this);
                        galleryIndex = glSelector.data('index');
                        const imgId = glSelector.data('id');
                        const imgCaption = glSelector.data('caption');
                        const imgIndex = glSelector.data('index');
                        const imgSrc = glSelector.find('.ultp-gallery-media img').attr('src');
                        const appendSelector = glSelector.find('.ultp-gallery-action-container');
                        handleLightBox(glSelector, imgId, imgIndex, imgSrc, imgCaption, appendSelector, lightboxCaption, lightboxIndicator);
                    }
                })
                /*  
                    Loadmore 
                */
                const loadMoreCount = loadMoreButton[0] ? Number(getComputedStyle(loadMoreButton[0]).getPropertyValue('--ultp-gallery-count').trim()) : '';
                let rootImgCount = loadMoreCount;

                if (loadMoreButton?.length > 0) {
                    gallery.each(function (index) {
                        if (loadMoreCount < (index + 1)) {
                            $(this).find('img').attr({ width: $(this).find('img').width(), height: $(this).find('img').height() })
                            $(this).css({ display: 'none' })
                        }
                    })
                }

                if (gallery.length <= rootImgCount) {
                    loadMoreButton.css({ display: 'none' });
                }

                function handleLightBox(selector, imgId, imgIndex, imgSrc, imgCaption, appendSelector, lightboxCaption, lightboxIndicator) {
                    galleryControl.css({ display: 'flex' });
                    $('.ultp-lightbox').remove();
                    const indicatorHtml = gallery.map(function (index) {
                        const imgSrc = $(this).find('img').attr('src');
                        const caption = $(this).data('caption');
                        if (imgId == $(this).data('id')) {
                            galleryIndex = index;
                        }
                        return `<div  data-index=${index} class="${imgId == $(this).data('id') ? "ultp-lightbox-indicator__item lightbox-active" : 'ultp-lightbox-indicator__item'}" data-id=${$(this).data('id')} data-caption=${caption}><img class="ultp-lightbox-indicator__item-img" src="${imgSrc}" /></div>`;
                    }).get().join('');

                    const galleryData = `<div class="ultp-lightbox" data-id=${imgId} data-index=${imgIndex}>
                        <div class="ultp-lightbox__container">
                            <div class="ultp-lightbox__inside">
                                <div class="ultp-lightbox__left-icon"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 49.16 37.25"><path stroke="rgba(0,0,0,0)" stroke-miterlimit="10" d="M18.157 36.154 2.183 20.179l-.053-.053-1.423-1.423 17.45-17.449a2.05 2.05 0 0 1 2.9 2.9l-12.503 12.5h38.053a2.05 2.05 0 1 1 0 4.1H8.555l12.5 12.5a2.05 2.05 0 1 1-2.9 2.9Z"></path></svg></div>
                                <div class="ultp-lightbox__img-container">
                                    <img class="ultp-lightbox__img" src="${imgSrc}" />
                                    ${lightboxCaption ? `<span class="ultp-lightbox__caption">${imgCaption}</span>` : ''}
                                </div>
                                <div class="ultp-lightbox__right-icon"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 49.16 37.25"><path stroke="rgba(0,0,0,0)" stroke-miterlimit="10" d="M28.1 36.154a2.048 2.048 0 0 1 0-2.9l12.5-12.5H2.55a2.05 2.05 0 1 1 0-4.1H40.6l-12.5-12.5a2.05 2.05 0 1 1 2.9-2.9l17.45 17.448L31 36.154a2.047 2.047 0 0 1-2.9 0Z"></path></svg></div>
                            </div>
                            ${lightboxIndicator ? `<div class="ultp-lightbox-indicator">${indicatorHtml}</div>` : ''}
                        </div>
                    </div>`;
                    appendSelector.append(galleryData);
                }
            })
        }
    }


    function handleGalleryLayout() {
        if ($('.wp-block-ultimate-post-gallery').length > 0) {
            $('.wp-block-ultimate-post-gallery').each(function () {
                const $gallery = $(this);
                const container = $gallery.find(".ultp-gallery-container");
                const masonry = $gallery.find(".ultp-gallery-container.ultp-gallery-masonry");
                const tiled = $gallery.find(".ultp-gallery-container.ultp-gallery-tiled");
                const filterItem = $gallery.find('.ultp-gallery-filter__item');

                const columns = Number(getComputedStyle(container[0]).getPropertyValue('--ultp-gallery-columns').trim());
                const gap = Number(getComputedStyle(container[0]).getPropertyValue('--ultp-gallery-gap').trim());

                const galleryItems = container.find('.ultp-gallery-item');
                const loadMoreButton = $gallery.find('.ultp-gallery-loadMore');
                const loadMoreCount = loadMoreButton[0] ? Number(getComputedStyle(loadMoreButton[0]).getPropertyValue('--ultp-gallery-count').trim()) : 0;

                let rootImgCount = loadMoreCount;

                // Add Loader if not present
                let loader = $gallery.find('.ultp-gallery-loader');
                if (loader.length === 0 && (masonry?.length || tiled?.length)) {
                    container.css({ height: '250px', overflow: 'hidden' });
                    loader = $(`<div class="ultp-gallery-loader" style="display:none;position:absolute;top:0;left:0;right:0;bottom:0;background:rgb(255 255 255 / 92%);z-index:9; display: flex; align-items:center;justify-content:center;">
                                <div class="spinner" style="border: 4px solid #f3f3f3;border-top: 4px solid #037FFF;border-radius: 50%;width: 30px;height: 30px;animation: spin 0.8s linear infinite;"></div>
                            </div>`);
                    $gallery.css('position', 'relative');
                    container.append(loader);
                }

                // Inject keyframes for spinner if not already added
                if (!document.getElementById('ultp-spinner-style')) {
                    const spinnerStyle = `<style id="ultp-spinner-style">
                    @keyframes spin {
                        0% { transform: rotate(0deg); }
                        100% { transform: rotate(360deg); }
                    }
                </style>`;
                    $('head').append(spinnerStyle);
                }

                // Show loader initially
                loader.fadeIn(150);

                // Add No Gallery Message
                let $noItems = $gallery.find('.ultp-no-gallery-message');
                if ($noItems.length === 0) {
                    $noItems = $('<div class="ultp-no-gallery-message">No gallery item found</div>');
                    container.after($noItems);
                }

                let loadGallery = function () { };

                function reLayoutTiledGallery() {
                    if (tiled.length === 0) return;
                    const visibleItems = tiled.find('.ultp-gallery-item:visible');
                    tiled.css('visibility', 'hidden');
                    visibleItems.each(function () {
                        $(this).css({ top: '', left: '', width: '', height: '', position: '' });
                    });
                    requestAnimationFrame(() => {
                        loadGallery(visibleItems);
                        tiled.css('visibility', 'visible');
                    });
                }

                function reLayoutMasonryGallery() {
                    if (masonry.length === 0) return;
                    const containerWidth = masonry.width();
                    const itemWidth = (containerWidth - (columns - 1) * gap) / columns;
                    const visibleItems = masonry.find('.ultp-gallery-item:visible');
                    let columnHeights = new Array(columns).fill(0);
                    visibleItems.each(function () {
                        const $el = $(this);
                        $el.css({ width: itemWidth + 'px', position: 'absolute' });
                        const minCol = columnHeights.indexOf(Math.min(...columnHeights));
                        const top = columnHeights[minCol];
                        const left = minCol * (itemWidth + gap);
                        $el.stop().animate({ top: `${top}px`, left: `${left}px` }, 300);
                        columnHeights[minCol] += $el.outerHeight(true) + gap;
                    });
                    masonry.css('height', Math.max(...columnHeights) + 'px');
                }

                function updateVisibleItemsByFilter(selectedTag, limit) {
                    let imgCount = 0;
                    let totalMatch = 0;
                    galleryItems.each(function () {
                        const $this = $(this);
                        const tag = $this.data('tag') || '';
                        if (selectedTag === 'All' || tag.includes(selectedTag)) {
                            totalMatch++;
                            if (imgCount < limit) {
                                $this.css({ display: 'block' });
                                imgCount++;
                            } else {
                                $this.css({ display: 'none' });
                            }
                        } else {
                            $this.css({ display: 'none' });
                        }
                    });
                    $noItems.toggle(totalMatch === 0);
                    return { visibleCount: imgCount, totalMatch };
                }

                function showLoader(callback) {
                    loader.fadeIn(150);
                    setTimeout(() => {
                        callback();
                        loader.fadeOut(150);
                    }, 400);
                }

                filterItem.on('click', function () {
                    const item = $(this);
                    const selectedTag = item.text();
                    item.siblings().removeClass('active-gallery-filter');
                    item.addClass('active-gallery-filter');

                    rootImgCount = loadMoreCount;

                    showLoader(() => {
                        const { visibleCount, totalMatch } = updateVisibleItemsByFilter(selectedTag, rootImgCount);
                        loadMoreButton.css({ display: visibleCount < totalMatch ? 'block' : 'none' });
                        reLayoutTiledGallery();
                        reLayoutMasonryGallery();
                    });
                });

                loadMoreButton.on('click', function (e) {
                    e.preventDefault();
                    const activeTag = $gallery.find('.active-gallery-filter').text() || 'All';

                    let visibleCount = 0;
                    let totalMatch = 0;

                    galleryItems.each(function () {
                        const $this = $(this);
                        const tag = $this.data('tag') || '';
                        if (activeTag === 'All' || tag.includes(activeTag)) {
                            totalMatch++;
                            if ($this.is(':visible')) visibleCount++;
                        }
                    });

                    const remainder = visibleCount % columns;
                    let itemsToAdd = remainder !== 0 ? columns - remainder : columns;
                    rootImgCount = Math.min(visibleCount + itemsToAdd, totalMatch);

                    showLoader(() => {
                        let currentVisible = 0;
                        galleryItems.each(function () {
                            const $this = $(this);
                            const tag = $this.data('tag') || '';
                            if (activeTag === 'All' || tag.includes(activeTag)) {
                                $this.css({ display: currentVisible < rootImgCount ? 'block' : 'none' });
                                currentVisible++;
                            } else {
                                $this.css({ display: 'none' });
                            }
                        });

                        if (rootImgCount >= totalMatch) {
                            loadMoreButton.css({ display: 'none' });
                        }

                        $noItems.toggle(totalMatch === 0);
                        reLayoutTiledGallery();
                        reLayoutMasonryGallery();
                    });
                });

                if (tiled.length) {
                const colGap = Number(getComputedStyle(container[0]).getPropertyValue('--ultp-gallery-gap').trim());
                const customColumns = Number(getComputedStyle(container[0]).getPropertyValue('--ultp-gallery-columns').trim());
                const customHeight = Number(getComputedStyle(container[0]).getPropertyValue('--ultp-gallery-height').trim());

                console.log(customHeight, "customHeight", getComputedStyle(container[0]).getPropertyValue('--ultp-gallery-height').trim());

                const gap        = colGap;                   // horizontal & vertical gap in px
                const columns    = customColumns || 3;   // # of items per row
                const rowHeight  = customHeight || 300;                  // FIX ME → the target rendered height

                /* Utility: robust aspect ratio (works before <img> finishes loading) */
                function getAspect($img) {
                    const el = $img[0];
                    const w  = el.naturalWidth  || el.width  || 1;
                    const h  = el.naturalHeight || el.height || 1;
                    return w / h;
                }

                /* Core layout function -------------------------------------------------- */
                loadGallery = function (visibleItems) {
                    const $container   = tiled;
                    const containerW   = $container.width();
                    let   cursorTop    = 0;

                    /* Prepare rows -------------------------------------------------------- */
                    const rows = [];
                    let currentRow      = [];
                    let currentRatioSum = 0;

                    visibleItems.each(function () {
                    const $item = $(this);
                    const $img  = $item.find("img");
                    const ratio = getAspect($img);

                    currentRow.push({ $item, ratio });
                    currentRatioSum += ratio;

                    if (currentRow.length === columns) {
                        rows.push({ cells: currentRow, ratioSum: currentRatioSum });
                        currentRow      = [];
                        currentRatioSum = 0;
                    }
                    });

                    /* last partial row (if any) */
                    if (currentRow.length) {
                    rows.push({ cells: currentRow, ratioSum: currentRatioSum });
                    }

                    /* Layout every row ---------------------------------------------------- */
                    rows.forEach(row => {
                    const gapsTotal = gap * (row.cells.length - 1);
                    const availW    = containerW - gapsTotal;

                    let cursorLeft = 0;
                    row.cells.forEach(({ $item, ratio }, idx) => {
                        const cellW = availW * (ratio / row.ratioSum);

                        $item.css({
                        position : "absolute",
                        top      : cursorTop,
                        left     : cursorLeft,
                        width    : cellW,
                        height   : rowHeight,          // keeps every image exactly this tall
                        });

                        cursorLeft += cellW + gap;
                    });

                    cursorTop += rowHeight + gap;
                    });

                    /* set container height once, after all items are positioned */
                    $container.height(cursorTop - gap);
                };
                }

                setTimeout(() => {
                    const initialFilter = $gallery.find('.ultp-gallery-filter__item.active-gallery-filter').text() || 'All';
                    const { visibleCount, totalMatch } = updateVisibleItemsByFilter(initialFilter, rootImgCount);
                    loadMoreButton.css({ display: visibleCount < totalMatch ? 'block' : 'none' });
                    $noItems.toggle(totalMatch === 0);
                    reLayoutTiledGallery();
                    reLayoutMasonryGallery();
                    loader.fadeOut(50);
                }, 500);
            });
        }
    }
    // Run on load and resize
    if (isFront) {
        $(window).on('load resize', function () {
            handleGalleryLayout();
        });
    }
})(jQuery);
