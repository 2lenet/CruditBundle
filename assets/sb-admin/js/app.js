import "bootstrap";

window.addEventListener('load',function() {

    document.querySelectorAll(".valuesetter").forEach(choice => {
        choice.addEventListener("click", (e) => {
            console.log(e.target.dataset);
            const hidden =  document.getElementById(e.target.dataset.valueid);
            hidden.value = e.target.dataset.value;
            /*
                TODO: SET the right icon when selected ( maybe use real svg ? )
                const button_icon =  document.getElementById(e.target.dataset.valueid+"_icon");
                console.log(button_icon);
                button_icon.classList = e.target.querySelector("i").classList;
            */
        })
    });

    // sidebar Toggle
    document.getElementById("sidebarToggle").addEventListener('click', function () {
        document.querySelector("body").classList.toggle("sidebar-toggled");
        document.querySelector(".sidebar").classList.toggle("toggled");
        /*if ($(".sidebar").hasClass("toggled")) {
            $('.sidebar .collapse').collapse('hide');
        }*/
    });
});

