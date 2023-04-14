window.addEventListener('load', function () {

    const scrollToTopButton = document.getElementById('scroll-to-top');

    document.addEventListener('scroll', function () {
        if (window.scrollY > 100) {
            scrollToTopButton.classList.add('scroll-to-top--visible');
        } else {
            scrollToTopButton.classList.remove('scroll-to-top--visible');
        }
    });

    scrollToTopButton.addEventListener('click', function () {
        window.scrollTo({top: 0, behavior: 'smooth'});
    });

});
