// Toast
window.addEventListener('load', function () {
    document.querySelectorAll('.np-toast').forEach(function (el) {
        bootstrap.Toast.getOrCreateInstance(el, { delay: 3500 }).show();
    });
});
