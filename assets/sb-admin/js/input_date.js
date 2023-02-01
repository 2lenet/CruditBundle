window.addEventListener('load', function () {

    const inputsDate = document.querySelectorAll('input[type="date"], input[type="datetime-local"]');

    inputsDate.forEach(function (inputDate) {
        inputDate.addEventListener('click', function () {
            this.showPicker();
        });

        inputDate.addEventListener('focus', function () {
            this.showPicker();
        });
    });

});
