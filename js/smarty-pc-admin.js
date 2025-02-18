jQuery(document).ready(function($) {
    // Handle tab switching
    $(".smarty-pc-nav-tab").click(function (e) {
        e.preventDefault();
        $(".smarty-pc-nav-tab").removeClass("smarty-pc-nav-tab-active");
        $(this).addClass("smarty-pc-nav-tab-active");

        $(".smarty-pc-tab-content").removeClass("active");
        $($(this).attr("href")).addClass("active");
    });

    // Load README.md
    $("#smarty-pc-load-readme-btn").click(function () {
        const $content = $("#smarty-pc-readme-content");
        $content.html("<p>Loading...</p>");

        $.ajax({
            url: smartyProductCarousel.ajaxUrl,
            type: "POST",
            data: {
                action: "smarty_pc_load_readme",
                nonce: smartyProductCarousel.nonce,
            },
            success: function (response) {
                console.log(response);
                if (response.success) {
                    $content.html(response.data);
                } else {
                    $content.html("<p>Error loading README.md</p>");
                }
            },
        });
    });

    // Load CHANGELOG.md
    $("#smarty-pc-load-changelog-btn").click(function () {
        const $content = $("#smarty-pc-changelog-content");
        $content.html("<p>Loading...</p>");

        $.ajax({
            url: smartyProductCarousel.ajaxUrl,
            type: "POST",
            data: {
                action: "smarty_pc_load_changelog",
                nonce: smartyProductCarousel.nonce,
            },
            success: function (response) {
                console.log(response);
                if (response.success) {
                    $content.html(response.data);
                } else {
                    $content.html("<p>Error loading CHANGELOG.md</p>");
                }
            },
        });
    });
});