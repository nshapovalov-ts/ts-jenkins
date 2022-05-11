
require([
    'jquery',
    'domReady!'
], function ($) {
    window.onbeforeunload = renderLoading;
    function renderLoading() {
        Pace.stop();
        var paceEle = $(Pace.bar.el);
        paceEle.removeClass('pace-inactive').addClass('pace-active');
        var timer = 0;
        var intervalId = setInterval(frame, 100);

        function frame() {
            if (timer === 96) {
                // Clear the timer interval once its reached 96%
                clearInterval(intervalId);
            } else {
                timer = timer + 1;
                // Increase the Percentage of progressbar
                Pace.bar.progress = timer;
                // Call render function to the progress bar and it updates the percentage of the loading bar.
                Pace.bar.render();
            }
        }
    }
});

