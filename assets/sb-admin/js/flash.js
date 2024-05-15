window.addEventListener('load', function () {

    const flashMessage = document.getElementById('crudit-flash');

    if (flashMessage) {
        document.addEventListener('scroll', function () {
            if (window.scrollY >= flashMessage.getBoundingClientRect().top) {
                flashMessage.classList.add('crudit-flash--fixed');
            } else {
                flashMessage.classList.remove('crudit-flash--fixed');
            }
        });
    }
});
