import "../css/app.scss";
import jquery from "jquery"
import dt from "datatables.net"

global.$ = global.jQuery = global.jquery = jquery;
global.dt = dt;
import "bootstrap";
import "startbootstrap-sb-admin-2/js/sb-admin-2.js";

(function($) {
    "use strict"; // Start of use strict
    // Toggle the side navigation
    $("#sidebarToggle, #sidebarToggleTop").on('click', function(e) {
        $("body").toggleClass("sidebar-toggled");
        $(".sidebar").toggleClass("toggled");
        if ($(".sidebar").hasClass("toggled")) {
            $('.sidebar .collapse').collapse('hide');
        }
    });

    // Close any open menu accordions when window is resized below 768px
    $(window).resize(function() {
        if ($(window).width() < 768) {
            $('.sidebar .collapse').collapse('hide');
        }

        // Toggle the side navigation when window is resized below 480px
        if ($(window).width() < 480 && !$(".sidebar").hasClass("toggled")) {
            $("body").addClass("sidebar-toggled");
            $(".sidebar").addClass("toggled");
            $('.sidebar .collapse').collapse('hide');
        }
    });

    // Prevent the content wrapper from scrolling when the fixed side navigation hovered over
    $('body.fixed-nav .sidebar').on('mousewheel DOMMouseScroll wheel', function(e) {
        if ($(window).width() > 768) {
            var e0 = e.originalEvent,
                delta = e0.wheelDelta || -e0.detail;
            this.scrollTop += (delta < 0 ? 1 : -1) * 30;
            e.preventDefault();
        }
    });

    // Scroll to top button appear
    $(document).on('scroll', function() {
        var scrollDistance = $(this).scrollTop();
        if (scrollDistance > 100) {
            $('.scroll-to-top').fadeIn();
        } else {
            $('.scroll-to-top').fadeOut();
        }
    });

    // Smooth scrolling using jQuery easing
    $(document).on('click', 'a.scroll-to-top', function(e) {
        var $anchor = $(this);
        $('html, body').stop().animate({
            scrollTop: ($($anchor.attr('href')).offset().top)
        }, 1000, 'easeInOutExpo');
        e.preventDefault();
    });


    $('a.active').find('.show-toggle').each(function (e, el) {
        let elm = $(el);
        if(elm && elm.attr('data-toggle')) {
            let target = $(elm.attr('data-toggle'));
            target.addClass('show')
        }
    });
    $(document).on('click','.show-toggle', function (e) {
        e.stopPropagation();
        e.preventDefault();
        let elm = $(e.currentTarget);
        console.log(elm.attr('data-toggle'))


        console.log(elm.attr('data-toggle'));
        if(elm.attr('data-toggle')) {

            let target = $(elm.attr('data-toggle'));
            console.log(target);
            if (target.hasClass('show')) {
                target.removeClass('show');
            } else {
                target.addClass('show')
            }
        }
    });

})(jquery); // End of use strict
