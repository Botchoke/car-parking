document.addEventListener("DOMContentLoaded", function () {

    const counters = document.querySelectorAll(".counter");

    counters.forEach(counter => {

        const target = Number(counter.getAttribute("data-target"));
        let count = 0;

        const updateCounter = () => {

            if (count < target) {
                count++;
                counter.innerText = count;
                setTimeout(updateCounter, 20);
            } else {
                counter.innerText = target;
            }

        };

        updateCounter();

    });

});

/* ============================================
   LIVE TIMER ONLY
   (No fee calculation here — fee is fixed by PHP)
   ============================================ */

function updateTimers() {

    let rows = document.querySelectorAll("#vehicleTable tbody tr");

    rows.forEach(row => {

        let timer = row.querySelector(".timer");

        if (!timer) return;

        let rawTime = timer.dataset.time;

        let start = new Date(rawTime.replace(" ", "T"));
        let now = new Date();

        let diff = Math.floor((now - start) / 1000);

        if (isNaN(diff) || diff < 0) {
            diff = 0;
        }

        let hours = Math.floor(diff / 3600);
        let minutes = Math.floor((diff % 3600) / 60);
        let seconds = diff % 60;

        // Display as H:MM:SS format
        if (hours > 0) {
            timer.innerText =
                hours + ":" +
                (minutes < 10 ? "0" + minutes : minutes) + ":" +
                (seconds < 10 ? "0" + seconds : seconds);
        } else {
            timer.innerText =
                minutes + ":" +
                (seconds < 10 ? "0" + seconds : seconds);
        }

    });

}

setInterval(updateTimers, 1000);
updateTimers();